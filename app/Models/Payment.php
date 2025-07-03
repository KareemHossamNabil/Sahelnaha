<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'order_id',
        'payment_method',
        'amount',
        'currency',
        'payment_status',
        'transaction_id',
        'paymob_order_id',
        'paymob_transaction_id',
        'wallet_number',
        'card_details',
        'payment_response'
    ];

    /**
     * Cast attributes to specific types.
     *
     * @var array
     */
    protected $casts = [
        'card_details' => 'array',
        'payment_response' => 'array',
        'amount' => 'decimal:2',
    ];
    protected $attributes = [
        'currency' => 'EGP',
        'payment_status' => 'pending',
    ];

    
    // العلاقة مع المستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

        // نطاقات الاستعلام (Query Scopes)
    public function scopeSuccessful($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeFailed($query)
    {
        return $query->where('payment_status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }
}
