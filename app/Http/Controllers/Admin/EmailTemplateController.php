<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Exception;

class EmailTemplateController extends Controller
{
    /**
     * Display email templates page
     */
    public function index()
    {
        $user = \Auth::user();
        $templates = EmailTemplate::orderBy('category')->orderBy('name')->get();
        
        return view('admin.settings.email.templates', compact('templates', 'user'));
    }

    /**
     * Get all email templates (API)
     */
    public function getTemplates()
    {
        $templates = EmailTemplate::all();
        
        return response()->json([
            'success' => true,
            'templates' => $templates
        ]);
    }

    /**
     * Get single template
     */
    public function show($id)
    {
        $template = EmailTemplate::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'template' => $template
        ]);
    }

    /**
     * Store new email template
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:email_templates,slug',
            'category' => 'required|string|in:transaction,investment,kyc,referral,support,account,system',
            'subject' => 'required|string|max:500',
            'body' => 'required|string',
            'variables' => 'nullable|json',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $template = EmailTemplate::create([
                'name' => $request->name,
                'slug' => $request->slug,
                'category' => $request->category,
                'subject' => $request->subject,
                'body' => $request->body,
                'variables' => $request->variables ? json_decode($request->variables, true) : [],
                'is_active' => $request->is_active ?? true,
                'created_by' => auth()->id()
            ]);

            Log::info('Email template created', [
                'template_id' => $template->id,
                'slug' => $template->slug,
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email template created successfully',
                'template' => $template
            ]);

        } catch (Exception $e) {
            Log::error('Failed to create email template', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update email template
     */
    public function update(Request $request, $id)
    {
        $template = EmailTemplate::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:email_templates,slug,' . $id,
            'category' => 'sometimes|string|in:transaction,investment,kyc,referral,support,account,system',
            'subject' => 'sometimes|string|max:500',
            'body' => 'sometimes|string',
            'variables' => 'nullable|json',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $template->update($request->only([
                'name', 'slug', 'category', 'subject', 'body', 'is_active'
            ]));

            if ($request->has('variables')) {
                $template->variables = json_decode($request->variables, true);
                $template->save();
            }

            $template->updated_by = auth()->id();
            $template->save();

            Log::info('Email template updated', [
                'template_id' => $template->id,
                'slug' => $template->slug,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email template updated successfully',
                'template' => $template
            ]);

        } catch (Exception $e) {
            Log::error('Failed to update email template', [
                'template_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete email template
     */
    public function destroy($id)
    {
        try {
            $template = EmailTemplate::findOrFail($id);
            
            if ($template->is_system) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete system templates'
                ], 403);
            }

            $slug = $template->slug;
            $template->delete();

            Log::info('Email template deleted', [
                'template_id' => $id,
                'slug' => $slug,
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email template deleted successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to delete email template', [
                'template_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle template active status
     */
    public function toggleStatus($id)
    {
        try {
            $template = EmailTemplate::findOrFail($id);
            $template->is_active = !$template->is_active;
            $template->save();

            return response()->json([
                'success' => true,
                'message' => 'Template status updated',
                'is_active' => $template->is_active
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview email template
     */
    public function preview(Request $request, $id)
    {
        $template = EmailTemplate::findOrFail($id);
        
        // Sample data for preview
        $sampleData = [
            'user_name' => 'John Doe',
            'user_email' => 'john@example.com',
            'amount' => '1000.00',
            'currency' => 'USDT',
            'transaction_id' => 'TXN123456',
            'plan_name' => 'Premium Plan',
            'commission' => '50.00',
            'referral_name' => 'Jane Smith',
            'support_ticket' => '#12345',
            'platform_name' => config('app.name'),
            'login_url' => route('login'),
            'dashboard_url' => route('dashboard')
        ];

        $previewSubject = $this->replaceVariables($template->subject, $sampleData);
        $previewBody = $this->replaceVariables($template->body, $sampleData);

        return response()->json([
            'success' => true,
            'preview' => [
                'subject' => $previewSubject,
                'body' => $previewBody,
                'sample_data' => $sampleData
            ]
        ]);
    }

    /**
     * Send test email
     */
    public function sendTest(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'test_email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $template = EmailTemplate::findOrFail($id);
            
            $sampleData = [
                'user_name' => auth()->user()->full_name,
                'amount' => '100.00',
                'platform_name' => config('app.name')
            ];

            $subject = $this->replaceVariables($template->subject, $sampleData);
            $body = $this->replaceVariables($template->body, $sampleData);

            Mail::raw($body, function ($message) use ($request, $subject) {
                $message->to($request->test_email)
                       ->subject('[TEST] ' . $subject);
            });

            return response()->json([
                'success' => true,
                'message' => 'Test email sent to ' . $request->test_email
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send test email', [
                'template_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicate template
     */
    public function duplicate($id)
    {
        try {
            $original = EmailTemplate::findOrFail($id);
            
            $template = $original->replicate();
            $template->name = $original->name . ' (Copy)';
            $template->slug = $original->slug . '-copy-' . time();
            $template->is_system = false;
            $template->created_by = auth()->id();
            $template->save();

            return response()->json([
                'success' => true,
                'message' => 'Template duplicated successfully',
                'template' => $template
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available variables for category
     */
    public function getVariables($category)
    {
        $variables = $this->getCategoryVariables($category);
        
        return response()->json([
            'success' => true,
            'variables' => $variables
        ]);
    }

    /**
     * Replace variables in text
     */
    private function replaceVariables(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        return $text;
    }

    /**
     * Get available variables by category
     */
    private function getCategoryVariables($category): array
    {
        $common = [
            '{user_name}' => 'User full name',
            '{user_email}' => 'User email address',
            '{user_username}' => 'Username',
            '{platform_name}' => 'Platform name',
            '{login_url}' => 'Login page URL',
            '{dashboard_url}' => 'Dashboard URL',
            '{support_url}' => 'Support page URL',
            '{date}' => 'Current date',
            '{time}' => 'Current time'
        ];

        $specific = match($category) {
            'transaction' => [
                '{amount}' => 'Transaction amount',
                '{currency}' => 'Currency type',
                '{transaction_id}' => 'Transaction ID',
                '{transaction_type}' => 'Transaction type (deposit/withdrawal)',
                '{transaction_status}' => 'Transaction status',
                '{wallet_address}' => 'Wallet address'
            ],
            'investment' => [
                '{amount}' => 'Investment amount',
                '{plan_name}' => 'Investment plan name',
                '{duration}' => 'Investment duration',
                '{return_percentage}' => 'Expected return percentage',
                '{return_amount}' => 'Return amount',
                '{investment_id}' => 'Investment ID'
            ],
            'kyc' => [
                '{kyc_status}' => 'KYC verification status',
                '{rejection_reason}' => 'Reason for rejection',
                '{kyc_url}' => 'KYC submission URL'
            ],
            'referral' => [
                '{referral_name}' => 'Referred user name',
                '{commission}' => 'Commission amount',
                '{referral_link}' => 'Personal referral link',
                '{total_referrals}' => 'Total referrals count'
            ],
            'support' => [
                '{ticket_number}' => 'Support ticket number',
                '{ticket_subject}' => 'Ticket subject',
                '{ticket_status}' => 'Ticket status',
                '{ticket_url}' => 'Ticket URL',
                '{reply_content}' => 'Reply content'
            ],
            'account' => [
                '{verification_link}' => 'Email verification link',
                '{reset_password_link}' => 'Password reset link',
                '{old_email}' => 'Old email address',
                '{new_email}' => 'New email address'
            ],
            default => []
        };

        return array_merge($common, $specific);
    }

    /**
     * Seed default templates
     */
    public function seedDefaults()
    {
        try {
            $this->createDefaultTemplates();

            return response()->json([
                'success' => true,
                'message' => 'Default templates created successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to seed templates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create default email templates
     */
    private function createDefaultTemplates()
    {
        $defaults = [
            [
                'name' => 'Welcome Email',
                'slug' => 'welcome-email',
                'category' => 'account',
                'subject' => 'Welcome to {platform_name}!',
                'body' => "Hi {user_name},\n\nWelcome to {platform_name}! We're excited to have you join our community.\n\nYou can access your dashboard here: {dashboard_url}\n\nIf you have any questions, feel free to contact our support team.\n\nBest regards,\n{platform_name} Team",
                'is_system' => true
            ],
            [
                'name' => 'Deposit Confirmation',
                'slug' => 'deposit-confirmation',
                'category' => 'transaction',
                'subject' => 'Deposit Confirmed - {amount} {currency}',
                'body' => "Hi {user_name},\n\nYour deposit of {amount} {currency} has been confirmed and credited to your account.\n\nTransaction ID: {transaction_id}\nAmount: {amount} {currency}\nStatus: Completed\n\nView transaction: {dashboard_url}/transactions\n\nThank you for using {platform_name}!",
                'is_system' => true
            ],
            [
                'name' => 'Withdrawal Approved',
                'slug' => 'withdrawal-approved',
                'category' => 'transaction',
                'subject' => 'Withdrawal Approved - {amount} {currency}',
                'body' => "Hi {user_name},\n\nYour withdrawal request has been approved and processed.\n\nTransaction ID: {transaction_id}\nAmount: {amount} {currency}\nWallet: {wallet_address}\nStatus: Completed\n\nThe funds should arrive in your wallet within 24 hours.\n\nView transaction: {dashboard_url}/transactions",
                'is_system' => true
            ],
            [
                'name' => 'Investment Created',
                'slug' => 'investment-created',
                'category' => 'investment',
                'subject' => 'Investment Activated - {plan_name}',
                'body' => "Hi {user_name},\n\nYour investment has been successfully activated!\n\nPlan: {plan_name}\nAmount: {amount}\nDuration: {duration}\nExpected Return: {return_percentage}%\n\nView your investment: {dashboard_url}/bot\n\nYou'll receive regular updates on your returns.\n\nHappy investing!",
                'is_system' => true
            ],
            [
                'name' => 'Investment Return Paid',
                'slug' => 'investment-return-paid',
                'category' => 'investment',
                'subject' => 'Investment Return Received - {amount}',
                'body' => "Hi {user_name},\n\nGreat news! You've received a return from your investment.\n\nPlan: {plan_name}\nReturn Amount: {amount}\nTotal Earned: {total_earned}\n\nView details: {dashboard_url}/bot\n\nKeep up the great investing!",
                'is_system' => true
            ],
            [
                'name' => 'KYC Approved',
                'slug' => 'kyc-approved',
                'category' => 'kyc',
                'subject' => 'Identity Verification Approved',
                'body' => "Hi {user_name},\n\nExcellent news! Your identity verification has been approved.\n\nYou now have access to all platform features including:\n- Withdrawals\n- Higher investment limits\n- Premium support\n\nStart using all features: {dashboard_url}\n\nThank you for completing your verification!",
                'is_system' => true
            ],
            [
                'name' => 'KYC Rejected',
                'slug' => 'kyc-rejected',
                'category' => 'kyc',
                'subject' => 'Identity Verification Requires Attention',
                'body' => "Hi {user_name},\n\nWe were unable to verify your identity at this time.\n\nReason: {rejection_reason}\n\nPlease resubmit your verification with the correct information: {kyc_url}\n\nIf you need help, contact our support team: {support_url}\n\nWe're here to help!",
                'is_system' => true
            ],
            [
                'name' => 'New Referral',
                'slug' => 'new-referral',
                'category' => 'referral',
                'subject' => 'New Referral Joined!',
                'body' => "Hi {user_name},\n\nGreat news! {referral_name} has joined using your referral link.\n\nYour referral stats:\n- Total Referrals: {total_referrals}\n- Total Earned: {commission}\n\nView your referral network: {dashboard_url}/referrals\n\nKeep sharing your link: {referral_link}",
                'is_system' => true
            ],
            [
                'name' => 'Commission Earned',
                'slug' => 'commission-earned',
                'category' => 'referral',
                'subject' => 'Commission Earned - {amount}',
                'body' => "Hi {user_name},\n\nYou've earned commission from your referral network!\n\nAmount: {amount}\nFrom: {referral_name}\n\nView earnings: {dashboard_url}/referrals\n\nKeep building your network!",
                'is_system' => true
            ],
            [
                'name' => 'Support Ticket Reply',
                'slug' => 'support-ticket-reply',
                'category' => 'support',
                'subject' => 'New Reply on Ticket #{ticket_number}',
                'body' => "Hi {user_name},\n\nYou have a new reply on your support ticket.\n\nTicket: #{ticket_number}\nSubject: {ticket_subject}\nStatus: {ticket_status}\n\nView and reply: {ticket_url}\n\nWe're here to help!",
                'is_system' => true
            ],
            [
                'name' => 'Password Changed',
                'slug' => 'password-changed',
                'category' => 'account',
                'subject' => 'Password Changed Successfully',
                'body' => "Hi {user_name},\n\nYour password was recently changed.\n\nDate: {date}\nTime: {time}\n\nIf you didn't make this change, please contact support immediately: {support_url}\n\nYour account security is important to us.",
                'is_system' => true
            ],
            [
                'name' => 'Email Verification',
                'slug' => 'email-verification',
                'category' => 'account',
                'subject' => 'Verify Your Email Address',
                'body' => "Hi {user_name},\n\nPlease verify your email address by clicking the link below:\n\n{verification_link}\n\nThis link will expire in 24 hours.\n\nIf you didn't create an account, you can safely ignore this email.\n\nWelcome to {platform_name}!",
                'is_system' => true
            ]
        ];

        foreach ($defaults as $template) {
            EmailTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                array_merge($template, ['created_by' => auth()->id()])
            );
        }
    }
}