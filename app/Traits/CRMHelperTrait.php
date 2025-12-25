<?php

namespace App\Traits;

use App\Models\Lead;
use App\Models\Followup;
use App\Models\Assignment;
use App\Models\Form;
use Carbon\Carbon;

trait CRMHelperTrait
{
    /**
     * Get status color class for display
     */
    protected function getStatusColor($status)
    {
        $colors = [
            'hot' => 'danger',
            'warm' => 'warning', 
            'cold' => 'info',
            'converted' => 'success'
        ];

        return $colors[$status] ?? 'secondary';
    }

    /**
     * Get followup type icon
     */
    protected function getFollowupTypeIcon($type)
    {
        $icons = [
            'call' => '📞',
            'email' => '📧',
            'meeting' => '🤝',
            'whatsapp' => '💬',
            'other' => '📝'
        ];

        return $icons[$type] ?? '📝';
    }

    /**
     * Format date for display
     */
    protected function formatDate($date, $format = 'M d, Y')
    {
        return $date ? Carbon::parse($date)->format($format) : null;
    }

    /**
     * Generate dashboard statistics
     */
    protected function getDashboardStats()
    {
        return [
            'activeLeads' => Lead::active()->count(),
            'convertedLeads' => Lead::converted()->count(),
            'todayFollowups' => Followup::dueToday()->pending()->count(),
            'overdueFollowups' => Followup::overdue()->count(),
            'hotLeads' => Lead::hot()->count(),
            'warmLeads' => Lead::where('status', 'warm')->count(),
            'coldLeads' => Lead::where('status', 'cold')->count(),
            'totalAssignments' => Assignment::active()->count(),
            'myAssignments' => Assignment::where('assigned_to', auth()->id())->active()->count(),
            'activeForms' => Form::active()->count(),
        ];
    }

    /**
     * Get common filter options
     */
    protected function getFilterOptions()
    {
        return [
            'statuses' => ['hot', 'warm', 'cold', 'converted'],
            'sources' => Lead::select('source')
                           ->whereNotNull('source')
                           ->groupBy('source')
                           ->orderBy('source')
                           ->pluck('source'),
            'countries' => Lead::select('country')
                             ->whereNotNull('country')
                             ->groupBy('country')
                             ->orderBy('country')
                             ->pluck('country'),
            'interests' => ['Low', 'Medium', 'High'],
        ];
    }

    /**
     * Format lead data for frontend
     */
    protected function formatLeadForFrontend($lead)
    {
        return [
            'id' => $lead->id,
            'firstName' => $lead->first_name,
            'lastName' => $lead->last_name,
            'fullName' => $lead->full_name,
            'email' => $lead->email,
            'mobile' => $lead->mobile,
            'whatsapp' => $lead->whatsapp,
            'country' => $lead->country,
            'source' => $lead->source,
            'status' => $lead->status,
            'statusColor' => $this->getStatusColor($lead->status),
            'interest' => $lead->interest,
            'notes' => $lead->notes,
            'createdAt' => $this->formatDate($lead->created_at),
            'createdBy' => $lead->createdBy ? $lead->createdBy->name : null,
            'followupsCount' => $lead->followups ? $lead->followups->count() : 0,
            'pendingFollowups' => $lead->followups ? $lead->followups->where('completed', false)->count() : 0,
            'lastFollowup' => $lead->followups ? $lead->followups->first() : null,
        ];
    }

    /**
     * Format followup data for frontend
     */
    protected function formatFollowupForFrontend($followup)
    {
        return [
            'id' => $followup->id,
            'leadId' => $followup->lead_id,
            'leadName' => $followup->lead ? $followup->lead->full_name : null,
            'followupDate' => $this->formatDate($followup->followup_date),
            'type' => $followup->type,
            'typeIcon' => $this->getFollowupTypeIcon($followup->type),
            'notes' => $followup->notes,
            'completed' => $followup->completed,
            'completedAt' => $this->formatDate($followup->completed_at),
            'createdBy' => $followup->createdBy ? $followup->createdBy->name : null,
            'isPastDue' => !$followup->completed && $followup->followup_date < now(),
            'isDueToday' => !$followup->completed && $followup->followup_date->isToday(),
        ];
    }

    /**
     * Send JSON response with standardized format
     */
    protected function jsonResponse($success = true, $message = '', $data = [], $status = 200)
    {
        $response = [
            'success' => $success,
            'message' => $message,
        ];

        if (!empty($data)) {
            $response = array_merge($response, $data);
        }

        return response()->json($response, $status);
    }

    /**
     * Handle exceptions and return appropriate response
     */
    protected function handleException(\Exception $e, $message = 'An error occurred')
    {
        \Log::error($message . ': ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);

        return $this->jsonResponse(
            false,
            $message . ': ' . $e->getMessage(),
            [],
            500
        );
    }

    /**
     * Validate pagination parameters
     */
    protected function validatePagination($request)
    {
        $page = max(1, (int) $request->get('page', 1));
        $perPage = max(5, min(100, (int) $request->get('per_page', 10))); // Between 5-100

        return compact('page', 'perPage');
    }

    /**
     * Get leads query with common filters applied
     */
    protected function getLeadsQuery($request)
    {
        $query = Lead::with(['followups', 'createdBy', 'assignments']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Source filter
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        // Interest filter
        if ($request->filled('interest')) {
            $query->where('interest', $request->interest);
        }

        // Country filter
        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return $query;
    }

    /**
     * Log user activity
     */
    protected function logUserActivity($action, $description, $model = null)
    {
        try {
            \Log::info("CRM Activity: {$action}", [
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'description' => $description,
                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model ? $model->id : null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Fail silently for logging errors
        }
    }
}