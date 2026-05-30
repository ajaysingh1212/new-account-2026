<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TermsTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id','title','document_type','content','status','is_default','attachment','created_by',
    ];

    protected $casts = ['is_default' => 'boolean'];

    public function company() { return $this->belongsTo(Company::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
