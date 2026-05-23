<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyMerge extends Model
{
    protected $fillable = ['company_id', 'merged_with_company_id', 'notes', 'created_by'];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function mergedWith()
    {
        return $this->belongsTo(Company::class, 'merged_with_company_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all company IDs that are merged together (both directions).
     * Returns the partner company IDs for a given company.
     */
    public static function getMergedCompanyIds(int $companyId): array
    {
        $asSource = static::where('company_id', $companyId)->pluck('merged_with_company_id')->toArray();
        $asTarget = static::where('merged_with_company_id', $companyId)->pluck('company_id')->toArray();
        return array_unique(array_merge($asSource, $asTarget));
    }
}
