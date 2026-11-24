<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'phone',
        'email',
        'nid',
        'address',
        'address_line_2',
        'city',
        'customer_group',
        'customer_type',
        'notes',
        'advance_balance',
        'due_limit',
        'opening_balance',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'advance_balance' => 'decimal:2',
        'due_limit' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function getOutstandingDueAttribute(): float
    {
        $saleDue = (float) DB::table('sales')
            ->where('company_id', $this->company_id)
            ->where('customer_id', $this->id)
            ->where('payment_status', '!=', 'paid')
            ->where('status', 'completed')
            ->sum('due_total');

        return (float) ($this->opening_balance + $saleDue);
    }
}
