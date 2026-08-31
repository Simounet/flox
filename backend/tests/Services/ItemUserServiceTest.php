<?php

namespace Tests\Services;

use App\Models\ItemUser;
use App\Services\Models\ItemUserService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\Item;
use App\Services\Models\ItemService;
use Tests\Traits\Factories;
use Tests\Traits\Fixtures;
use Tests\Traits\Mocks;

class ItemUserServiceTest extends TestCase {

    use DatabaseTransactions;
    use Factories;
    use Fixtures;
    use Mocks;

    private $item;
    private $itemService;

    private $itemUser;
    private $itemUserService;

    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();

        $this->item = app(Item::class);
        $this->itemService = app(ItemService::class);

        $this->itemUser = app(ItemUser::class);
        $this->itemUserService = app(ItemUserService::class);

        $this->createStorageDownloadsMock();
        $this->createImdbRatingMock();
    }

    #[Test]
    public function changeItemUserRating(): void
    {
        $updatedRatingValue = 3;

        $item = $this->createMovie();
        $itemUser = $this->createItemUser(['item_id' => $item->id, 'user_id' => $this->user->id]);
        $this->assertTrue($itemUser instanceof ItemUser);

        $ratingChanged = $this->itemUserService->changeRating($item->id, $updatedRatingValue, $this->user->id);
        $this->assertEquals(200, $ratingChanged->getStatusCode());

        $itemUserUpdated = $this->itemUser->find($itemUser->id);

        $this->assertEquals(1, $itemUser->rating);
        $this->assertEquals($updatedRatingValue, $itemUserUpdated->rating);
        $this->assertEquals($itemUser->updated_at, $itemUserUpdated->updated_at);
    }

    #[Test]
    public function removeItemUser()
    {
        $item = $this->createMovie();
        $itemUser = $this->createItemUser(['item_id' => $item->id, 'user_id' => $this->user->id]);

        $itemUser1 = $this->itemUser->find($itemUser->id);
        $this->itemUserService->remove($item->id, $this->user->id);
        $itemUser2 = $this->itemUser->find($itemUser->id);

        $this->assertNotNull($itemUser1);
        $this->assertNull($itemUser2);
    }
}
