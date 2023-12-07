<?php

namespace App\Http\Resources;

use App\Models\Item;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'content' => $this->content,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->getUser($this->user),
            'item' => $this->when($this->relationLoaded('item'), $this->getItem($this->item))
        ];
        return $data;
    }

    private function getUser(User $user): array
    {
        return [
            'username' => $user->username,
            'user_id' => $user->id
        ];
    }

    private function getItem(Item $item): array
    {
        return [
            'tmdb_id' => $item->tmdb_id,
            'title' => $item->title,
            'slug' => $item->slug,
            'media_type' => $item->media_type
        ];
    }
}
