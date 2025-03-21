<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReviewResource;
use App\Jobs\ReviewSendActivities;
use App\Models\Profile;
use App\Models\Review;
use App\Services\Fediverse\Activity\ReviewActivity;
use App\Services\Fediverse\Activity\Verbs;
use App\Services\Models\ReviewService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpFoundation\Response;

class ReviewController extends Controller
{

    private $reviewService;

    public function __construct(
        ReviewService $reviewService
    )
    {
      $this->reviewService = $reviewService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    }

    public function changeRating(int $reviewId): Response
    {
      $user = Auth::user();
      abort_if(!$user, 403);

      return $this->reviewService->changeRating(
        $reviewId,
        Request::input('rating'),
        $user->id
    );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
      $itemId = Request::input('itemId');
      $content = Request::input('content');

      if(!$content || !$itemId) {
        return response('Bad request.', Response::HTTP_BAD_REQUEST);
      }

      if(Auth::check() || $request->validate(['content' => 'required|unique:content|max:255'])) {
        $reviewModel = new Review();
        $storedReview = $reviewModel->store(
            Auth::user()->id,
            $itemId,
            [
                'content' => $content
            ]
        );
        $activityType = $storedReview->wasRecentlyCreated ?
            Verbs::CREATE : Verbs::UPDATE;
        ReviewSendActivities::dispatch(
            $activityType,
            $storedReview->id,
            $storedReview->user->username
        );
        return response('Success', Response::HTTP_OK);
      }

      return response('Invalid credentials.', Response::HTTP_UNAUTHORIZED, ['WWW-Authenticate' => 'Basic']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $review = Review::with('item')->findOrFail($id);
        $data = new ReviewResource($review);

        return $data;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Review $review)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(string $id)
    {
        if(!Auth::check()) {
          return response('Bad request.', Response::HTTP_BAD_REQUEST);
        }

        $userId = Auth::id();
        $review = Review::where([
            'id' => $id,
            'user_id' => $userId
        ])->firstOrFail();
        // @todo delete associated content (episodeUser)
        $review->delete();

        if($review->content !== '') {
            ReviewSendActivities::dispatch(
                Verbs::DELETE,
                $review->id,
                $review->user->username
            );
        }

        return response('Success', Response::HTTP_OK);
    }

    public function showObject(Request $request, string $username, string $id)
    {
        $review = Review::findOrFail($id);
        $profile = Profile::where(['username' => $username])->first();
        $reviewActivity = (new ReviewActivity)->activity($review, $profile);

        return response()->json($reviewActivity->toArray(), 200, ['Content-Type' => 'application/activity+json'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }

}
