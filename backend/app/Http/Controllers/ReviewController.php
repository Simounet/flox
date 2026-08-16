<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReviewResource;
use App\Jobs\ReviewSendActivities;
use App\Models\Profile;
use App\Models\Review;
use App\Services\Fediverse\Activity\ReviewActivity;
use App\Services\Fediverse\Activity\Verbs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReviewController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
      if(!Auth::check()) {
        return response('Invalid credentials.', Response::HTTP_UNAUTHORIZED, ['WWW-Authenticate' => 'Basic']);
      }

      $request->validate([
        'itemId'  => ['required'],
        'rating'  => ['required', 'numeric'],
        'content' => ['required', 'max:255'],
      ]);

      $itemId = $request->input('itemId');
      $rating = $request->input('rating');
      $content = $request->input('content');

      $reviewModel = new Review();
      $storedReview = $reviewModel->store(
          Auth::user()->id,
          $itemId,
          [
              'content' => $content,
              'rating' => $rating
          ]
      );
      $storedReview->load(['itemUser:id,user_id', 'itemUser.user:id,username']);
      $activityType = $storedReview->wasRecentlyCreated ?
          Verbs::CREATE : Verbs::UPDATE;
      ReviewSendActivities::dispatch(
          $activityType,
          $storedReview->id,
          $storedReview->user->username
      );
      return response()->json(['reviewId' => $storedReview->id], Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $review = Review::with('itemUser', 'comments')->findOrFail($id);
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

    public function showObject(Request $request, string $username, string $id)
    {
        $review = Review::findOrFail($id);
        $profile = Profile::where(['username' => $username])->first();
        $reviewActivity = (new ReviewActivity)->activity($review, $profile);

        return response()->json($reviewActivity->toArray(), 200, ['Content-Type' => 'application/activity+json'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }

}
