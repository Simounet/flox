<?php

  namespace App\Services\Models;

  use App\Models\Genre;
  use App\Models\Item;
  use App\Models\Review;
  use App\Services\IMDB;
  use App\Services\Storage;
  use App\Services\Models\PersonService;
  use App\Services\TMDB;
  use App\Jobs\UpdateItem;
  use App\Models\Setting;
  use Carbon\Carbon;
  use Illuminate\Database\Eloquent\Builder;
  use Illuminate\Support\Facades\Auth;
  use Illuminate\Support\Facades\DB;
  use Symfony\Component\HttpFoundation\Response;

  class ItemService {

    private $item;
    private $tmdb;
    private $storage;
    private $alternativeTitleService;
    private $episodeService;
    private $imdb;
    private $setting;
    private $genreService;
    private $personService;
    private $reviewService;

    /**
     * @param Item $item
     * @param TMDB $tmdb
     * @param Storage $storage
     * @param AlternativeTitleService $alternativeTitleService
     * @param EpisodeService $episodeService
     * @param GenreService $genreService
     * @param IMDB $imdb
     * @param Setting $setting
     * @param PersonService $personService
     */
    public function __construct(
      Item $item,
      TMDB $tmdb,
      Storage $storage,
      AlternativeTitleService $alternativeTitleService,
      EpisodeService $episodeService,
      GenreService $genreService,
      IMDB $imdb,
      Setting $setting,
      PersonService $personService,
      ReviewService $reviewService
    ){
      $this->item = $item;
      $this->tmdb = $tmdb;
      $this->storage = $storage;
      $this->alternativeTitleService = $alternativeTitleService;
      $this->episodeService = $episodeService;
      $this->imdb = $imdb;
      $this->setting = $setting;
      $this->genreService = $genreService;
      $this->personService = $personService;
      $this->reviewService = $reviewService;
    }

    /**
     * @param $data
     * @return Item
     */
    public function create(
      array $data,
      int $userId
    ): Item
    {
      DB::beginTransaction();
      $item = $this->createItemInfoIfNotExists($data);
      $this->reviewService->create($item, $userId);
      DB::commit();

      return $item->fresh();
    }

    public function createItemInfoIfNotExists(array $data): Item
    {
      $existingItem = Item::where('tmdb_id', $data['tmdb_id'])
        ->get()
        ->first();

      if($existingItem instanceof Item) {
        return $existingItem;
      }

      // @TODO should use server fetched data instead of client data
      $data = $this->makeDataComplete($data);

      $item = $this->item->store($data);
      $this->personService->storeCredits(
        [
          'cast' => $data['credit_cast'],
          'crew' => $data['credit_crew']
        ]
      );

      $this->episodeService->create($item);
      $this->genreService->sync($item, $data['genre_ids'] ?? []);
      $this->alternativeTitleService->create($item);
      $this->storage->downloadImages($item->poster, $item->backdrop);
      return $item;
    }

    public function findByUser(int $userId, int $tmdbId): Item|null
    {
        $item = $this->item->where(['tmdb_id' => $tmdbId])->first();
        if(!$item) {
          return null;
        }

        $reviews = $this->reviewService->count($item->id, $userId);
        if($reviews === 0) {
          return null;
        }
        return $item;
    }

    /**
     * Search against TMDb and IMDb for more informations.
     * We don't need to get more informations if we add the item from the subpage.
     *
     * @param $data
     * @return array
     */
    public function makeDataComplete(array $data): array
    {
      if(
        ! isset($data['imdb_id'])
        || ! isset($data['credit_cast'])
        || ! isset($data['credit_crew'])
      ) {
        $details = $this->tmdb->details($data['tmdb_id'], $data['media_type']);
        $title = $details->name ?? $details->title;

        // @todo extract
        try {
          $release = Carbon::createFromFormat('Y-m-d',
            isset($details->release_date) ? ($details->release_date ?: Item::FALLBACK_DATE) : ($details->first_air_date ?? Item::FALLBACK_DATE)
          );
        } catch (\Exception $exception) {
          $release = Carbon::createFromFormat('Y-m-d', Item::FALLBACK_DATE);
        }

        $data['title'] = $data['title'] ?? $title;
        // @todo facto
        $data['original_title'] = $details->original_name ?? $details->original_title;
        $data['imdb_id'] = $data['imdb_id'] ?? $this->parseImdbId($details);
        $data['youtube_key'] = $data['youtube_key'] ?? $this->parseYoutubeKey($details, $data['media_type']);
        $data['overview'] = $data['overview'] ?? $details->overview;
        $data['tmdb_rating'] = $data['tmdb_rating'] ?? $details->vote_average;
        $data['backdrop'] = $data['backdrop'] ?? $details->backdrop_path;
        $data['slug'] = $data['slug'] ?? getSlug($title);
        $data['homepage'] = $data['homepage'] ?? $details->homepage;
        $data['credit_cast'] = $data['credit_cast'] ?? $this->personService->castFromTMDB($data['tmdb_id'], $details->credits->cast);
        $data['credit_crew'] = $data['credit_crew'] ?? $this->personService->crewFromTMDB($data['tmdb_id'], $details->credits->crew);
        // @todo extract
        $data['released'] = $release->copy()->getTimestamp();
        $data['released_datetime'] = $release;
        $data['poster'] = $data['poster'] ?? $details->poster_path;
        $genreIds = collect($details->genres)->pluck('id')->all();
        $data['genre_ids'] = $data['genre_ids'] ?? $genreIds;
        $data['genre'] = $data['genre'] ?? Genre::whereIn('id', $genreIds)->get();
        $data['popularity'] = $data['popularity'] ?? ($details->popularity ?? 0);
      }

      $data['imdb_rating'] = $this->parseImdbRating($data);

      return $data;
    }

    /**
     * Refresh informations for all items.
     */
    public function refreshAll()
    {
      logInfo("Refresh all items");
      increaseTimeLimit();

      $this->genreService->updateGenreLists();

      $this->item->orderBy('refreshed_at')->get()->each(function($item) {
        UpdateItem::dispatch($item->id);
      });
    }

    /**
     * Refresh informations for an item.
     * Like ratings, new episodes, new poster and backdrop images.
     *
     * @param $itemId
     *
     * @return Response|false
     */
    public function refresh($itemId)
    {
      logInfo("Start refresh for item [$itemId]");

      $item = $this->item->findOrFail($itemId);

      $details = $this->tmdb->details($item->tmdb_id, $item->media_type);

      $title = $details->name ?? ($details->title ?? null);

      // If TMDb didn't find anything then title will be not set => don't update
      if( ! $title) {
        return false;
      }

      logInfo("Refresh", [$title]);

      $this->storage->removeImages($item->poster, $item->backdrop);

      $imdbId = $item->imdb_id ?? $this->parseImdbId($details);

      $item->update([
        'imdb_id' => $imdbId,
        'youtube_key' => $this->parseYoutubeKey($details, $item->media_type),
        'overview' => $details->overview,
        'tmdb_rating' => $details->vote_average,
        'imdb_rating' => $this->parseImdbRating(['imdb_id' => $imdbId]),
        'backdrop' => $details->backdrop_path,
        'poster' => $details->poster_path,
        'slug' => getSlug($title),
        'title' => $title,
        'homepage' => $details->homepage ?? null,
        'original_title' => $details->original_name ?? $details->original_title,
      ]);

      $this->personService->storeCredits(
        [
          'cast' => $this->personService->castFromTMDB($item->tmdb_id, $details->credits->cast),
          'crew' => $this->personService->crewFromTMDB($item->tmdb_id, $details->credits->crew)
        ]
      );

      $this->episodeService->create($item);
      $this->alternativeTitleService->create($item);

      $this->genreService->sync(
        $item,
        collect($details->genres)->pluck('id')->all()
      );

      $this->storage->downloadImages($item->poster, $item->backdrop);
    }

    /**
     * If the user clicks to fast on adding item,
     * we need to re-fetch the rating from IMDb.
     *
     * @param $data
     *
     * @return float|null
     */
    private function parseImdbRating($data)
    {
      if( ! isset($data['imdb_rating'])) {
        $imdbId = $data['imdb_id'];

        if($imdbId) {
          return $this->imdb->parseRating($imdbId);
        }

        return null;
      }

      // Otherwise we already have the rating saved.
      return $data['imdb_rating'];
    }

    /**
     * TV shows needs an extra append for external ids.
     *
     * @param $details
     * @return mixed
     */
    public function parseImdbId($details)
    {
      return $details->external_ids->imdb_id ?? ($details->imdb_id ?? null);
    }

    /**
     * Get the key for the youtube trailer video. Fallback with english trailer.
     *
     * @param $details
     * @param $mediaType
     * @return string|null
     */
    public function parseYoutubeKey($details, $mediaType)
    {
      if(isset($details->videos->results[0])) {
        return $details->videos->results[0]->key;
      }

      // Try to fetch details again with english language as fallback.
      $videos = $this->tmdb->videos($details->id, $mediaType, 'en');

      return $videos->results[0]->key ?? null;
    }

    /**
     * @param $data
     * @param $mediaType
     * @return Item
     */
    public function createEmpty($data, $mediaType)
    {
      $mediaType = mediaType($mediaType);

      $data = [
        'name' => getFileName($data),
        'src' => $data->changed->src ?? $data->src,
        'subtitles' => $data->changed->subtitles ?? $data->subtitles,
      ];

      return $this->item->storeEmpty($data, $mediaType);
    }

    /**
     * Delete movie or tv show (with episodes and alternative titles).
     * Also remove the poster image file.
     *
     * @param $itemId
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function remove($itemId)
    {
      $item = $this->item->find($itemId);

      if( ! $item) {
        return response('Not Found', Response::HTTP_NOT_FOUND);
      }

      $tmdbId = $item->tmdb_id;

      $item->delete();

      // Delete all related episodes, alternative titles and images.
      $this->episodeService->remove($tmdbId);
      $this->alternativeTitleService->remove($tmdbId);
      $this->storage->removeImages($item->poster, $item->backdrop);
    }

    /**
     * Return all items with pagination.
     *
     * @param $type
     * @param $orderBy
     * @param $sortDirection
     * @return mixed
     */
    public function getWithPagination($type, $orderBy, $sortDirection)
    {
      $filter = $this->getSortFilter($orderBy);

      $items = Item::orderBy($filter, $sortDirection)
        ->with('latestEpisode', 'review', 'userReview')
        ->withCount('episodesWithSrc')
        ->where('user_id', Auth::id())
        ->whereHas('userReview');

      if($type == 'watchlist') {
        $items->findByReviewWatchlist(1);
      } elseif( ! $this->setting->first()->show_watchlist_everywhere) {
        $items->findByReviewWatchlist(0);
      }

      if($type == 'tv' || $type == 'movie') {
        $items->where('media_type', $type);
      }

      return $items->simplePaginate(config('app.LOADING_ITEMS'));
    }

    /**
     * Search for all items by title in our database.
     *
     * @param $title
     * @return mixed
     */
    public function search($title)
    {
      return $this->item->findByTitle($title)->with('latestEpisode')->withCount('episodesWithSrc')->get();
    }

    /**
     * Create a new item from import.
     *
     * @param $item
     */
    public function import($item)
    {
      logInfo("Importing", [$item->title]);

      // Fallback if export was from an older version of flox (<= 1.2.2).
      if( ! isset($item->last_seen_at)) {
        $item->last_seen_at = Carbon::createFromTimestamp($item->created_at);
      }

      // New versions of flox has no genre field anymore.
      if(isset($item->genre)) {
        unset($item->genre);
      }

      // For empty items (from file-parser) we don't need access to details.
      if($item->tmdb_id) {
        $item = $this->makeDataComplete((array) $item);
        $this->storage->downloadImages($item['poster'], $item['backdrop']);
      }

      $item = collect($item)->except('id')->toArray();

      Item::create($item);
    }

    /**
     * See if we can find a item by title, fp_name, tmdb_id or src in our database.
     *
     * If we search from file-parser, we also need to filter the results by media_type.
     * If we have e.g. 'Avatar' as tv show, we don't want results like the 'Avatar' movie.
     *
     * @param $type
     * @param $value
     * @param $mediaType
     * @return mixed
     */
    public function findBy($type, $value, $mediaType = null)
    {
      if($mediaType) {
        $mediaType = mediaType($mediaType);
      }

      switch($type) {
        case 'title':
          return $this->item->findByTitle($value, $mediaType)->first();
        case 'title_strict':
          return $this->item->findByTitleStrict($value, $mediaType)->first();
        case 'fp_name':
          return $this->item->findByFPName($value, $mediaType)->first();
        case 'tmdb_id':
          return $this->item->findByTmdbId($value)->with('latestEpisode')->first();
        case 'tmdb_id_strict':
          return $this->item->findByTmdbIdStrict($value, $mediaType)->with('creditCast', 'creditCrew', 'latestEpisode', 'review', 'userReview')->first();
        case 'src':
          return $this->item->findBySrc($value)->first();
      }

      return null;
    }

    /**
     * Get the correct name from the table for sort filter.
     *
     * @param $orderBy
     * @return string
     */
    private function getSortFilter($orderBy)
    {
      switch($orderBy) {
        case 'last seen':
          return 'reviews.updated_at';
        case 'own rating':
          return 'review.rating';
        case 'title':
          return 'title';
        case 'release':
          return 'released';
        case 'tmdb rating':
          return 'tmdb_rating';
        case 'imdb rating':
          return 'imdb_rating';
      }
    }
  }
