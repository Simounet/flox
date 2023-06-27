<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReviewResource;
use App\Review;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
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
      $itemId = Request::input('itemId');
      $content = Request::input('content');

      if(!$content || !$itemId) {
        return response('Bad request.', Response::HTTP_BAD_REQUEST);
      }

      if(Auth::check() || $request->validate(['content' => 'required|unique:content|max:255'])) {
        $reviewModel = new Review();
        $reviewModel->store(Auth::user()->id, $itemId, $content);
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
    public function destroy(Review $review)
    {
        //
    }
}
