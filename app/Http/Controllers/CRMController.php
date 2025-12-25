<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Followup;
use App\Models\Assignment;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CRMController extends Controller
{
    /**
     * Display the CRM dashboard
     */
    public function index()
    {
        // User Fetch
        $user = Auth::user();

        // Dashboard stats
        $stats = [
            'activeLeads' => Lead::active()->count(),
            'convertedLeads' => Lead::converted()->count(),
            'todayFollowups' => Followup::dueToday()->pending()->count(),
            'overdueFollowups' => Followup::overdue()->count(),
        ];

        // Today's followups
        $todayFollowups = Followup::dueToday()
            ->pending()
            ->with(['lead', 'createdBy'])
            ->orderBy('created_at')
            ->get();

        // Recent leads
        $recentLeads = Lead::with('createdBy')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('crm.index', compact('stats', 'todayFollowups', 'recentLeads', 'user'));
    }

    /**
     * Get dashboard data for AJAX updates
     */
    public function dashboardData()
    {
        $stats = [
            'activeLeads' => Lead::active()->count(),
            'convertedLeads' => Lead::converted()->count(),
            'todayFollowups' => Followup::dueToday()->pending()->count(),
            'overdueFollowups' => Followup::overdue()->count(),
        ];

        $todayFollowups = Followup::dueToday()
            ->pending()
            ->with(['lead', 'createdBy'])
            ->orderBy('created_at')
            ->get()
            ->map(function ($followup) {
                return [
                    'id' => $followup->id,
                    'lead' => [
                        'id' => $followup->lead->id,
                        'first_name' => $followup->lead->first_name,
                        'last_name' => $followup->lead->last_name,
                        'mobile' => $followup->lead->mobile,
                        'status' => $followup->lead->status,
                    ],
                    'type' => $followup->type,
                    'notes' => $followup->notes,
                    'type_icon' => $followup->type_icon,
                ];
            });

        $recentLeads = Lead::with('createdBy')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'first_name' => $lead->first_name,
                    'last_name' => $lead->last_name,
                    'email' => $lead->email,
                    'mobile' => $lead->mobile,
                    'status' => $lead->status,
                    'source' => $lead->source,
                    'created_at' => $lead->created_at->format('M d, Y'),
                ];
            });

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'todayFollowups' => $todayFollowups,
            'recentLeads' => $recentLeads,
        ]);
    }

    /**
     * Display leads with pagination and filters
     */
    public function leads(Request $request)
    {
        $query = Lead::with(['followups', 'createdBy', 'assignments']);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply source filter
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        // Apply interest filter
        if ($request->filled('interest')) {
            $query->where('interest', $request->interest);
        }

        // Apply country filter
        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }

        // Pagination
        $leads = $query->orderBy('created_at', 'desc')
                      ->paginate(10);

        // Add formatted data for frontend
        $leads->getCollection()->transform(function ($lead) {
            $lead->formatted_created_at = $lead->created_at->format('M d, Y');
            $lead->pending_followups = $lead->followups->where('completed', false)->count();
            $lead->last_followup = $lead->followups->first();
            return $lead;
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'leads' => $leads->items(),
                'pagination' => [
                    'current_page' => $leads->currentPage(),
                    'last_page' => $leads->lastPage(),
                    'per_page' => $leads->perPage(),
                    'total' => $leads->total(),
                    'from' => $leads->firstItem(),
                    'to' => $leads->lastItem(),
                    'has_more_pages' => $leads->hasMorePages(),
                ],
            ]);
        }

        return view('crm.leads', compact('leads'));
    }

    /**
     * Store a new lead
     */
    public function storeLead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:255',
            'lastName' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:leads,email',
            'mobile' => 'required|string|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'interest' => 'nullable|in:Low,Medium,High',
            'notes' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $validated = $validator->validated();
            
            $lead = Lead::create([
                'first_name' => $validated['firstName'],
                'last_name' => $validated['lastName'] ?? null,
                'email' => $validated['email'] ?? null,
                'mobile' => $validated['mobile'],
                'whatsapp' => $validated['whatsapp'] ?? null,
                'country' => $validated['country'] ?? null,
                'source' => $validated['source'] ?? null,
                'interest' => $validated['interest'] ?? null,
                'notes' => $validated['notes'],
                'status' => 'cold',
                'created_by' => auth()->id(),
            ]);

            // Log activity
            $lead->logActivity(
                'created',
                "Lead {$lead->full_name} was created by " . auth()->user()->name,
                [],
                $validated
            );

            return response()->json([
                'success' => true,
                'message' => 'Lead added successfully!',
                'lead' => $lead->load('createdBy'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating lead: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show lead details
     */
    public function showLead($id)
    {
        try {
            $lead = Lead::with([
                'followups.createdBy',
                'assignments.assignedBy',
                'assignments.assignedTo',
                'activities.user',
                'createdBy'
            ])->findOrFail($id);

            // Format followups for display
            $lead->followups->transform(function ($followup) {
                $followup->formatted_date = $followup->followup_date->format('M d, Y');
                $followup->type_icon = $followup->type_icon;
                return $followup;
            });

            return response()->json([
                'success' => true,
                'lead' => $lead,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found',
            ], 404);
        }
    }

    /**
     * Update lead
     */
    public function updateLead(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:leads,email,' . $id,
            'mobile' => 'required|string|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'status' => 'required|in:hot,warm,cold,converted',
            'interest' => 'nullable|in:Low,Medium,High',
            'notes' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $lead = Lead::findOrFail($id);
            $oldValues = $lead->toArray();
            
            $lead->update($validator->validated());

            // Log activity
            $lead->logActivity(
                'updated',
                "Lead {$lead->full_name} was updated by " . auth()->user()->name,
                $oldValues,
                $validator->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Lead updated successfully!',
                'lead' => $lead->fresh(['createdBy']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating lead: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete lead
     */
    public function deleteLead($id)
    {
        try {
            $lead = Lead::findOrFail($id);
            $leadName = $lead->full_name;
            
            $lead->delete();

            return response()->json([
                'success' => true,
                'message' => "Lead {$leadName} deleted successfully!",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting lead: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store followup for a lead
     */
    public function storeFollowup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|exists:leads,id',
            'followup_date' => 'required|date|after_or_equal:today',
            'type' => 'required|in:call,email,meeting,whatsapp,other',
            'notes' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $validated = $validator->validated();
            $validated['created_by'] = auth()->id();

            $followup = Followup::create($validated);
            $lead = Lead::find($validated['lead_id']);

            // Log activity
            $lead->logActivity(
                'followup_added',
                "Follow-up scheduled for " . Carbon::parse($validated['followup_date'])->format('M d, Y'),
                [],
                $validated
            );

            return response()->json([
                'success' => true,
                'message' => 'Follow-up added successfully!',
                'followup' => $followup->load('createdBy'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating follow-up: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark followup as completed
     */
    public function completeFollowup($id)
    {
        try {
            $followup = Followup::findOrFail($id);
            $followup->markAsCompleted();

            // Log activity
            $followup->lead->logActivity(
                'followup_completed',
                "Follow-up marked as completed by " . auth()->user()->name,
                ['completed' => false],
                ['completed' => true]
            );

            return response()->json([
                'success' => true,
                'message' => 'Follow-up marked as completed!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error completing follow-up: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign lead to user
     */
    public function assignLead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|exists:leads,id',
            'assigned_to' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $validated = $validator->validated();
            
            $assignment = Assignment::create([
                'lead_id' => $validated['lead_id'],
                'assigned_by' => auth()->id(),
                'assigned_to' => $validated['assigned_to'],
                'assigned_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $lead = Lead::find($validated['lead_id']);
            $assignedTo = User::find($validated['assigned_to']);

            // Log activity
            $lead->logActivity(
                'assigned',
                "Lead assigned to {$assignedTo->name} by " . auth()->user()->name,
                [],
                $validated
            );

            return response()->json([
                'success' => true,
                'message' => 'Lead assigned successfully!',
                'assignment' => $assignment->load(['assignedBy', 'assignedTo']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error assigning lead: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get assignments with filters
     */
    public function assignments(Request $request)
    {
        $filter = $request->get('filter', 'all');
        
        $query = Assignment::with(['lead', 'assignedBy', 'assignedTo']);

        switch ($filter) {
            case 'by-me':
                $query->where('assigned_by', auth()->id());
                break;
            case 'to-me':
                $query->where('assigned_to', auth()->id());
                break;
            default:
                // Show all assignments the user has access to
                $query->where(function ($q) {
                    $q->where('assigned_by', auth()->id())
                      ->orWhere('assigned_to', auth()->id());
                });
        }

        $assignments = $query->where('status', 'active')
                            ->orderBy('assigned_at', 'desc')
                            ->get()
                            ->map(function ($assignment) {
                                return [
                                    'id' => $assignment->id,
                                    'lead_name' => $assignment->lead->full_name,
                                    'lead_id' => $assignment->lead->id,
                                    'assigned_by' => $assignment->assignedBy->name,
                                    'assigned_to' => $assignment->assignedTo->name,
                                    'assigned_date' => $assignment->assigned_at->format('M d, Y'),
                                    'status' => $assignment->status,
                                    'notes' => $assignment->notes,
                                ];
                            });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'assignments' => $assignments,
            ]);
        }

        return view('crm.assignments', compact('assignments'));
    }

    /**
     * Get forms
     */
    public function forms(Request $request)
    {
        $query = Form::where('created_by', auth()->id());

        $forms = $query->orderBy('created_at', 'desc')
                      ->get()
                      ->map(function ($form) {
                          return [
                              'id' => $form->id,
                              'title' => $form->title,
                              'description' => $form->description,
                              'submissions' => $form->submissions_count,
                              'is_active' => $form->is_active,
                              'created_at' => $form->created_at->format('M d, Y'),
                              'public_url' => $form->public_url,
                              'slug' => $form->slug,
                          ];
                      });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'forms' => $forms,
            ]);
        }

        return view('crm.forms', compact('forms'));
    }

    /**
     * Store a new form
     */
    public function storeForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'submit_button_text' => 'nullable|string|max:255',
            'success_message' => 'nullable|string|max:255',
            'standard_fields' => 'nullable|array',
            'custom_fields' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $validated = $validator->validated();
            $validated['created_by'] = auth()->id();

            $form = Form::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Form created successfully!',
                'form' => [
                    'id' => $form->id,
                    'title' => $form->title,
                    'description' => $form->description,
                    'submissions' => $form->submissions_count,
                    'is_active' => $form->is_active,
                    'created_at' => $form->created_at->format('M d, Y'),
                    'public_url' => $form->public_url,
                    'slug' => $form->slug,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating form: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle form status
     */
    public function toggleFormStatus($id)
    {
        try {
            $form = Form::findOrFail($id);
            $form->update(['is_active' => !$form->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Form status updated successfully!',
                'is_active' => $form->is_active,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating form status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete form
     */
    public function deleteForm($id)
    {
        try {
            $form = Form::findOrFail($id);
            $formTitle = $form->title;
            
            $form->delete();

            return response()->json([
                'success' => true,
                'message' => "Form '{$formTitle}' deleted successfully!",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting form: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle public form submission
     */
    public function submitForm(Request $request, $slug)
    {
        try {
            $form = Form::where('slug', $slug)
                       ->where('is_active', true)
                       ->firstOrFail();

            // Create form submission
            $submission = $form->submissions()->create([
                'form_data' => $request->all(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer' => $request->header('referer'),
            ]);

            // Convert to lead automatically
            $lead = $submission->convertToLead();
            
            if ($lead) {
                $lead->logActivity(
                    'created_from_form',
                    "Lead created from form submission: {$form->title}",
                    [],
                    $submission->form_data
                );
            }

            // Increment form submissions count
            $form->incrementSubmissions();

            return response()->json([
                'success' => true,
                'message' => $form->success_message,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error submitting form: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show public form
     */
    public function showPublicForm($slug)
    {
        try {
            $form = Form::where('slug', $slug)
                       ->where('is_active', true)
                       ->firstOrFail();

            return view('forms.public', compact('form'));

        } catch (\Exception $e) {
            abort(404, 'Form not found or inactive');
        }
    }

    /**
     * Search leads for autocomplete
     */
    public function searchLeads(Request $request)
    {
        $search = $request->get('q', '');
        
        $leads = Lead::where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('mobile', 'like', "%{$search}%");
                })
                ->active()
                ->limit(10)
                ->get(['id', 'first_name', 'last_name', 'email', 'mobile']);

        return response()->json([
            'success' => true,
            'leads' => $leads->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'text' => "{$lead->full_name} ({$lead->email})",
                    'name' => $lead->full_name,
                    'email' => $lead->email,
                    'mobile' => $lead->mobile,
                ];
            }),
        ]);
    }

    /**
     * Get assignable users
     */
    public function getAssignableUsers()
    {
        $users = User::where('id', '!=', auth()->id())
                    ->orderBy('name')
                    ->get(['id', 'name', 'email']);

        return response()->json([
            'success' => true,
            'users' => $users,
        ]);
    }

    /**
     * Export leads to CSV
     */
    public function exportLeads(Request $request)
    {
        $query = Lead::with(['followups', 'assignments']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        $leads = $query->orderBy('created_at', 'desc')->get();

        $filename = 'leads-export-' . now()->format('Y-m-d-H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($leads) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($file, [
                'ID', 'First Name', 'Last Name', 'Email', 'Mobile', 'WhatsApp',
                'Country', 'Source', 'Status', 'Interest', 'Notes', 'Created At',
                'Total Followups', 'Pending Followups', 'Assignments'
            ]);

            foreach ($leads as $lead) {
                fputcsv($file, [
                    $lead->id,
                    $lead->first_name,
                    $lead->last_name,
                    $lead->email,
                    $lead->mobile,
                    $lead->whatsapp,
                    $lead->country,
                    $lead->source,
                    $lead->status,
                    $lead->interest,
                    $lead->notes,
                    $lead->created_at->format('Y-m-d H:i:s'),
                    $lead->followups->count(),
                    $lead->followups->where('completed', false)->count(),
                    $lead->assignments->count(),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get lead sources for filters
     */
    public function getLeadSources()
    {
        $sources = Lead::select('source')
                      ->whereNotNull('source')
                      ->groupBy('source')
                      ->orderBy('source')
                      ->pluck('source');

        return response()->json([
            'success' => true,
            'sources' => $sources,
        ]);
    }

    /**
     * Get countries for filters
     */
    public function getCountries()
    {
        $countries = Lead::select('country')
                        ->whereNotNull('country')
                        ->groupBy('country')
                        ->orderBy('country')
                        ->pluck('country');

        return response()->json([
            'success' => true,
            'countries' => $countries,
        ]);
    }

    /**
     * Update lead status
     */
    public function updateLeadStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:hot,warm,cold,converted',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status',
            ], 422);
        }

        try {
            $lead = Lead::findOrFail($id);
            $oldStatus = $lead->status;
            $newStatus = $request->status;
            
            $lead->update(['status' => $newStatus]);

            // Log activity
            $lead->logActivity(
                'status_changed',
                "Status changed from {$oldStatus} to {$newStatus} by " . auth()->user()->name,
                ['status' => $oldStatus],
                ['status' => $newStatus]
            );

            return response()->json([
                'success' => true,
                'message' => 'Lead status updated successfully!',
                'status' => $newStatus,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating lead status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk operations
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'exists:leads,id',
            'status' => 'required|in:hot,warm,cold,converted',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $leadIds = $request->lead_ids;
            $status = $request->status;
            
            Lead::whereIn('id', $leadIds)->update(['status' => $status]);

            return response()->json([
                'success' => true,
                'message' => count($leadIds) . ' leads updated successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating leads: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get statistics for dashboard
     */
    public function getStats()
    {
        $stats = [
            'leads' => [
                'total' => Lead::count(),
                'active' => Lead::active()->count(),
                'hot' => Lead::hot()->count(),
                'converted' => Lead::converted()->count(),
            ],
            'followups' => [
                'total' => Followup::count(),
                'today' => Followup::dueToday()->pending()->count(),
                'overdue' => Followup::overdue()->count(),
                'completed' => Followup::completed()->count(),
            ],
            'assignments' => [
                'total' => Assignment::count(),
                'active' => Assignment::active()->count(),
                'by_me' => Assignment::where('assigned_by', auth()->id())->count(),
                'to_me' => Assignment::where('assigned_to', auth()->id())->count(),
            ],
            'forms' => [
                'total' => Form::count(),
                'active' => Form::active()->count(),
                'total_submissions' => FormSubmission::count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
}