<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ReviewCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        $reviews = array_map(function($review) {
            return new ReviewResource($review);
        }, $this->collection->toArray());
        return $reviews;
    }
}
