<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Kra8\Snowflake\HasSnowflakePrimary;

class Profile extends Model
{
    use HasSnowflakePrimary;

    public const INSTANCE_ACTOR_ID = 1;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $casts = [
        'last_fetched_at' => 'datetime'
    ];
    protected $hidden = ['private_key'];
    protected $visible = ['id', 'user_id', 'username', 'name'];
    public $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'followers',
            'profile_id',
            'target_profile_id'
        );
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'followers',
            'target_profile_id',
            'profile_id'
        );
    }

    public function whereLocalProfile(string $username): ?self
    {
        return $this->firstWhere(['username' => $username, 'domain' => config('flox.domain')]);
    }
}
