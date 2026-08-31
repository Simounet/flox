<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditCast extends Model
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
     * Create the new cast.
     *
     * @param $data
     * @return CreditCast
     */
     public function store(int $tmdbId, array $cast): self
     {
       return $this->firstOrCreate(
         [
           'tmdb_id' => $tmdbId,
           'person_id' => $cast['person']['id']
         ],
         [
           'character' => $cast['character'] ?? '',
           'known_for_department' => $cast['known_for_department'] ?? '',
           'credit_id' => $cast['credit_id'],
           'order' => $cast['order']
         ]
       );
     }

     // @TODO ValueObject instead of array return
     public function fromTMDB(int $tmdbId, object $cast): array
     {
       return [
         'tmdb_id' => $tmdbId,
         'person_id' => $cast->id,
         'character' => $cast->character,
         'known_for_department' => $cast->known_for_department,
         'credit_id' => $cast->credit_id,
         'order' => $cast->order
       ];
     }
}
