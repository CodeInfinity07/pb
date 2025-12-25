<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Form;
use App\Models\Followup;
use App\Models\Assignment;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminCRMController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | CRM DASHBOARD METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Display CRM dashboard with overview statistics
     */
    public function index()
    {
        $user = Auth::user();

        // Get CRM statistics
        $crmStats = $this->getCrmStats();

        // Get recent leads
        $recentLeads = Lead::with([
            'createdBy',
            'followups' => function ($q) {
                $q->pending()->orderBy('followup_date', 'asc');
            }
        ])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get upcoming followups
        $upcomingFollowups = Followup::with(['lead', 'createdBy'])
            ->pending()
            ->where('followup_date', '>=', today())
            ->orderBy('followup_date', 'asc')
            ->limit(8)
            ->get();

        // Get overdue followups
        $overdueFollowups = Followup::with(['lead', 'createdBy'])
            ->overdue()
            ->orderBy('followup_date', 'asc')
            ->limit(5)
            ->get();

        // Get active forms
        $activeForms = Form::active()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.settings.crm.dashboard', compact(
            'user',
            'crmStats',
            'recentLeads',
            'upcomingFollowups',
            'overdueFollowups',
            'activeForms'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | LEADS MANAGEMENT METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Display leads listing with filters
     */
    public function leads(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $status = $request->get('status');
        $source = $request->get('source');
        $search = $request->get('search');

        // Build query
        $query = Lead::with([
            'createdBy',
            'followups' => function ($q) {
                $q->pending()->orderBy('followup_date', 'asc')->limit(1);
            },
            'assignments' => function ($q) {
                $q->active()->with('assignedTo');
            }
        ]);

        // Apply filters
        if ($status) {
            $query->where('status', $status);
        }

        if ($source) {
            $query->where('source', $source);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        // Get leads with pagination
        $leads = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get lead statistics
        $leadStats = $this->getLeadStats($request);

        // Get filter options
        $sources = Lead::distinct()->pluck('source')->filter();
        $countries = Lead::distinct()->pluck('country')->filter();

        return view('admin.settings.crm.leads.index', compact(
            'user',
            'leads',
            'leadStats',
            'sources',
            'countries'
        ));
    }

    /**
     * Store a new lead
     */
    public function storeLead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:leads,email',
            'mobile' => 'required|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'country' => 'required|string|max:255',
            'source' => 'required|string|max:255',
            'status' => 'required|in:hot,warm,cold,converted,lost',
            'interest' => 'required|in:High,Medium,Low',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        try {
            $lead = Lead::create(array_merge($request->all(), [
                'created_by' => Auth::id()
            ]));

            // Log activity
            $lead->logActivity('created', 'Lead created manually by admin', [], $lead->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Lead created successfully',
                'lead' => $lead
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create lead: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update lead status
     */
    public function updateLeadStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:hot,warm,cold,converted,lost',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        try {
            $lead = Lead::findOrFail($id);
            $oldStatus = $lead->status;

            $lead->update(['status' => $request->status]);

            // Log activity
            $lead->logActivity(
                'status_updated',
                "Status changed from {$oldStatus} to {$request->status}",
                ['status' => $oldStatus],
                ['status' => $request->status]
            );

            return response()->json([
                'success' => true,
                'message' => 'Lead status updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update lead status: ' . $e->getMessage()
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FORMS MANAGEMENT METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Display forms management page
     */
    public function forms(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $status = $request->get('status');
        $search = $request->get('search');

        // Build query
        $query = Form::with([
            'createdBy',
            'submissions' => function ($q) {
                $q->orderBy('created_at', 'desc')->limit(3);
            }
        ]);

        // Apply filters
        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'inactive') {
            $query->inactive();
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Get forms with pagination
        $forms = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get form statistics
        $formStats = $this->getFormStats($request);

        return view('admin.settings.crm.forms.index', compact(
            'user',
            'forms',
            'formStats'
        ));
    }

    /**
     * Store a new form
     */
    public function storeForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'submit_button_text' => 'required|string|max:100',
            'success_message' => 'required|string|max:500',
            'standard_fields' => 'required|array',
            'custom_fields' => 'nullable|array',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        try {
            $form = Form::create(array_merge($request->all(), [
                'created_by' => Auth::id(),
                'is_active' => $request->boolean('is_active', true)
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Form created successfully',
                'form' => $form
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create form: ' . $e->getMessage()
            ]);
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
                'message' => 'Form status updated successfully',
                'status' => $form->is_active
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update form status: ' . $e->getMessage()
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FOLLOWUPS MANAGEMENT METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Display followups page
     */
    public function followups(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $status = $request->get('status');
        $type = $request->get('type');
        $date = $request->get('date');
        $search = $request->get('search');

        // Build query
        $query = Followup::with(['lead', 'createdBy']);

        // Apply filters
        if ($status === 'pending') {
            $query->pending();
        } elseif ($status === 'completed') {
            $query->completed();
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($date === 'today') {
            $query->dueToday();
        } elseif ($date === 'overdue') {
            $query->overdue();
        } elseif ($date === 'upcoming') {
            $query->upcoming();
        }

        if ($search) {
            $query->whereHas('lead', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Get followups with pagination
        $followups = $query->orderBy('followup_date', 'asc')->paginate(20);

        // Get followup statistics
        $followupStats = $this->getFollowupStats($request);

        return view('admin.settings.crm.followups.index', compact(
            'user',
            'followups',
            'followupStats'
        ));
    }

    /**
     * Store a new followup
     */
    public function storeFollowup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|exists:leads,id',
            'followup_date' => 'required|date',
            'type' => 'required|in:call,email,meeting,whatsapp,other',
            'notes' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        try {
            $followup = Followup::create(array_merge($request->all(), [
                'created_by' => Auth::id()
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Followup scheduled successfully',
                'followup' => $followup->load('lead')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create followup: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Complete a followup
     */
    public function completeFollowup($id)
    {
        try {
            $followup = Followup::findOrFail($id);
            $followup->markAsCompleted();

            return response()->json([
                'success' => true,
                'message' => 'Followup marked as completed'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete followup: ' . $e->getMessage()
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ASSIGNMENTS MANAGEMENT METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Display assignments page
     */
    public function assignments(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $status = $request->get('status');
        $assigned_to = $request->get('assigned_to');
        $search = $request->get('search');

        // Build query
        $query = Assignment::with(['lead', 'assignedBy', 'assignedTo']);

        // Apply filters
        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'completed') {
            $query->completed();
        }

        if ($assigned_to) {
            $query->assignedTo($assigned_to);
        }

        if ($search) {
            $query->whereHas('lead', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Get assignments with pagination
        $assignments = $query->orderBy('assigned_at', 'desc')->paginate(20);

        // Get assignment statistics
        $assignmentStats = $this->getAssignmentStats($request);

        // Get assignable users
        $assignableUsers = User::where('status', 'active')
            ->select('id', 'first_name', 'last_name', 'email', 'role')
            ->orderBy('first_name')
            ->get();

        return view('admin.settings.crm.assignments.index', compact(
            'user',
            'assignments',
            'assignmentStats',
            'assignableUsers'
        ));
    }

    /**
     * Assign lead to user
     */
    public function assignLead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|exists:leads,id',
            'assigned_to' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        try {
            $lead = Lead::findOrFail($request->lead_id);

            $assignment = $lead->assignTo(
                User::findOrFail($request->assigned_to),
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Lead assigned successfully',
                'assignment' => $assignment->load(['lead', 'assignedTo'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign lead: ' . $e->getMessage()
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get CRM statistics
     */
    private function getCrmStats(): array
    {
        return [
            'total_leads' => Lead::count(),
            'hot_leads' => Lead::where('status', 'hot')->count(),
            'converted_leads' => Lead::where('status', 'converted')->count(),
            'pending_followups' => Followup::pending()->count(),
            'overdue_followups' => Followup::overdue()->count(),
            'active_forms' => Form::active()->count(),
            'form_submissions_today' => FormSubmission::whereDate('created_at', today())->count(),
            'active_assignments' => Assignment::active()->count(),
            'new_leads_today' => Lead::whereDate('created_at', today())->count(),
            'conversion_rate' => $this->getConversionRate(),
        ];
    }

    /**
     * Get lead statistics
     */
    private function getLeadStats($request): array
    {
        $baseQuery = Lead::query();

        // Apply same filters as main query for consistent stats
        if ($request->get('status')) {
            $baseQuery->where('status', $request->get('status'));
        }
        if ($request->get('source')) {
            $baseQuery->where('source', $request->get('source'));
        }

        return [
            'total' => (clone $baseQuery)->count(),
            'hot' => (clone $baseQuery)->where('status', 'hot')->count(),
            'warm' => (clone $baseQuery)->where('status', 'warm')->count(),
            'cold' => (clone $baseQuery)->where('status', 'cold')->count(),
            'converted' => (clone $baseQuery)->where('status', 'converted')->count(),
            'lost' => (clone $baseQuery)->where('status', 'lost')->count(),
            'today' => (clone $baseQuery)->whereDate('created_at', today())->count(),
        ];
    }

    /**
     * Get form statistics
     */
    private function getFormStats($request): array
    {
        return [
            'total' => Form::count(),
            'active' => Form::active()->count(),
            'inactive' => Form::inactive()->count(),
            'total_submissions' => FormSubmission::count(),
            'submissions_today' => FormSubmission::whereDate('created_at', today())->count(),
        ];
    }

    /**
     * Get followup statistics
     */
    private function getFollowupStats($request): array
    {
        return [
            'total' => Followup::count(),
            'pending' => Followup::pending()->count(),
            'completed' => Followup::completed()->count(),
            'due_today' => Followup::dueToday()->count(),
            'overdue' => Followup::overdue()->count(),
            'upcoming' => Followup::upcoming()->count(),
        ];
    }

    /**
     * Get assignment statistics
     */
    private function getAssignmentStats($request): array
    {
        return [
            'total' => Assignment::count(),
            'active' => Assignment::active()->count(),
            'completed' => Assignment::completed()->count(),
        ];
    }

    /**
     * Calculate conversion rate
     */
    private function getConversionRate(): float
    {
        $totalLeads = Lead::count();
        $convertedLeads = Lead::where('status', 'converted')->count();

        return $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0;
    }

    // Add these additional methods to your AdminCRMController class

    /**
     * Show lead details
     */
    public function showLead($id)
    {
        try {
            $lead = Lead::with([
                'followups' => function ($q) {
                    $q->orderBy('followup_date', 'desc');
                },
                'assignments' => function ($q) {
                    $q->with(['assignedTo', 'assignedBy'])->orderBy('assigned_at', 'desc');
                },
                'activities' => function ($q) {
                    $q->orderBy('created_at', 'desc')->limit(10);
                },
                'createdBy'
            ])->findOrFail($id);

            $html = view('admin.crm.leads.show', compact('lead'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load lead details: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update lead information
     */
    public function updateLead(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:leads,email,' . $id,
            'mobile' => 'required|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'country' => 'required|string|max:255',
            'source' => 'required|string|max:255',
            'status' => 'required|in:hot,warm,cold,converted,lost',
            'interest' => 'required|in:High,Medium,Low',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        try {
            $lead = Lead::findOrFail($id);
            $oldData = $lead->toArray();

            $lead->update($request->all());

            // Log activity
            $lead->logActivity('updated', 'Lead information updated', $oldData, $lead->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Lead updated successfully',
                'lead' => $lead
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update lead: ' . $e->getMessage()
            ]);
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

            // Delete related records
            $lead->followups()->delete();
            $lead->assignments()->delete();
            $lead->activities()->delete();
            $lead->formSubmissions()->delete();

            $lead->delete();

            return response()->json([
                'success' => true,
                'message' => "Lead {$leadName} deleted successfully"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete lead: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Search leads for utilities
     */
    public function searchLeads(Request $request)
    {
        $search = $request->get('search');

        if (strlen($search) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Search term must be at least 2 characters'
            ]);
        }

        try {
            $leads = Lead::where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            })
                ->select('id', 'first_name', 'last_name', 'email', 'mobile', 'status', 'source')
                ->limit(10)
                ->get();

            // Add computed attributes
            $leads->each(function ($lead) {
                $lead->full_name = $lead->first_name . ' ' . $lead->last_name;
            });

            return response()->json([
                'success' => true,
                'leads' => $leads
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search leads: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show form details
     */
    public function showForm($id)
    {
        try {
            $form = Form::with([
                'createdBy',
                'submissions' => function ($q) {
                    $q->orderBy('created_at', 'desc')->limit(20);
                }
            ])->findOrFail($id);

            $html = view('admin.crm.forms.show', compact('form'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load form details: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update form
     */
    public function updateForm(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'submit_button_text' => 'required|string|max:100',
            'success_message' => 'required|string|max:500',
            'standard_fields' => 'required|array',
            'custom_fields' => 'nullable|array',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        try {
            $form = Form::findOrFail($id);

            $form->update(array_merge($request->all(), [
                'is_active' => $request->boolean('is_active', false)
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Form updated successfully',
                'form' => $form
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update form: ' . $e->getMessage()
            ]);
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

            // Delete related submissions
            $form->submissions()->delete();

            $form->delete();

            return response()->json([
                'success' => true,
                'message' => "Form '{$formTitle}' deleted successfully"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete form: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get form submissions
     */
    public function formSubmissions($id)
    {
        try {
            $form = Form::findOrFail($id);
            $submissions = $form->submissions()
                ->with('lead')
                ->orderBy('created_at', 'desc')
                ->get();

            $html = view('admin.crm.forms.submissions', compact('form', 'submissions'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load submissions: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update followup
     */
    public function updateFollowup(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'followup_date' => 'required|date',
            'type' => 'required|in:call,email,meeting,whatsapp,other',
            'notes' => 'required|string|max:1000',
            'reschedule_reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        try {
            $followup = Followup::findOrFail($id);
            $oldDate = $followup->followup_date;

            $followup->update($request->only(['followup_date', 'type', 'notes']));

            // Log activity if date changed
            if ($request->followup_date !== $oldDate->format('Y-m-d')) {
                $followup->lead->logActivity(
                    'followup_rescheduled',
                    "Followup rescheduled from {$oldDate->format('M d, Y')} to {$followup->followup_date->format('M d, Y')}",
                    ['followup_date' => $oldDate],
                    ['followup_date' => $followup->followup_date]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Followup updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update followup: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete followup
     */
    public function deleteFollowup($id)
    {
        try {
            $followup = Followup::with('lead')->findOrFail($id);
            $leadName = $followup->lead->full_name;

            $followup->delete();

            return response()->json([
                'success' => true,
                'message' => "Followup for {$leadName} deleted successfully"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete followup: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Complete assignment
     */
    public function completeAssignment($id)
    {
        try {
            $assignment = Assignment::with('lead')->findOrFail($id);

            $assignment->markAsCompleted();

            // Log activity
            $assignment->lead->logActivity(
                'assignment_completed',
                "Assignment completed by {$assignment->assignedTo->full_name}",
                ['status' => 'active'],
                ['status' => 'completed']
            );

            return response()->json([
                'success' => true,
                'message' => 'Assignment marked as completed'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete assignment: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Reassign lead
     */
    public function reassignLead(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|exists:users,id',
            'reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        try {
            $assignment = Assignment::with(['lead', 'assignedTo'])->findOrFail($id);
            $oldAssignee = $assignment->assignedTo->full_name;
            $newAssignee = User::findOrFail($request->assigned_to);

            $assignment->update([
                'assigned_to' => $request->assigned_to,
                'notes' => $request->reason ? "Reassigned: {$request->reason}" : "Reassigned lead"
            ]);

            // Log activity
            $assignment->lead->logActivity(
                'assignment_reassigned',
                "Lead reassigned from {$oldAssignee} to {$newAssignee->full_name}",
                ['assigned_to' => $assignment->assignedTo->id],
                ['assigned_to' => $newAssignee->id]
            );

            return response()->json([
                'success' => true,
                'message' => "Lead reassigned to {$newAssignee->full_name} successfully"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reassign lead: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete assignment
     */
    public function deleteAssignment($id)
    {
        try {
            $assignment = Assignment::with('lead')->findOrFail($id);
            $leadName = $assignment->lead->full_name;

            $assignment->delete();

            return response()->json([
                'success' => true,
                'message' => "Assignment for {$leadName} deleted successfully"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete assignment: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get assignable users
     */
    public function getAssignableUsers()
    {
        try {
            $users = User::whereIn('role', ['admin', 'support', 'moderator'])
                ->where('status', 'active')
                ->select('id', 'first_name', 'last_name', 'email', 'role')
                ->orderBy('first_name')
                ->get();

            return response()->json([
                'success' => true,
                'users' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get assignable users: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get lead sources for filters
     */
    public function getLeadSources()
    {
        try {
            $sources = Lead::distinct()->pluck('source')->filter();

            return response()->json([
                'success' => true,
                'sources' => $sources
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get lead sources: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get countries for filters
     */
    public function getCountries()
    {
        try {
            $countries = Lead::distinct()->pluck('country')->filter();

            return response()->json([
                'success' => true,
                'countries' => $countries
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get countries: ' . $e->getMessage()
            ]);
        }
    }
}