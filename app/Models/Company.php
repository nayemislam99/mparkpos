<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function headOfficeBranch(): HasOne
    {
        return $this->hasOne(Branch::class)->where('is_head_office', true);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(CompanyRolePermission::class);
    }
}
