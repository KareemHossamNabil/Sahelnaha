<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'name_ar'        => $this->name_ar,
            'name_en'        => $this->name_en,

            'price'       => $this->price,
            'rating'      => $this->rating,
            'discount'    => $this->discount,
            'category'    => $this->category,
            'description' => $this->description,
            'image_url'   => $this->image ? asset('storage/' . $this->image) : null,
            'reviews'     => $this->reviews->map(function ($review) {
                return [
                    'user_name' => $review->user_name,
                    'rating'  => $review->rating,
                    'comment'  => $review->comment
                ];
            }),
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
