<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name','email','password','user_type','profile_pic','background_pic',
        'phone','address','facebook','twitter','linkedin','instagram','website',
        'current_company_id','is_active',
    ];

    protected $hidden = ['password','remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'user_companies');
    }

    public function currentCompany()
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    public function userRoles()
    {
        return $this->hasMany(UserRole::class);
    }

    public function rolesForCompany($companyId)
    {
        return Role::whereIn('id',
            $this->userRoles()->where('company_id', $companyId)->pluck('role_id')
        )->get();
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    // ── Helpers ───────────────────────────────────────────────
    public function isSuperAdmin(): bool
    {
        return $this->user_type === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    public function hasPermission(string $slug, ?int $companyId = null): bool
    {
        if ($this->isSuperAdmin()) return true;

        $companyId = $companyId ?? $this->current_company_id;
        $roleIds = $this->userRoles()->where('company_id', $companyId)->pluck('role_id');

        return Permission::where('slug', $slug)
            ->whereHas('roles', fn($q) => $q->whereIn('roles.id', $roleIds))
            ->exists();
    }

    public function getProfilePicUrlAttribute(): string
    {
        return $this->profile_pic
            ? asset('storage/' . $this->profile_pic)
            : asset('img/default-avatar.png');
    }

    public function getBackgroundPicUrlAttribute(): string
    {
        return $this->background_pic
            ? asset('storage/' . $this->background_pic)
            : asset('img/default-bg.jpg');
    }
}
