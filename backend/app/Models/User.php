<?php

  namespace App\Models;

  use Illuminate\Database\Eloquent\Builder;
  use Illuminate\Database\Eloquent\Factories\HasFactory;
  use Illuminate\Database\Eloquent\Relations\BelongsToMany;
  use Illuminate\Database\Eloquent\Relations\HasMany;
  use Illuminate\Database\Eloquent\Relations\HasManyThrough;
  use Illuminate\Foundation\Auth\User as Authenticatable;

  class User extends Authenticatable {
    use HasFactory;

    protected $casts = [
      'is_admin' => 'boolean'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
      'username',
      'password',
      'is_admin',
      'api_key',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
      'password',
      'remember_token',
    ];

    public function items(): HasManyThrough
    {
        return $this->hasManyThrough(
            Item::class,
            ItemUser::class,
            'user_id', // Foreign key on item_users pointing to users
            'id', // Foreign key on items used by item_users.item_id
            'id', // Local key on users
            'item_id' // Local key on item_users pointing to items.id
        );
    }

    public function itemUsers(): HasMany
    {
      return $this->hasMany(ItemUser::class);
    }

    public function episodes(): BelongsToMany
    {
      return $this->belongsToMany(Episode::class)->using(EpisodeUser::class);
    }

    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(
            Review::class,
            ItemUser::class,
            'user_id', // Foreign key on item_users pointing to users
            'item_user_id', // Foreign key on reviews pointing to item_users
            'id', // Local key on users
            'id'  // Local key on item_users
        );
    }

    /**
     * Scope to find a user by an api key.
     */
    public function scopeFindByApiKey(Builder $query, string $key): Builder
    {
      return $query->where('api_key', $key);
    }

    public function getAuthPasswordName(): string
    {
      return 'password';
    }
  }
