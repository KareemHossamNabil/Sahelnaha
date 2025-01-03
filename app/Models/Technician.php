<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Technician extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'email', 'password', 'address'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
