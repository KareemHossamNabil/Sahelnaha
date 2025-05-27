<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class TechnicianWallet extends Model
{
    use HasFactory;

    protected $fillable = ['tech_id', 'balance'];

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
