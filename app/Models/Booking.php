<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'address_id',
        'payment_method',
        'card_details',
        'status',
        'total_price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'card_details' => 'array',
        'total_price' => 'decimal:2',
    ];

    /**
     * Get the user that owns the booking.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the address associated with the booking.
     */
    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * Get the problem associated with the booking.
     */
    public function problem()
    {
        return $this->hasOne(BookingProblem::class);
    }

    /**
     * Get the schedule associated with the booking.
     */
    public function schedule()
    {
        return $this->hasOne(BookingSchedule::class);
    }
}
