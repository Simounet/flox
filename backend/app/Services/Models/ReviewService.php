<?php

namespace App\Services\Models;

class ReviewService {

    public function getRating(int $rating): string
    {
        switch($rating) {
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
