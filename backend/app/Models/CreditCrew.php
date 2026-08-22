<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditCrew extends Model
{
    /**
     * No timestamps needed.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [
      'person'
    ];

    /**
     * Don't auto-apply mass assignment protection.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Belongs to one person.
     */
    public function person(): BelongsTo
    {
      return $this->belongsTo(Person::class);
    }

    /**
     * Create the new crew.
     *
     * @param $data
     * @return CreditCrew
     */
     public function store(int $tmdbId, array $crew): self
     {
       return $this->firstOrCreate(
         [
           'tmdb_id' => $tmdbId,
           'person_id' => $crew['person']['id']
         ],
         [
            'known_for_department' => $crew['known_for_department'],
            'credit_id' => $crew['credit_id'],
            'department' => $crew['department'],
            'job' => $crew['job']
         ]
       );
     }

     // @TODO ValueObject instead of array return
     public function fromTMDB(int $tmdbId, object $crew): array
     {
       return [
         'tmdb_id' => $tmdbId,
         'person_id' => $crew->id,
         'known_for_department' => $crew->known_for_department,
         'credit_id' => $crew->credit_id,
         'department' => $crew->department,
         'job' => $crew->job,
       ];
     }
}
