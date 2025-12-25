<?php
// app/Http/Controllers/Admin/AdminSupportController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class AdminSupportController extends Controller
{
    /**
     * Display support ticket dashboard
     */
    public function index(): View
    {
        $this->checkAccess();
        $user = auth()->user();

        $stats = [
            'total_tickets' => SupportTicket::count(),
            'open_tickets' => SupportTicket::whereIn('status', ['open', 'in_progress'])->count(),
            'pending_tickets' => SupportTicket::where('status', 'pending_user')->count(),
            'resolved_today' => SupportTicket::where('status', 'resolved')
                ->whereDate('updated_at', today())->count(),
            'unassigned_tickets' => SupportTicket::whereNull('assigned_to')
                ->whereIn('status', ['open', 'in_progress'])->count(),
            'urgent_tickets' => SupportTicket::where('priority', 'urgent')
                ->whereIn('status', ['open', 'in_progress'])->count(),
            'overdue_tickets' => SupportTicket::whereIn('status', ['open', 'in_progress'])
                ->get()->filter(fn($ticket) => $ticket->is_overdue)->count(),
            'my_assigned_tickets' => SupportTicket::where('assigned_to', $user->id)
                ->whereIn('status', ['open', 'in_progress'])->count(),
        ];

        // Recent tickets for quick overview
        $recentTickets = SupportTicket::with(['user', 'assignedTo'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.support.index', compact('stats', 'recentTickets', 'user'));
    }

    /**
     * Display all tickets with filtering
     */
    public function tickets(Request $request): View
    {
        $this->checkAccess();

        $query = SupportTicket::with(['user', 'assignedTo', 'lastReplyBy'])
            ->orderBy('updated_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('assigned_to')) {
            if ($request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }

        if ($request->filled('user_search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->user_search . '%')
                    ->orWhere('email', 'like', '%' . $request->user_search . '%');
            });
        }

        if ($request->filled('ticket_number')) {
            $query->where('ticket_number', 'like', '%' . $request->ticket_number . '%');
        }

        $tickets = $query->paginate(20)->withQueryString();

        // Get filter options - FIXED: Use your existing role system
        $assignableUsers = $this->getAssignableUsers();

        return view('admin.support.tickets', compact(
            'tickets',
            'assignableUsers'
        ));
    }

    /**
     * Show specific ticket details
     */
    public function show(SupportTicket $ticket): View
    {
        $this->checkAccess();

        $ticket->load([
            'user',
            'assignedTo',
            'replies.user',
            'lastReplyBy'
        ]);

        // Get assignable users for assignment dropdown
        $assignableUsers = $this->getAssignableUsers();

        return view('admin.support.show', compact('ticket', 'assignableUsers'));
    }

    public function storeReply(Request $request, SupportTicket $ticket): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'message' => 'required|string|min:10',
            'is_internal_note' => 'sometimes|in:0,1,true,false',
            'change_status' => 'nullable|in:open,in_progress,pending_user,resolved,closed',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,txt'
        ]);

        try {
            DB::beginTransaction();

            // Handle file attachments
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('support-attachments', 'public');
                    $attachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType(),
                    ];
                }
            }

            // Handle boolean conversion for is_internal_note
            $isInternalNote = $this->convertToBoolean($request->input('is_internal_note', false));

            // Create reply
            $reply = SupportTicketReply::create([
                'ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'message' => $validated['message'],
                'is_internal_note' => $isInternalNote,
                'attachments' => empty($attachments) ? null : $attachments,
            ]);

            // Update ticket status if requested
            if (!empty($validated['change_status'])) {
                $ticket->update(['status' => $validated['change_status']]);
            }

            // If not an internal note and ticket isn't assigned to current user, assign it
            if (!$isInternalNote && $ticket->assigned_to !== auth()->id()) {
                $ticket->assignTo(auth()->user());
            }

            // 🔔 SEND NOTIFICATION - Admin replied to user's ticket
            if (!$isInternalNote) {
                $ticket->user->notify(
                    \App\Notifications\UnifiedNotification::supportTicketReply(
                        $ticket->ticket_number,
                        $ticket->subject,
                        route('support.show', $ticket->id)
                    )
                );

                Log::info('Support ticket reply notification sent to user', [
                    'ticket_id' => $ticket->id,
                    'user_id' => $ticket->user_id,
                    'replied_by' => auth()->id()
                ]);
            }

            DB::commit();

            Log::info('Support ticket reply added', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'user_id' => auth()->id(),
                'is_internal' => $isInternalNote
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reply added successfully',
                'reply' => $reply->load('user')
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to add support ticket reply', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add reply: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update ticket status
     */
    public function updateStatus(Request $request, SupportTicket $ticket): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,pending_user,resolved,closed'
        ]);

        try {
            $oldStatus = $ticket->status;
            $ticket->update(['status' => $validated['status']]);

            Log::info('Support ticket status updated', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ticket status updated successfully',
                'status' => $validated['status']
            ]);

        } catch (Exception $e) {
            Log::error('Failed to update support ticket status', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }

    /**
     * Update ticket priority
     */
    public function updatePriority(Request $request, SupportTicket $ticket): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'priority' => 'required|in:low,medium,high,urgent'
        ]);

        try {
            $oldPriority = $ticket->priority;
            $ticket->update(['priority' => $validated['priority']]);

            Log::info('Support ticket priority updated', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'old_priority' => $oldPriority,
                'new_priority' => $validated['priority'],
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ticket priority updated successfully',
                'priority' => $validated['priority']
            ]);

        } catch (Exception $e) {
            Log::error('Failed to update support ticket priority', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update priority'
            ], 500);
        }
    }

    /**
     * Assign ticket to user
     */
    public function assign(Request $request, SupportTicket $ticket): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id'
        ]);

        try {
            $oldAssignee = $ticket->assigned_to;

            if ($validated['assigned_to']) {
                $assignee = User::findOrFail($validated['assigned_to']);
                // Verify the assignee has staff privileges
                if (!$assignee->hasStaffPrivileges()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User does not have staff privileges'
                    ], 400);
                }
                $ticket->assignTo($assignee);
                $message = "Ticket assigned to {$assignee->name} successfully";
            } else {
                $ticket->unassign();
                $message = "Ticket unassigned successfully";
            }

            Log::info('Support ticket assignment updated', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'old_assignee' => $oldAssignee,
                'new_assignee' => $validated['assigned_to'],
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'assigned_to' => $validated['assigned_to']
            ]);

        } catch (Exception $e) {
            Log::error('Failed to assign support ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign ticket'
            ], 500);
        }
    }

    /**
     * Download attachment from ticket or reply
     */
    public function downloadAttachment(SupportTicket $ticket, $source, $attachmentIndex)
    {
        $this->checkAccess();

        $attachments = null;

        if ($source === 'original') {
            $attachments = $ticket->attachments;
        } else {
            $reply = SupportTicketReply::where('ticket_id', $ticket->id)
                ->where('id', $source)
                ->first();

            if (!$reply) {
                abort(404, 'Reply not found.');
            }

            $attachments = $reply->attachments;
        }

        if (!$attachments || !isset($attachments[$attachmentIndex])) {
            abort(404, 'Attachment not found.');
        }

        $attachment = $attachments[$attachmentIndex];
        $filePath = storage_path('app/public/' . $attachment['path']);

        if (!file_exists($filePath)) {
            abort(404, 'File not found.');
        }

        return response()->download($filePath, $attachment['name']);
    }

    /**
     * Get dashboard statistics
     */
    public function getStatistics(): JsonResponse
    {
        $this->checkAccess();

        try {
            $stats = [
                'total_tickets' => SupportTicket::count(),
                'open_tickets' => SupportTicket::whereIn('status', ['open', 'in_progress'])->count(),
                'pending_tickets' => SupportTicket::where('status', 'pending_user')->count(),
                'resolved_today' => SupportTicket::where('status', 'resolved')
                    ->whereDate('updated_at', today())->count(),
                'unassigned_tickets' => SupportTicket::whereNull('assigned_to')
                    ->whereIn('status', ['open', 'in_progress'])->count(),
                'urgent_tickets' => SupportTicket::where('priority', 'urgent')
                    ->whereIn('status', ['open', 'in_progress'])->count(),
                'avg_response_time' => $this->calculateAverageResponseTime(),
                'tickets_by_category' => $this->getTicketsByCategory(),
                'tickets_by_status' => $this->getTicketsByStatus(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics'
            ], 500);
        }
    }

    /**
     * Search users for assignment
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $this->checkAccess();

        $query = $request->get('q', '');

        $users = $this->getAssignableUsers()
            ->filter(function ($user) use ($query) {
                return stripos($user->name, $query) !== false ||
                    stripos($user->email, $query) !== false;
            })
            ->take(10)
            ->values();

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }

    /**
     * Get users who can be assigned tickets
     */
    private function getAssignableUsers()
    {
        // Get all users who have staff privileges
        // This adapts to your existing role system
        return User::all()->filter(function ($user) {
            return $user->hasStaffPrivileges();
        });
    }

    /**
     * Convert various boolean representations to actual boolean
     */
    private function convertToBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'on', 'yes']);
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return false;
    }

    /**
     * Private helper methods
     */
    private function calculateAverageResponseTime(): float
    {
        // This is a simplified calculation
        // You might want to implement more sophisticated logic
        $tickets = SupportTicket::whereNotNull('last_reply_at')
            ->whereHas('replies', function ($q) {
                $q->whereHas('user', function ($q) {
                    // Filter by users who have staff privileges
                    $staffUserIds = User::all()->filter(function ($user) {
                        return $user->hasStaffPrivileges();
                    })->pluck('id');

                    $q->whereIn('id', $staffUserIds);
                });
            })
            ->get();

        if ($tickets->isEmpty()) {
            return 0;
        }

        $totalHours = $tickets->sum(function ($ticket) {
            return $ticket->created_at->diffInHours($ticket->last_reply_at);
        });

        return round($totalHours / $tickets->count(), 1);
    }

    private function getTicketsByCategory(): array
    {
        return SupportTicket::selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();
    }

    private function getTicketsByStatus(): array
    {
        return SupportTicket::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Check admin access
     */
    private function checkAccess(): void
    {
        if (!auth()->user()->hasStaffPrivileges()) {
            abort(403, 'Access denied. Staff privileges required.');
        }
    }
}