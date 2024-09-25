<?php

namespace App\ValueObjects;

class EpisodeUserValueObject
{
    public function __construct(
        protected int $userId,
        protected int $episodeId,
    ) {}

    public function get(): array {
        return [
          'user_id' => $this->userId,
          'episode_id' => $this->episodeId
        ];
    }
}
