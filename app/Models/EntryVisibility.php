<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntryVisibility extends Model
{
    protected $fillable = [
        'entry_type','entry_id','company_id',
        'visible_to_all_company','visible_to_roles','visible_to_users',
    ];

    protected $casts = [
        'visible_to_all_company' => 'boolean',
        'visible_to_roles' => 'array',
        'visible_to_users' => 'array',
    ];

    /**
     * Check if a user can see a given entry.
     */
    public static function canView(User $user, string $entryType, int $entryId): bool
    {
        if ($user->isSuperAdmin()) return true;
        if ($user->isAdmin()) {
            return static::where('entry_type', $entryType)
                ->where('entry_id', $entryId)
                ->where('company_id', $user->current_company_id)
                ->exists();
        }

        $vis = static::where('entry_type', $entryType)->where('entry_id', $entryId)->first();
        if (!$vis) return false; // default: only creator sees
        if ($vis->visible_to_all_company) return true;

        // Check roles
        $userRoleIds = UserRole::where('user_id', $user->id)
            ->where('company_id', $user->current_company_id)
            ->pluck('role_id')->toArray();

        if (!empty(array_intersect($userRoleIds, $vis->visible_to_roles ?? []))) return true;
        if (in_array($user->id, $vis->visible_to_users ?? [])) return true;

        return false;
    }
}
