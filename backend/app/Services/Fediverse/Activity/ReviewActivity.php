<?php

namespace App\Services\Fediverse\Activity;

use ActivityPhp\Type;
use ActivityPhp\Type\Extended\Object\Note;
use App\Item;
use App\Profile;
use App\Review;
use App\Services\Models\ReviewService;

class ReviewActivity
{

    public function activity(Review $review, Profile $profile, array $followersInbox = []): Note
    {
        $item = Item::select(['poster', 'rating', 'title'])->where('id', $review->item_id)->firstOrFail();
        $created = $review->created_at->toAtomString();
        $updated = $review->updated_at->toAtomString();
        $reviewUrl = route('user.review', ['username' => $profile->username, 'id' => $review->id]);
        $content = $this->getReviewContent((int) $item->rating, $review->content);
        $poster = $item->getPoster();

        $note = new Note();
        $note->set('id', $reviewUrl);
        $note->set('published', $created);
        $note->set('updated', $updated);
        $note->set('attributedTo', $profile->remote_url);
        $note->set('content', '<p>' . $content . '</p>');
        $note->set('url', $reviewUrl);
        $note->set('to', 'https://www.w3.org/ns/activitystreams#Public');
        if(count($followersInbox)) {
            $note->set('cc', $followersInbox);
        }
        $document = Type::create('Document', [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'url' => $poster['url'],
            'name' => 'Poster ' . $poster['title'],
        ]);
        $note->set('attachment', [$document]);

        return $note;
    }

    private function getReviewContent(int $rating, string $content): string
    {
        $htmlContent = preg_replace("/\r\n|\r|\n/", '<br>', $content);
        $starsRating = (new ReviewService)->getRating($rating);
        $ratingContent = '<br><br>Avis : ' . $starsRating;
        return $htmlContent . $ratingContent;
    }
}
