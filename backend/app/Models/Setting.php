<?php
  namespace App\Models;

  use Illuminate\Database\Eloquent\Model;

  class Setting extends Model {

    protected $primaryKey = "user_id";

    /**
     * No timestamps needed.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Don't auto-apply mass assignment protection.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
      'show_date' => 'boolean',
      'show_genre' => 'boolean',
      'episode_spoiler_protection' => 'boolean',
      'show_watchlist_everywhere' => 'boolean',
      'refresh_automatically' => 'boolean',
      'daily_reminder' => 'boolean',
      'weekly_reminder' => 'boolean',
      'last_fetch_to_file_parser' => 'datetime',
    ];
  }
