<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Exception;

class AdminImpersonationController extends Controller
{
    /**
     * Display impersonation dashboard.
     */
    public function index(Request $request): View
    {
        $this->checkAdminAccess();
        $user = Auth::user();

        // Get filter parameters
        $search = $request->get('search');
        $status = $request->get('status');
        $role = $request->get('role');
        $sort_by = $request->get('sort_by', 'created_at');
        $sort_order = $request->get('sort_order', 'desc');

        // Build query for users (exclude current admin and other admins for security)
        $query = User::with(['profile'])
            ->where('id', '!=', auth()->id())
            ->where('role', '!=', User::ROLE_ADMIN);

        // Apply filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('username', 'LIKE', "%{$search}%");
            });
        }

        if ($status) {
            if ($status === 'active') {
                // Active = status active AND has investments
                $query->where('status', 'active')
                    ->whereHas('investments');
            } else {
                // Other statuses (inactive, suspended, etc.)
                $query->where('status', $status);
            }
        }

        if ($role && $role !== 'all') {
            $query->where('role', $role);
        }

        // Apply sorting
        $query->orderBy($sort_by, $sort_order);

        // Get users with pagination
        $users = $query->paginate(15)->withQueryString();

        // Get statistics
        $stats = $this->getImpersonationStatistics();

        // Get current impersonation status
        $currentImpersonation = $this->getCurrentImpersonationStatus();

        return view('admin.impersonation.index', compact(
            'users',
            'stats',
            'search',
            'status',
            'role',
            'user',
            'sort_by',
            'sort_order',
            'currentImpersonation'
        ));
    }

    /**
     * Search users for impersonation.
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'search' => 'required|string|min:2|max:50'
        ]);

        try {
            $users = User::with(['profile'])
                ->where('id', '!=', auth()->id())
                ->where('role', '!=', User::ROLE_ADMIN)
                ->where(function ($query) use ($validated) {
                    $query->where('first_name', 'LIKE', '%' . $validated['search'] . '%')
                        ->orWhere('last_name', 'LIKE', '%' . $validated['search'] . '%')
                        ->orWhere('email', 'LIKE', '%' . $validated['search'] . '%')
                        ->orWhere('username', 'LIKE', '%' . $validated['search'] . '%');
                })
                ->limit(20)
                ->get(['id', 'first_name', 'last_name', 'email', 'username', 'status', 'role'])
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->full_name,
                        'email' => $user->email,
                        'username' => $user->username,
                        'status' => $user->status,
                        'role' => $user->role,
                        'avatar' => $user->profile->avatar_url ?? null,
                    ];
                });

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
     * Start impersonating a user.
     */
    public function startImpersonation(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        try {
            $targetUser = User::findOrFail($validated['user_id']);
            $adminUser = Auth::user();

            // Security check: prevent impersonating admins
            if ($targetUser->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot impersonate admin users for security reasons.'
                ], 403);
            }

            // Security check: prevent impersonating yourself
            if ($targetUser->id === $adminUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot impersonate yourself.'
                ], 400);
            }

            // Check if user is active
            if (!$targetUser->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot impersonate inactive users.'
                ], 400);
            }

            // Store original admin ID in session
            Session::put('impersonation.original_admin_id', $adminUser->id);
            Session::put('impersonation.target_user_id', $targetUser->id);
            Session::put('impersonation.started_at', now());

            // Log the impersonation
            Log::info('Admin started impersonating user', [
                'admin_id' => $adminUser->id,
                'admin_email' => $adminUser->email,
                'admin_name' => $adminUser->full_name,
                'target_user_id' => $targetUser->id,
                'target_user_email' => $targetUser->email,
                'target_user_name' => $targetUser->full_name,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Login as target user
            Auth::login($targetUser);

            return response()->json([
                'success' => true,
                'message' => "Successfully impersonating {$targetUser->full_name}",
                'redirect_url' => route('dashboard')
            ]);

        } catch (Exception $e) {
            Log::error('Failed to start impersonation', [
                'admin_id' => auth()->id(),
                'target_user_id' => $validated['user_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start impersonation. Please try again.'
            ], 500);
        }
    }

    /**
     * Stop impersonating and return to admin account.
     */
    public function stopImpersonation(): RedirectResponse
    {
        $originalAdminId = Session::get('impersonation.original_admin_id');
        $targetUserId = Session::get('impersonation.target_user_id');
        $startedAt = Session::get('impersonation.started_at');

        if (!$originalAdminId) {
            return redirect()->route('dashboard')
                ->with('error', 'No impersonation session found.');
        }

        try {
            $originalAdmin = User::findOrFail($originalAdminId);
            $currentUser = Auth::user();

            // Log the end of impersonation
            Log::info('Admin stopped impersonating user', [
                'admin_id' => $originalAdminId,
                'admin_name' => $originalAdmin->full_name,
                'target_user_id' => $targetUserId,
                'target_user_name' => $currentUser ? $currentUser->full_name : 'Unknown',
                'current_user_id' => $currentUser ? $currentUser->id : null,
                'duration_minutes' => $startedAt ? now()->diffInMinutes($startedAt) : null,
                'ip_address' => request()->ip()
            ]);

            // Clear impersonation session data
            Session::forget('impersonation.original_admin_id');
            Session::forget('impersonation.target_user_id');
            Session::forget('impersonation.started_at');

            // Login back as original admin
            Auth::login($originalAdmin);

            return redirect()->route('admin.impersonation.index')
                ->with('success', 'Impersonation ended successfully. Welcome back!');

        } catch (Exception $e) {
            Log::error('Failed to stop impersonation', [
                'original_admin_id' => $originalAdminId,
                'current_user_id' => auth()->id() ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Fallback: logout and redirect to login
            Auth::logout();
            Session::flush();

            return redirect()->route('login')
                ->with('error', 'Impersonation session ended. Please login again.');
        }
    }

    /**
     * Get impersonation history.
     */
    public function history(Request $request): View
    {
        $this->checkAdminAccess();
        $user = Auth::user();

        // This would require a dedicated impersonation_logs table
        // For now, we'll show a placeholder view
        return view('admin.impersonation.history', compact('user'));
    }

    /**
     * Get current impersonation status.
     */
    public function getStatus(): JsonResponse
    {
        $status = $this->getCurrentImpersonationStatus();

        return response()->json([
            'success' => true,
            'status' => $status
        ]);
    }

    /**
     * Check admin access.
     */
    private function checkAdminAccess(): void
    {
        if (!auth()->user() || !auth()->user()->canAccessAdmin()) {
            abort(403, 'Access denied. Admin privileges required.');
        }
    }

    /**
     * Get impersonation statistics.
     */
    private function getImpersonationStatistics(): array
    {
        $totalUsers = User::where('role', '!=', User::ROLE_ADMIN)->count();
        // Active users = status 'active' AND have investments
        $activeUsers = User::where('role', '!=', User::ROLE_ADMIN)
            ->where('status', 'active')
            ->whereHas('investments')
            ->count();
        $verifiedUsers = User::where('role', '!=', User::ROLE_ADMIN)->verified()->count();

        // Count KYC verified users using the model's method
        $kycVerifiedUsers = User::where('role', '!=', User::ROLE_ADMIN)
            ->get()
            ->filter(function ($user) {
                return $user->isKycVerified();
            })
            ->count();

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'verified_users' => $verifiedUsers,
            'kyc_verified_users' => $kycVerifiedUsers,
        ];
    }

    /**
     * Get current impersonation status.
     */
    private function getCurrentImpersonationStatus(): ?array
    {
        $originalAdminId = Session::get('impersonation.original_admin_id');
        $targetUserId = Session::get('impersonation.target_user_id');
        $startedAt = Session::get('impersonation.started_at');

        if (!$originalAdminId || !$targetUserId) {
            return null;
        }

        try {
            $originalAdmin = User::find($originalAdminId);
            $currentUser = Auth::user();

            // Validate session integrity
            if (!$originalAdmin || !$currentUser || $currentUser->id != $targetUserId) {
                // Clear invalid session data
                Session::forget('impersonation.original_admin_id');
                Session::forget('impersonation.target_user_id');
                Session::forget('impersonation.started_at');
                return null;
            }

            return [
                'is_impersonating' => true,
                'original_admin' => [
                    'id' => $originalAdmin->id,
                    'name' => $originalAdmin->full_name,
                    'email' => $originalAdmin->email,
                    'role' => $originalAdmin->role,
                ],
                'current_user' => [
                    'id' => $currentUser->id,
                    'name' => $currentUser->full_name,
                    'email' => $currentUser->email,
                    'role' => $currentUser->role,
                    'status' => $currentUser->status,
                ],
                'started_at' => $startedAt,
                'duration' => $startedAt ? now()->diffForHumans($startedAt, true) : null,
                'duration_minutes' => $startedAt ? now()->diffInMinutes($startedAt) : 0,
            ];
        } catch (Exception $e) {
            Log::warning('Error getting impersonation status', [
                'error' => $e->getMessage(),
                'original_admin_id' => $originalAdminId,
                'target_user_id' => $targetUserId
            ]);

            // Clear session on any error
            Session::forget('impersonation.original_admin_id');
            Session::forget('impersonation.target_user_id');
            Session::forget('impersonation.started_at');

            return null;
        }
    }

    /**
     * Get user profile information for display.
     */
    private function getUserProfileInfo(User $user): array
    {
        $profile = $user->profile;

        return [
            'avatar_url' => $profile ? $profile->avatar_url : null,
            'country' => $profile ? $profile->country_name : null,
            'kyc_status' => $user->kyc_status,
            'kyc_verified' => $user->isKycVerified(),
            'phone_verified' => $profile ? $profile->isPhoneVerified() : false,
            'total_balance' => $user->total_balance,
            'total_investments' => $profile ? $profile->total_investments : 0,
            'referral_count' => $profile ? $profile->referral_count : 0,
            'last_login' => $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never',
        ];
    }

    /**
     * Get detailed user information for impersonation confirmation.
     */
    public function getUserDetails(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        try {
            $user = User::with(['profile'])->findOrFail($validated['user_id']);

            // Security check: prevent getting admin details
            if ($user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot access admin user details.'
                ], 403);
            }

            $profileInfo = $this->getUserProfileInfo($user);

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'status' => $user->status,
                    'role' => $user->role_display_name,
                    'created_at' => $user->formatted_registration_date,
                    'profile' => $profileInfo,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get impersonation permissions for current admin.
     */
    public function getPermissions(): JsonResponse
    {
        $this->checkAdminAccess();

        $user = Auth::user();
        $permissions = [
            'can_impersonate_users' => $user->canAccessAdmin(),
            'can_impersonate_staff' => $user->isAdmin(), // Only admin can impersonate staff
            'can_view_logs' => $user->hasStaffPrivileges(),
            'available_roles' => $this->getImpersonatableRoles($user),
        ];

        return response()->json([
            'success' => true,
            'permissions' => $permissions
        ]);
    }

    /**
     * Get roles that current admin can impersonate.
     */
    private function getImpersonatableRoles(User $admin): array
    {
        $allRoles = [
            User::ROLE_USER => 'User',
            User::ROLE_MODERATOR => 'Moderator',
            User::ROLE_SUPPORT => 'Support',
        ];

        // Only admin can impersonate staff
        if (!$admin->isAdmin()) {
            unset($allRoles[User::ROLE_MODERATOR], $allRoles[User::ROLE_SUPPORT]);
        }

        return $allRoles;
    }

    /**
     * Validate impersonation session periodically.
     */
    public function validateSession(): JsonResponse
    {
        $status = $this->getCurrentImpersonationStatus();

        if (!$status) {
            return response()->json([
                'success' => false,
                'message' => 'No valid impersonation session found.'
            ], 404);
        }

        // Check if session has been running too long (optional security measure)
        $maxDurationMinutes = config('admin.impersonation.max_duration_minutes', 480); // 8 hours default

        if ($status['duration_minutes'] > $maxDurationMinutes) {
            return response()->json([
                'success' => false,
                'message' => 'Impersonation session has exceeded maximum duration.',
                'should_terminate' => true
            ], 403);
        }

        return response()->json([
            'success' => true,
            'status' => $status
        ]);
    }
}