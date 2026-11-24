<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'created_by',
        'invoice_number',
        'customer_name',
        'customer_phone',
        'subtotal',
        'line_discount_total',
        'order_discount_type',
        'order_discount_value',
        'order_discount_total',
        'grand_total',
        'paid_total',
        'due_total',
        'payment_status',
        'status',
        'note',
        'sold_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_total' => 'decimal:2',
        'due_total' => 'decimal:2',
        'sold_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
