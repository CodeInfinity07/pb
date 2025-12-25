<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine if the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasStaffPrivileges();
    }

    /**
     * Determine if the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Users can view their own profile, staff can view any user
        return $user->id === $model->id || $user->hasStaffPrivileges();
    }

    /**
     * Determine if the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Users can update their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // Admin can update any user
        if ($user->isAdmin()) {
            return true;
        }

        // Moderators can update regular users only
        if ($user->isModerator() && $model->isUser()) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Prevent self-deletion
        if ($user->id === $model->id) {
            return false;
        }

        // Only admins can delete users
        return $user->isAdmin();
    }

    /**
     * Determine if the user can manage roles.
     */
    public function manageRoles(User $user, User $model): bool
    {
        // Prevent self role modification
        if ($user->id === $model->id) {
            return false;
        }

        // Only admins can change roles
        return $user->isAdmin();
    }

    /**
     * Determine if the user can manage users.
     */
    public function manageUsers(User $user): bool
    {
        return $user->canManageUsers();
    }

    /**
     * Determine if the user can access admin panel.
     */
    public function accessAdmin(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    /**
     * Determine if the user can suspend other users.
     */
    public function suspend(User $user, User $model): bool
    {
        // Cannot suspend self
        if ($user->id === $model->id) {
            return false;
        }

        // Admin can suspend anyone except other admins
        if ($user->isAdmin()) {
            return !$model->isAdmin() || $user->id !== $model->id;
        }

        // Moderators can suspend regular users only
        return $user->isModerator() && $model->isUser();
    }

    /**
     * Determine if the user can view financial data.
     */
    public function viewFinancials(User $user, User $model): bool
    {
        // Users can view their own financials
        if ($user->id === $model->id) {
            return true;
        }

        // Staff can view user financials for support
        return $user->hasStaffPrivileges();
    }
}