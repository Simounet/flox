<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Follower extends Model
{
    protected $fillable = [
        'profile_id',
        'target_profile_id',
        'activity_id'
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'profile_id', 'id');
    }

    public function target()
    {
        return $this->belongsTo(Profile::class, 'target_profile_id', 'id');
    }
}
