<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'trade_name',
        'bin',
        'tin',
        'business_type',
        'email',
        'phone',
        'address',
        'is_active',
        'deactivated_at',
        'deactivated_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deactivated_at' => 'datetime',
    ];

    public function branches()
    {
        return $this->hasMany(CompanyBranch::class);
    }

    public function headOfficeBranch()
    {
        return $this->hasOne(CompanyBranch::class)->where('is_head_office', true);
    }
}
