<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    protected $fillable = ['name', 'address'];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
