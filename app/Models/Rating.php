<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'offer_id',
        'technician_id',
        'user_id',
        'rating',
        'comment',
        'invoice_image'
    ];

    /**
     * Get the technician that owns the rating
     */
    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    /**
     * Get the user that created the rating
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the offer that was rated
     */
    public function offer()
    {
        return $this->belongsTo(TechnicianOffer::class, 'offer_id');
    }
}
