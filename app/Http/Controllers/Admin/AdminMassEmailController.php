<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MassEmailCampaign;
use App\Services\DynamicMailConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Exception;

class AdminMassEmailController extends Controller
{
    /**
     * Display mass email dashboard.
     */
    public function index(): View
    {
        $this->checkAdminAccess();
        
        $user = Auth::user();
        
        // Get email statistics
        $stats = $this->getEmailStatistics();
        
        // Get recent campaigns
        $recentCampaigns = MassEmailCampaign::with('user')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.emails.mass.index', compact('user', 'stats', 'recentCampaigns'));
    }

    /**
     * Get recipient count for selected groups.
     */
    public function getRecipientCount(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'recipient_groups' => 'required|array',
            'recipient_groups.*' => 'string|in:all,active,inactive,blocked,kyc_verified,email_verified,specific_users',
            'specific_users' => 'nullable|array',
            'specific_users.*' => 'exists:users,id'
        ]);

        try {
            $query = $this->buildRecipientQuery($validated['recipient_groups'], $validated['specific_users'] ?? []);
            $count = $query->count();
            
            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => "Found {$count} recipients"
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to count recipients: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview email before sending.
     */
    public function preview(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'recipient_groups' => 'required|array',
            'recipient_groups.*' => 'string|in:all,active,inactive,blocked,kyc_verified,email_verified,specific_users',
            'specific_users' => 'nullable|array',
            'specific_users.*' => 'exists:users,id'
        ]);

        try {
            // Get recipient count
            $query = $this->buildRecipientQuery($validated['recipient_groups'], $validated['specific_users'] ?? []);
            $recipientCount = $query->count();
            
            // Get sample recipients (first 5)
            $sampleRecipients = $query->limit(5)->get(['first_name', 'last_name', 'email']);
            
            // Process content for preview
            $processedContent = $this->processEmailContent($validated['content']);
            
            return response()->json([
                'success' => true,
                'preview' => [
                    'subject' => $validated['subject'],
                    'content' => $processedContent,
                    'recipient_count' => $recipientCount,
                    'sample_recipients' => $sampleRecipients,
                    'groups' => $validated['recipient_groups']
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate preview: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send mass email.
     */
    public function send(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'recipient_groups' => 'required|array',
            'recipient_groups.*' => 'string|in:all,active,inactive,blocked,kyc_verified,email_verified,specific_users',
            'specific_users' => 'nullable|array',
            'specific_users.*' => 'exists:users,id',
            'send_immediately' => 'boolean',
            'scheduled_at' => 'nullable|date|after:now'
        ]);

        try {
            // Apply database mail settings
            DynamicMailConfigService::configure();

            // Get recipients
            $query = $this->buildRecipientQuery($validated['recipient_groups'], $validated['specific_users'] ?? []);
            $recipientCount = $query->count();

            if ($recipientCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No recipients found for the selected criteria.'
                ], 400);
            }

            // Create campaign record
            $campaign = MassEmailCampaign::create([
                'user_id' => auth()->id(),
                'name' => 'Campaign - ' . now()->format('M d, Y H:i'),
                'subject' => $validated['subject'],
                'content' => $validated['content'],
                'recipient_groups' => $validated['recipient_groups'],
                'specific_users' => $validated['specific_users'] ?? [],
                'total_recipients' => $recipientCount,
                'status' => $validated['send_immediately'] ? 'sending' : 'scheduled',
                'scheduled_at' => $validated['scheduled_at'] ?? null,
                'created_by' => auth()->id()
            ]);

            if ($validated['send_immediately']) {
                $this->dispatchMassEmail($campaign, $query);
                
                return response()->json([
                    'success' => true,
                    'message' => "Mass email campaign initiated! Sending to {$recipientCount} recipients.",
                    'campaign_id' => $campaign->id,
                    'recipient_count' => $recipientCount
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => "Email campaign scheduled successfully for " . \Carbon\Carbon::parse($validated['scheduled_at'])->format('M d, Y \a\t g:i A'),
                    'campaign_id' => $campaign->id,
                    'recipient_count' => $recipientCount
                ]);
            }

        } catch (Exception $e) {
            $this->logError('Mass email send failed', $e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to send mass email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get campaign status.
     */
    public function campaignStatus(MassEmailCampaign $campaign): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            return response()->json([
                'success' => true,
                'campaign' => [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'total_recipients' => $campaign->total_recipients,
                    'emails_sent' => $campaign->emails_sent,
                    'emails_failed' => $campaign->emails_failed,
                    'progress_percentage' => $campaign->getProgressPercentageAttribute(),
                    'created_at' => $campaign->created_at->format('M d, Y \a\t g:i A'),
                    'completed_at' => $campaign->completed_at?->format('M d, Y \a\t g:i A'),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get campaign status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get campaigns list.
     */
    public function campaigns(): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            $campaigns = MassEmailCampaign::with('user')
                ->latest()
                ->paginate(10);

            return response()->json([
                'success' => true,
                'campaigns' => $campaigns
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get campaigns: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel campaign.
     */
    public function cancelCampaign(MassEmailCampaign $campaign): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            if (!in_array($campaign->status, ['pending', 'scheduled', 'sending'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign cannot be cancelled in its current status.'
                ], 400);
            }

            $campaign->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id()
            ]);

            Log::info('Mass email campaign cancelled', [
                'campaign_id' => $campaign->id,
                'cancelled_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign cancelled successfully.'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel campaign: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test email configuration.
     */
    public function testConfiguration(): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            DynamicMailConfigService::configure();
            
            // Send test email to admin
            $testRecipient = auth()->user()->email;
            
            Mail::raw('This is a test email to verify your email configuration for mass email campaigns.', function ($message) use ($testRecipient) {
                $message->to($testRecipient)
                       ->subject('Mass Email Configuration Test')
                       ->from(config('mail.from.address'), config('mail.from.name'));
            });

            return response()->json([
                'success' => true,
                'message' => "Test email sent successfully to {$testRecipient}! Email configuration is working."
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Email configuration test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search users for specific recipient selection.
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'search' => 'required|string|min:2|max:50'
        ]);

        try {
            $users = User::where(function ($query) use ($validated) {
                $query->where('first_name', 'LIKE', '%' . $validated['search'] . '%')
                      ->orWhere('last_name', 'LIKE', '%' . $validated['search'] . '%')
                      ->orWhere('email', 'LIKE', '%' . $validated['search'] . '%')
                      ->orWhere('username', 'LIKE', '%' . $validated['search'] . '%');
            })
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'email', 'status']);

            return response()->json([
                'success' => true,
                'users' => $users
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check admin access.
     */
    private function checkAdminAccess()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Access denied. Admin privileges required.');
        }
    }

    /**
     * Build recipient query based on selected groups.
     */
    private function buildRecipientQuery(array $groups, array $specificUsers = [])
    {
        $query = User::query();

        if (in_array('specific_users', $groups) && !empty($specificUsers)) {
            return $query->whereIn('id', $specificUsers);
        }

        // Handle different recipient groups
        if (in_array('all', $groups)) {
            // No additional filtering
        } elseif (in_array('active', $groups)) {
            $query->where('status', 'active');
        } elseif (in_array('inactive', $groups)) {
            $query->where('status', 'inactive');
        } elseif (in_array('blocked', $groups)) {
            $query->where('status', 'blocked');
        } else {
            // Apply multiple filters
            $statuses = [];
            if (in_array('active', $groups)) $statuses[] = 'active';
            if (in_array('inactive', $groups)) $statuses[] = 'inactive';
            if (in_array('blocked', $groups)) $statuses[] = 'blocked';
            
            if (!empty($statuses)) {
                $query->whereIn('status', $statuses);
            }
        }

        // Additional filters
        if (in_array('kyc_verified', $groups)) {
            $query->kycVerified();
        }

        if (in_array('email_verified', $groups)) {
            $query->verified();
        }

        return $query;
    }

    /**
     * Dispatch mass email job.
     */
    private function dispatchMassEmail(MassEmailCampaign $campaign, $recipientQuery)
    {
        // Process recipients in chunks to avoid memory issues
        $recipientQuery->chunk(100, function ($recipients) use ($campaign) {
            foreach ($recipients as $recipient) {
                // Dispatch individual email job
                \App\Jobs\SendMassEmailJob::dispatch($campaign, $recipient);
            }
        });

        Log::info('Mass email campaign dispatched', [
            'campaign_id' => $campaign->id,
            'total_recipients' => $campaign->total_recipients,
            'initiated_by' => auth()->id()
        ]);
    }

    /**
     * Process email content with placeholders.
     */
    private function processEmailContent(string $content, User $user = null): string
    {
        if (!$user) {
            // Return content with placeholder examples for preview
            $placeholders = [
                '{{first_name}}' => 'John',
                '{{last_name}}' => 'Doe',
                '{{full_name}}' => 'John Doe',
                '{{email}}' => 'john.doe@example.com',
                '{{username}}' => 'johndoe',
                '{{site_name}}' => config('app.name', 'Your Site'),
                '{{site_url}}' => config('app.url'),
                '{{current_date}}' => now()->format('F d, Y'),
                '{{current_year}}' => now()->year,
            ];
        } else {
            // Replace with actual user data
            $placeholders = [
                '{{first_name}}' => $user->first_name,
                '{{last_name}}' => $user->last_name,
                '{{full_name}}' => $user->full_name,
                '{{email}}' => $user->email,
                '{{username}}' => $user->username,
                '{{site_name}}' => config('app.name', 'Your Site'),
                '{{site_url}}' => config('app.url'),
                '{{current_date}}' => now()->format('F d, Y'),
                '{{current_year}}' => now()->year,
            ];
        }

        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
    }

    /**
     * Get email statistics.
     */
    private function getEmailStatistics(): array
    {
        return [
            'total_campaigns' => MassEmailCampaign::count(),
            'active_campaigns' => MassEmailCampaign::whereIn('status', ['sending', 'scheduled'])->count(),
            'completed_campaigns' => MassEmailCampaign::where('status', 'completed')->count(),
            'total_emails_sent' => MassEmailCampaign::sum('emails_sent'),
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'kyc_verified_users' => User::kycVerified()->count(),
            'email_verified_users' => User::verified()->count(),
        ];
    }

    /**
     * Log errors.
     */
    private function logError(string $message, Exception $exception)
    {
        Log::error($message, [
            'user_id' => auth()->id(),
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);
    }
}