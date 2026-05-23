<?php

namespace App\Services;

use App\Models\EntryVisibility;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class EntryVisibilityService
{
    public function scopeForUser(Builder $query, string $modelClass): Builder
    {
        $user = auth()->user();
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        $query->where('company_id', $user->current_company_id);

        return $this->applyIfSupported($query, $modelClass);
    }

    public function apply(Builder $query, string $modelClass): Builder
    {
        $user = auth()->user();
        if (!$user || $user->isSuperAdmin() || $user->isAdmin()) {
            return $query;
        }

        $roleIds = UserRole::where('user_id', $user->id)
            ->where('company_id', $user->current_company_id)
            ->pluck('role_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $visibleIds = EntryVisibility::where('entry_type', $modelClass)
            ->where('company_id', $user->current_company_id)
            ->get()
            ->filter(function (EntryVisibility $visibility) use ($user, $roleIds) {
                if ($visibility->visible_to_all_company) {
                    return true;
                }

                $visibleRoles = array_map('intval', $visibility->visible_to_roles ?? []);
                $visibleUsers = array_map('intval', $visibility->visible_to_users ?? []);

                return in_array((int) $user->id, $visibleUsers, true)
                    || count(array_intersect($roleIds, $visibleRoles)) > 0;
            })
            ->pluck('entry_id')
            ->all();

        return $query->where(function (Builder $builder) use ($user, $visibleIds) {
            $builder->where('created_by', $user->id);
            if (!empty($visibleIds)) {
                $builder->orWhereIn('id', $visibleIds);
            }
        });
    }

    public function applyIfSupported(Builder $query, string $modelClass): Builder
    {
        $model = new $modelClass;
        if (!in_array('created_by', $model->getFillable(), true)) {
            return $query;
        }

        return $this->apply($query, $modelClass);
    }

    public function syncFromRequest(Request $request, Model $entry): void
    {
        $roles = array_values(array_filter(array_map('intval', $request->input('visible_to_roles', []))));
        $users = array_values(array_filter(array_map('intval', $request->input('visible_to_users', []))));

        EntryVisibility::updateOrCreate(
            [
                'entry_type' => $entry::class,
                'entry_id' => $entry->getKey(),
            ],
            [
                'company_id' => $entry->company_id,
                'visible_to_all_company' => $request->boolean('visible_to_all_company'),
                'visible_to_roles' => $roles,
                'visible_to_users' => $users,
            ]
        );
    }

    public function canView(User $user, Model $entry): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            return (int) $entry->company_id === (int) $user->current_company_id;
        }

        if ((int) ($entry->created_by ?? 0) === (int) $user->id) {
            return true;
        }

        return EntryVisibility::canView($user, $entry::class, $entry->getKey());
    }

    public function canManage(User $user, Model $entry): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ((int) $entry->company_id !== (int) $user->current_company_id) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return (int) ($entry->created_by ?? 0) === (int) $user->id;
    }

    public function authorizeView(Model $entry): void
    {
        abort_unless($this->canView(auth()->user(), $entry), 403);
    }

    public function authorizeManage(Model $entry): void
    {
        abort_unless($this->canManage(auth()->user(), $entry), 403);
    }
}
