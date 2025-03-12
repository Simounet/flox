<?php

declare(strict_types=1);

namespace Tests\_Fixtures;

use App\Models\Profile;

class CommentFixture {

    public array $data;

    public function __construct(
            private readonly string $action,
            private readonly string $comment,
            private readonly string $objectId,
            private readonly Profile $profile,
            private readonly Profile|\stdClass $remoteProfile,
            private readonly int $reviewId,
            private readonly int $statusId,
    ) {
        $dataStr = str_replace([
            '%COMMENT%',
            '%LOCAL_NAME_AT_DOMAIN%',
            '%LOCAL_PROFILE_URL%',
            '%REMOTE_OBJECT_ID%',
            '%REMOTE_URL%',
            '%REMOTE_NAME%',
            '%REMOTE_DOMAIN%',
            '%REMOTE_STATUS_ID%',
            '%REVIEW_ID%',
        ], [
            $this->comment,
            $this->profile->name . '@' . $this->profile->domain,
            $this->profile->remote_url,
            $this->objectId,
            $this->remoteProfile->remote_url,
            $this->remoteProfile->name,
            $this->remoteProfile->domain,
            (string) $this->statusId,
            (string) $this->reviewId,
        ], file_get_contents(__DIR__ . '/../_Fixtures/fediverse-fake-user/comment-' . $this->action . '.json'));
        $this->data = (array) json_decode($dataStr);
    }

    public function toString(): string
    {
        return json_encode($this->data);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
