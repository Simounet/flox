<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reviews = (new ReviewCollection($this->review))->toArray($request);

        $data = parent::toArray($request);
        $data['review'] = $reviews;
        return $data;
    }
}
