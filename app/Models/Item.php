<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = ['item_name', 'price', 'description', 'item_img', 'market_id'];

    public function market()
    {
        return $this->belongsTo(Market::class);
    }
}
