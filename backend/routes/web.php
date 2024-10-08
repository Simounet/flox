<?php

use Illuminate\Support\Facades\Route;
  use App\Http\Middleware\JsonRequestOnly;

  Route::prefix('api')->group(function() {
    Route::get('/logout', 'UserController@logout');
    Route::post('/login', 'UserController@login');

    Route::get('/items/{type}/{orderBy}/{sortDirection}', 'ItemController@items');
    Route::get('/search-items', 'ItemController@search');

    Route::get('/item/{tmdbId}/{mediaType}', 'SubpageController@item');
    Route::get('/imdb-rating/{imdbId}', 'SubpageController@imdbRating');

    Route::get('/suggestions/{tmdbID}/{mediaType}', 'TMDBController@suggestions');
    Route::get('/genres', 'GenreController@allGenres');
    Route::get('/genre/{genre}', 'TMDBController@genre');
    Route::get('/trending', 'TMDBController@trending');
    Route::get('/upcoming', 'TMDBController@upcoming');
    Route::get('/now-playing', 'TMDBController@nowPlaying');

    Route::patch('/refresh-all', 'ItemController@refreshAll');
    Route::get('/settings', 'SettingController@settings');

    Route::middleware('api_key')->group(function() {
      Route::post('kodi', 'ApiController@kodi');
      Route::post('plex', 'ApiController@plex');
    });

    Route::middleware('auth')->group(function() {
      Route::get('/calendar', 'CalendarController@items');
      Route::get('/check-update', 'SettingController@checkForUpdate');
      Route::get('/episodes/{tmdbId}', 'ItemController@episodes');
      Route::get('/version', 'SettingController@getVersion');
      Route::get('/api-key', 'SettingController@getApiKey');
      Route::patch('/settings/refresh', 'SettingController@updateRefresh');
      Route::patch('/settings/api-key', 'SettingController@generateApiKey');
      Route::patch('/settings/reminders-send-to', 'SettingController@updateRemindersSendTo');
      Route::patch('/settings/reminder-options', 'SettingController@updateReminderOptions');
      Route::patch('/settings', 'SettingController@updateSettings');

      Route::post('/add', 'ItemController@add');
      Route::post('/watchlist', 'ItemController@watchlist');
      Route::patch('/update-alternative-titles/{tmdbId?}', 'ItemController@updateAlternativeTitles');
      Route::patch('/update-genre', 'ItemController@updateGenre');
      Route::patch('/toggle-episode/{id}', 'ItemController@toggleEpisode');
      Route::patch('/toggle-season', 'ItemController@toggleSeason');
      Route::patch('/refresh/{itemId}', 'ItemController@refresh');

      Route::get('/userdata', 'UserController@getUserData');
      Route::patch('/userdata', 'UserController@changeUserData')->middleware('csrf');

      Route::patch('/review/change-rating/{reviewId}', 'ReviewController@changeRating');
      Route::delete('/review/{id}', 'ReviewController@delete')->middleware('csrf');
      Route::post('/review', 'ReviewController@store')->middleware('csrf');

      Route::get('/search-tmdb', 'TMDBController@search');

      Route::post('/fetch-files', 'FileParserController@call');

      Route::get('/video/{type}/{id}', 'VideoController@serve');
    });
  });

  Route::middleware([JsonRequestOnly::class])->group(function () {
      Route::get('/users/{username}/review/{id}', 'ReviewController@showObject')->name('user.review');
  });

  Route::fallback('HomeController@app');
