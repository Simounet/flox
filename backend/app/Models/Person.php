<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    protected $table = 'persons';

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
     * Scope to find the person by name.
     */
    public function scopeFindByName(Builder $query, string $person): Builder
    {
      return $query->where('name', $person);
    }

    /**
     * Create the new person.
     *
     * @param $data
     * @return Person
     */
     public function store(array $person): self
     {
       return $this->firstOrCreate(
         ['id' => $person['id']],
         [
           'name' => $person['name'],
           'profile_path' => $person['profile_path'],
           'popularity' => $person['popularity']
         ]
       );
     }

     // @TODO define ValueObject instead of $person object
     // @TODO define ValueObject instead of array
     public function fromCredits(object $person): array
     {
       return [
         'id' => $person->id,
         'name' => $person->name,
         'profile_path' => $person->profile_path,
         'popularity' => $person->popularity
       ];
     }
}
