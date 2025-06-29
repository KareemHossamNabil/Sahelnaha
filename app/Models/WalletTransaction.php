<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'amount',
        'type',
        'description',
        'status',
        'metadata'
    ];
    protected $casts = [
        'metadata' => 'array',
    ];
    public function wallet()
    {
        return $this->belongsTo(TechnicianWallet::class, 'wallet_id');
    }
}
