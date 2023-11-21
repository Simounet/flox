<?php

namespace App\Services\Fediverse\Activity;

use App\Profile;
use App\Review;
use App\Services\Fediverse\HttpSignature;
use Illuminate\Support\Facades\Http;

class NoteActivity
{
    public function activity(Review $review, string $username, array $followersInbox, string $sharedInboxUrl): bool
    {
        $actor = Profile::where('username', $username)->first();
        $created = $review->created_at->toAtomString();
        $updated = $review->updated_at->toAtomString();
        $reviewUrl = $actor->remote_url . '/review/' . $review->id;
        $type = $created === $updated ? 'Create' : 'Update';
        $createId = $actor->remote_url . '#' . strtolower($type) . '/review/' . $review->id;
        $activity = '{
            "@context": "https://www.w3.org/ns/activitystreams",
                "id": "' . $createId . '",
                "type": "' . $type . '",
                "actor": "' . $actor->remote_url . '",

                "object": {
                    "id": "' . $reviewUrl . '",
                    "type": "Note",
                    "published": "' . $created . '",
                    "updated": "' . $updated . '",
                    "attributedTo": "' . $actor->remote_url . '",
                    "content": "<p>' . $review->content . '</p>",
                    "url": "' . $reviewUrl . '",
                    "to": "https://www.w3.org/ns/activitystreams#Public",
                    "cc": ' . json_encode($followersInbox, JSON_UNESCAPED_SLASHES) . '
                }
        }';
        $activity = json_decode($activity);

        $payload = json_encode($activity);
        $headers = (new HttpSignature)->sign(
                $sharedInboxUrl,
                $actor->private_key,
                $actor->key_id_url,
                $payload
                );
        $response = Http::withHeaders($headers)
            ->post($sharedInboxUrl, $activity);
        return $response->successful();
    }
}
