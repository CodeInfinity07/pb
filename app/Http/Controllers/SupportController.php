<?php
// app/Http/Controllers/SupportController.php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class SupportController extends Controller
{
    /**
     * Display user's support tickets
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        
        $query = SupportTicket::where('user_id', $user->id)
            ->orderBy('updated_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $tickets = $query->paginate(10)->withQueryString();

        // Get user's ticket statistics
        $stats = [
            'total_tickets' => SupportTicket::where('user_id', $user->id)->count(),
            'open_tickets' => SupportTicket::where('user_id', $user->id)
                ->whereIn('status', ['open', 'in_progress'])->count(),
            'resolved_tickets' => SupportTicket::where('user_id', $user->id)
                ->where('status', 'resolved')->count(),
            'closed_tickets' => SupportTicket::where('user_id', $user->id)
                ->where('status', 'closed')->count(),
        ];

        return view('support.index', compact('tickets', 'stats', 'user'));
    }

    /**
     * Show create ticket form
     */
    public function create(): View
    {
        $user = \Auth::user();
        return view('support.create', compact('user'));
    }

    /**
     * Store new support ticket
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255|min:5',
            'description' => 'required|string|min:20',
            'priority' => 'required|in:low,medium,high,urgent',
            'category' => 'required|string|in:technical,billing,account,feature,bug,general,other',
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

            // Create the ticket
            $ticket = SupportTicket::create([
                'user_id' => auth()->id(),
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'priority' => $validated['priority'],
                'category' => $validated['category'],
                'attachments' => empty($attachments) ? null : $attachments,
            ]);

            // Update last reply info for the initial ticket
            $ticket->updateLastReply(auth()->user());

            DB::commit();

            Log::info('Support ticket created', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'user_id' => auth()->id(),
                'subject' => $validated['subject'],
                'priority' => $validated['priority']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Support ticket created successfully',
                'ticket' => $ticket,
                'redirect_url' => route('support.show', $ticket)
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create support ticket', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'data' => $validated
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create ticket: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show specific ticket
     */
    public function show(SupportTicket $ticket): View
    {

        $user = \Auth::user();

        // Ensure user can only view their own tickets
        if ($ticket->user_id !== auth()->id()) {
            abort(403, 'You can only view your own tickets.');
        }

        $ticket->load([
            'user', 
            'assignedTo', 
            'replies' => function($query) {
                // Only load public replies for regular users
                $query->where('is_internal_note', false)->with('user');
            },
            'lastReplyBy'
        ]);

        return view('support.show', compact('ticket', 'user'));
    }

    /**
     * Store reply to ticket
     */
    public function storeReply(Request $request, SupportTicket $ticket): JsonResponse
    {
        // Ensure user can only reply to their own tickets
        if ($ticket->user_id !== auth()->id()) {
            abort(403, 'You can only reply to your own tickets.');
        }

        // Check if ticket is closed
        if ($ticket->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reply to a closed ticket. Please create a new ticket if you need further assistance.'
            ], 400);
        }

        $validated = $request->validate([
            'message' => 'required|string|min:10',
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

            // Create reply
            $reply = SupportTicketReply::create([
                'ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'message' => $validated['message'],
                'is_internal_note' => false, // User replies are never internal
                'attachments' => empty($attachments) ? null : $attachments,
            ]);

            // If ticket was resolved, reopen it
            if ($ticket->status === 'resolved') {
                $ticket->update(['status' => 'open']);
            }

            DB::commit();

            Log::info('Support ticket reply added by user', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'user_id' => auth()->id()
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
     * Close ticket (user requested)
     */
    public function close(SupportTicket $ticket): JsonResponse
    {
        // Ensure user can only close their own tickets
        if ($ticket->user_id !== auth()->id()) {
            abort(403, 'You can only close your own tickets.');
        }

        if ($ticket->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'Ticket is already closed'
            ], 400);
        }

        try {
            $ticket->update(['status' => 'closed']);

            Log::info('Support ticket closed by user', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ticket closed successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to close support ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to close ticket'
            ], 500);
        }
    }

    /**
     * Reopen ticket
     */
    public function reopen(SupportTicket $ticket): JsonResponse
    {
        // Ensure user can only reopen their own tickets
        if ($ticket->user_id !== auth()->id()) {
            abort(403, 'You can only reopen your own tickets.');
        }

        if (!in_array($ticket->status, ['resolved', 'closed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only resolved or closed tickets can be reopened'
            ], 400);
        }

        try {
            $ticket->update(['status' => 'open']);

            Log::info('Support ticket reopened by user', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ticket reopened successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to reopen support ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reopen ticket'
            ], 500);
        }
    }

    /**
     * Get user's ticket statistics
     */
    public function getStats(): JsonResponse
    {
        $user = auth()->user();

        try {
            $stats = [
                'total_tickets' => SupportTicket::where('user_id', $user->id)->count(),
                'open_tickets' => SupportTicket::where('user_id', $user->id)
                    ->whereIn('status', ['open', 'in_progress'])->count(),
                'pending_tickets' => SupportTicket::where('user_id', $user->id)
                    ->where('status', 'pending_user')->count(),
                'resolved_tickets' => SupportTicket::where('user_id', $user->id)
                    ->where('status', 'resolved')->count(),
                'closed_tickets' => SupportTicket::where('user_id', $user->id)
                    ->where('status', 'closed')->count(),
                'avg_resolution_time' => $this->getAverageResolutionTime($user->id),
                'tickets_by_category' => SupportTicket::where('user_id', $user->id)
                    ->selectRaw('category, COUNT(*) as count')
                    ->groupBy('category')
                    ->pluck('count', 'category')
                    ->toArray(),
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
     * Download attachment
     */
    public function downloadAttachment(SupportTicket $ticket, $replyId, $attachmentIndex): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        // Ensure user can only download attachments from their own tickets
        if ($ticket->user_id !== auth()->id()) {
            abort(403, 'You can only access your own ticket attachments.');
        }

        // Get the reply
        $reply = SupportTicketReply::where('ticket_id', $ticket->id)
            ->where('id', $replyId)
            ->where('is_internal_note', false) // Users can't see internal notes
            ->first();

        if (!$reply || !$reply->attachments || !isset($reply->attachments[$attachmentIndex])) {
            abort(404, 'Attachment not found.');
        }

        $attachment = $reply->attachments[$attachmentIndex];
        $filePath = storage_path('app/public/' . $attachment['path']);

        if (!file_exists($filePath)) {
            abort(404, 'File not found.');
        }

        return response()->download($filePath, $attachment['name']);
    }

    /**
     * Private helper methods
     */
    private function getAverageResolutionTime(int $userId): float
    {
        $resolvedTickets = SupportTicket::where('user_id', $userId)
            ->whereIn('status', ['resolved', 'closed'])
            ->get();

        if ($resolvedTickets->isEmpty()) {
            return 0;
        }

        $totalHours = $resolvedTickets->sum(function($ticket) {
            return $ticket->created_at->diffInHours($ticket->updated_at);
        });

        return round($totalHours / $resolvedTickets->count(), 1);
    }
}