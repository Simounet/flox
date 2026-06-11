<?php

  namespace App\Services;

  use App\Models\CreditCast;
  use App\Models\CreditCrew;
  use App\Enums\MediaTypeEnum;
  use App\Models\Genre;
  use App\Models\Item;
  use Carbon\Carbon;
  use Illuminate\Http\Response;
  use Illuminate\Http\Client\Response as ClientResponse;
  use Illuminate\Support\Collection;
  use Illuminate\Support\Facades\Cache;
  use Illuminate\Support\Facades\Http;
  use GuzzleHttp\Exception\ClientException;
  use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

  class TMDB {
    private const BASE = 'https://api.themoviedb.org';

    private string $apiKey;

    private string $translation;


    public function __construct()
    {
      $this->apiKey = config('services.tmdb.key');
      $this->translation = config('app.locale');
    }

    /**
     * Search TMDb by 'title'.
     */
    public function search(string $title, ?MediaTypeEnum $mediaType = null): Response
    {
      if( ! $title) {
        return response([], HttpFoundationResponse::HTTP_UNPROCESSABLE_ENTITY);
      }

      $tv = collect();
      $movies = collect();

      if( ! $mediaType || $mediaType === MediaTypeEnum::TV) {
        $response = $this->fetchSearch($title, MediaTypeEnum::TV);
        $tv = collect($this->createItems($response, MediaTypeEnum::TV));
      }

      if( ! $mediaType || $mediaType === MediaTypeEnum::MOVIE) {
        $response = $this->fetchSearch($title, MediaTypeEnum::MOVIE);
        $movies = collect($this->createItems($response, MediaTypeEnum::MOVIE));
      }

      $sortedEntries = $movies
        ->merge($tv)
        ->sortByDesc('popularity');

      $withExactTitles = $sortedEntries->filter(function($entry) use ($title) {
        return strtolower($entry['title']) == strtolower($title);
      });

      $rest = $sortedEntries->reject(function($entry) use ($title) {
        return strtolower($entry['title']) == strtolower($title);
      });

      return response($withExactTitles->merge($rest)->values()->all());
    }

    private function fetchSearch(string $title, MediaTypeEnum $mediaType): ClientResponse {
      return $this->requestTmdb(self::BASE . '/3/search/' . $mediaType->value, [
        'query' => $title,
      ]);
    }

    /**
     * Search TMDb for recommendations and similar movies.
     */
    public function suggestions(MediaTypeEnum $mediaType, int $tmdbId): Collection
    {
      $recommendations = $this->searchSuggestions($mediaType, $tmdbId, 'recommendations');
      $similar = $this->searchSuggestions($mediaType, $tmdbId, 'similar');

      $items = $recommendations->merge($similar);

      $inDB = Item::all('tmdb_id')->toArray();

      // Remove movies which already are in database.
      return $items->filter(function($item) use ($inDB) {
        return ! in_array($item['tmdb_id'], array_column($inDB, 'tmdb_id'));
      })->values();
    }

    private function searchSuggestions(MediaTypeEnum $mediaType, int $tmdbId, string $type): Collection
    {
      $response = $this->requestTmdb(self::BASE . '/3/' . $mediaType->value . '/' . $tmdbId . '/' . $type);

      return collect($this->createItems($response, $mediaType));
    }

    /**
     * Search TMDb for upcoming movies in our region.
     *
     * @return array
     */
    public function upcoming()
    {
      $cache = Cache::remember('upcoming', $this->untilEndOfDay(), function() {
        $region = getRegion($this->translation);

        $response = $this->requestTmdb(self::BASE . '/3/movie/upcoming', [
          'region' => $region,
        ]);

        return $this->createItems($response, MediaTypeEnum::MOVIE);
      });

      return $this->filterItems(collect($cache));
    }

    /**
     * Search TMDb for current playing movies in our region.
     *
     * @return array
     */
    public function nowPlaying()
    {
      $cache = Cache::remember('current', $this->untilEndOfDay(), function() {
        $region = getRegion($this->translation);

        $response = $this->requestTmdb(self::BASE . '/3/movie/now_playing', [
          'region' => $region,
        ]);

        return $this->createItems($response, MediaTypeEnum::MOVIE);
      });

      return $this->filterItems(collect($cache));
    }

    /**
     * Search TMDb for current popular movies and tv shows.
     *
     * @return array
     */
    public function trending()
    {
      $cache = Cache::remember('trending', $this->untilEndOfDay(), function() {
        $responseMovies = $this->fetchPopular(MediaTypeEnum::MOVIE);
        $responseTv = $this->fetchPopular(MediaTypeEnum::TV);

        $tv = collect($this->createItems($responseTv, MediaTypeEnum::TV));
        $movies = collect($this->createItems($responseMovies, MediaTypeEnum::MOVIE));

        return $tv->merge($movies)->shuffle()->toArray();
      });

      return $this->filterItems(collect($cache));
    }

    /**
     * Search TMDb by genre.
     */
    public function byGenre(string $genre): array
    {
      $genreId = Genre::findByName($genre)->firstOrFail()->id;

      $cache = Cache::remember('genre-' . $genre, $this->untilEndOfDay(), function() use ($genreId) {

        $responseMovies = $this->requestTmdb(self::BASE . '/3/discover/movie', ['with_genres' => $genreId]);
        $responseTv = $this->requestTmdb(self::BASE . '/3/discover/tv', ['with_genres' => $genreId]);

        $movies = collect($this->createItems($responseMovies, MediaTypeEnum::MOVIE));
        $tv = collect($this->createItems($responseTv, MediaTypeEnum::TV));

        return $tv->merge($movies)->shuffle()->toArray();
      });

      //$inDB = Item::findByGenreId($genreId)->get();

      return $this->filterItems(collect($cache), $genreId);
    }

    /**
     * Merge the response with items from our database.
     */
    private function filterItems(Collection $items, ?int $genreId = null): array
    {
      $allId = $items->pluck('tmdb_id');

      // Get all movies / tv shows that are already in our database.
      $searchInDB = Item::whereIn('tmdb_id', $allId)->with('latestEpisode')->withCount('episodesWithSrc');

      if($genreId) {
        $searchInDB->findByGenreId($genreId);
      }

      $foundInDB = $searchInDB->get()->toArray();

      // Remove them from the TMDb response.
      $filtered = $items->filter(function($item) use ($foundInDB) {
        return ! in_array($item['tmdb_id'], array_column($foundInDB, 'tmdb_id'));
      });

      $merged = $filtered->merge($foundInDB);

      // Reset array keys to display inDB items first.
      return array_values($merged->reverse()->toArray());
    }

    private function fetchPopular(MediaTypeEnum $mediaType)
    {
      return $this->requestTmdb(self::BASE . '/3/' . $mediaType->value . '/popular');
    }

    private function createItems(ClientResponse $response, MediaTypeEnum $mediaType): array
    {
      $items = [];
      $response = $response->object();

      foreach($response->results as $result) {
        $items[] = $this->createItem($result, $mediaType);
      }

      return $items;
    }

    public function createItem(object $data, MediaTypeEnum $mediaType): array
    {
      try {
        $release = Carbon::createFromFormat('Y-m-d',
          isset($data->release_date) ? ($data->release_date ?: Item::FALLBACK_DATE) : ($data->first_air_date ?? Item::FALLBACK_DATE)
        );
      } catch (\Exception $exception) {
        $release = Carbon::createFromFormat('Y-m-d', Item::FALLBACK_DATE);
      }

      $title = $data->name ?? $data->title;

      $item = [
        'tmdb_id' => $data->id,
        'title' => $title,
        'slug' => getSlug($title),
        'original_title' => $data->original_name ?? $data->original_title,
        'poster' => $data->poster_path,
        'media_type' => $mediaType->value,
        'released' => $release->copy()->getTimestamp(),
        'released_datetime' => $release->toString(),
        'genre_ids' => $data->genre_ids,
        'credit_cast' => $data->credit_cast ?? [],
        'credit_crew' => $data->credit_crew ?? [],
        'review' => $data->review ?? [],
        'user_review' => $data->user_review ?? null,
        'genre' => Genre::whereIn('id', $data->genre_ids)->get()->toArray(),
        'episodes' => [],
        'overview' => $data->overview,
        'backdrop' => $data->backdrop_path,
        'homepage' => $data->homepage ?? null,
        'tmdb_rating' => $data->vote_average,
        'popularity' => $data->popularity ?? 0,
      ];

      return $item;
    }

    private function requestTmdb(string $url, array $query = []): ClientResponse
    {
      $query = array_merge([
        'api_key' => $this->apiKey,
        'language' => strtolower($this->translation)
      ], $query);

      try {
        $response = Http::get($url,  $query);

        if($this->hasLimitRemaining($response)) {
          return $response;
        }
      } catch (ClientException $e) {
        // wtf? throws exception because of "bad" statuscode?
        $response = $e->getResponse();

        if($this->hasLimitRemaining($response)) {
          return $response;
        }
      }

      sleep(1);
      return $this->requestTmdb($url, $query);
    }

    /**
     * Get full movie or tv details with trailers.
     */
    public function details(int $tmdbId, MediaTypeEnum $mediaType): object
    {
      $response = $this->requestTmdb(self::BASE . '/3/' . $mediaType->value . '/' . $tmdbId, [
        'append_to_response' => 'videos,external_ids,credits',
      ]);

      if($response->status() !== Response::HTTP_OK) {
        // ignore any error
        return json_decode('{}');
      }

      return $response->object();
    }

    public function videos(int $tmdbId, MediaTypeEnum $mediaType, ?string $translation = null): object
    {
      $response = $this->requestTmdb(self::BASE . '/3/' . $mediaType->value . '/' . $tmdbId . '/videos', [
        'language' => $translation ?? $this->translation,
      ]);

      // TODO: what if it fails? error handling?
      return $response->object();
    }

    /**
     * Get current count of seasons.
     */
    private function tvSeasonsCount(int $id, MediaTypeEnum $mediaType): ?int
    {
      if($mediaType === MediaTypeEnum::TV) {
        $response = $this->requestTmdb(self::BASE . '/3/tv/' . $id);

        $seasons = collect($response->json()['seasons']);

        return $seasons->filter(function ($season) {
          // We don't need pilots
          return $season['season_number'] > 0;
        })->count();
      }

      return null;
    }

    /**
     * Get all episodes of each season.
     */
    public function tvEpisodes(int $tmdbId): object
    {
      $seasons = $this->tvSeasonsCount($tmdbId, MediaTypeEnum::TV);
      $data = [];

      for($i = 1; $i <= $seasons; $i++) {
        $response = $this->requestTmdb(self::BASE . '/3/tv/' . $tmdbId . '/season/' . $i);

        $data[$i] = $response->object();
      }

      return (object) $data;
    }

    /**
     * Make a new request to TMDb to get the alternative titles.
     */
    public function getAlternativeTitles(Item $item): array
    {
      $response = $this->fetchAlternativeTitles($item);

      $body = $response->object();

      if(property_exists($body, 'titles') || property_exists($body, 'results')) {
        return $body->titles ?? $body->results;
      }

      return [];
    }

    public function fetchAlternativeTitles(Item $item): ClientResponse
    {
      return $this->requestTmdb(self::BASE . '/3/' . MediaTypeEnum::from($item['media_type'])->value . '/' . $item['tmdb_id'] . '/alternative_titles');
    }

    /**
     * Get the lists of genres from TMDb for tv shows and movies.
     */
    public function getGenreLists(): array
    {
      $movies = $this->requestTmdb(self::BASE . '/3/genre/movie/list');
      $tv = $this->requestTmdb(self::BASE . '/3/genre/tv/list');

      return [
        'movies' => $movies->object(),
        'tv' => $tv->object(),
      ];
    }

    public function hasLimitRemaining(ClientResponse $response): bool
    {
      if($response->getStatusCode() == 429) {
        return false;
      }

      $rateLimit = $response->header('X-RateLimit-Remaining');

      // Change it on production, good idea...
      // https://www.themoviedb.org/talk/5df7d28326dac100145530f2
      return $rateLimit ? (int) $rateLimit[0] > 1 : true;
    }

    private function untilEndOfDay(): float
    {
      return now()->secondsUntilEndOfDay();
    }
  }
