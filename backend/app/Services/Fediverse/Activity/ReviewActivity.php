<?php

namespace App\Services\Fediverse\Activity;

use ActivityPhp\Type;
use ActivityPhp\Type\Extended\Object\Note;
use App\Item;
use App\Review;
use App\Services\Models\ReviewService;
use App\User;
use Illuminate\Support\Facades\Request;

class ReviewActivity
{

    public function reviewObject(Request $request, Review $review): Note
    {
        $url = $request::url();
        $item = Item::select(['poster', 'rating', 'title'])->where('id', $review->item_id)->firstOrFail();
        $username = User::select('username')->where('id', $review->user_id)->value('username');
        $content = $this->getReviewContent($item->rating, $review->content);
        $poster = $item->getPoster();

        $document = Type::create('Document', [
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'url' => $poster['url'],
                'name' => $poster['title'],
        ]);

        $note = new Note();
        $note->set('@context', 'https://www.w3.org/ns/activitystreams');
        $note->set('id', $url);
        $note->set('content', $content);
        $note->set('attributedTo', route('federation.user', ['username' => $username]));
        $note->set('published', $review->created_at->toAtomString());
        $note->set('to', ['https://www.w3.org/ns/activitystreams#Public']);
        $note->set('cc', [route('federation.user.followers', ['username' => $username])]);
        $note->set('attachment', [$document]);

        return $note;
    }

    private function getReviewContent(int $rating, string $content): string
    {
        $htmlContent = preg_replace("/\r\n|\r|\n/", '<br/>', $content);
        $starsRating = (new ReviewService)->getRating($rating);
        $ratingContent = '<br/><br/>Note: ' . $starsRating;
        return $htmlContent . $ratingContent;
    }
}
