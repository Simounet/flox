<?php

declare(strict_types=1);

namespace App\ValueObjects;

readonly class RatingValueObject
{
    public function __construct(
        public readonly int $rating
    ) {}

    public function __toString(): string
    {
        switch($this->rating) {
            case 1:
                return '👍';
            case 2:
                return '🤔';
            case 3:
                return '👎';
            default:
                return 'not rated';
        }
    }
}
