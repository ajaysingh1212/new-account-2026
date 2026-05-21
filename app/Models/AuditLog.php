<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'user_id','company_id','action','model','model_id',
        'old_values','new_values','ip_address','user_agent','description',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function company() { return $this->belongsTo(Company::class); }

    // Static helper to log actions
    public static function log(string $action, array $data = []): void
    {
        $user = auth()->user();
        static::create(array_merge([
            'user_id'    => $user?->id,
            'company_id' => $user?->current_company_id,
            'action'     => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $data));
    }
}
