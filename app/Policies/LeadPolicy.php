<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('leads.view');
    }

    /** Sales users only see leads assigned to them (or unassigned); managers see all. */
    public function view(User $user, Lead $lead): bool
    {
        if (! $user->can('leads.view')) {
            return false;
        }

        return $user->can('leads.view_all') || $lead->assigned_to === null || $lead->assigned_to === $user->id;
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->can('leads.manage') && $this->view($user, $lead);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->can('leads.delete');
    }

    public function export(User $user): bool
    {
        return $user->can('leads.export');
    }
}
