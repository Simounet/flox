<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\FileParserController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SubpageController;
use App\Http\Controllers\TMDBController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VideoController;
use App\Http\Middleware\JsonRequestOnly;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function() {
    Route::get('/logout', [UserController::class, 'logout']);
    Route::post('/login', [UserController::class, 'login']);

    Route::get('/items/{type}/{orderBy}/{sortDirection}', [ItemController::class, 'items']);
    Route::get('/search-items', [ItemController::class, 'search']);

    Route::get('/item/{tmdbId}/{mediaType}', [SubpageController::class, 'item']);
    Route::get('/imdb-rating/{imdbId}', [SubpageController::class, 'imdbRating']);

    Route::get('/suggestions/{tmdbID}/{mediaType}', [TMDBController::class, 'suggestions']);
    Route::get('/genres', [GenreController::class, 'allGenres']);
    Route::get('/genre/{genre}', [TMDBController::class, 'genre']);
    Route::get('/trending', [TMDBController::class, 'trending']);
    Route::get('/upcoming', [TMDBController::class, 'upcoming']);
    Route::get('/now-playing', [TMDBController::class, 'nowPlaying']);

    Route::patch('/refresh-all', [ItemController::class, 'refreshAll']);
    Route::get('/settings', [SettingController::class, 'settings']);

    Route::middleware('api_key')->group(function() {
        Route::post('kodi', [ApiController::class, 'kodi']);
        Route::post('plex', [ApiController::class, 'plex']);
    });

    Route::middleware('auth')->group(function() {
        Route::get('/calendar', [CalendarController::class, 'items']);
        Route::get('/check-update', [SettingController::class, 'checkForUpdate']);
        Route::get('/episodes/{tmdbId}', [ItemController::class, 'episodes']);
        Route::get('/version', [SettingController::class, 'getVersion']);
        Route::get('/api-key', [SettingController::class, 'getApiKey']);
        Route::patch('/settings/refresh', [SettingController::class, 'updateRefresh']);
        Route::patch('/settings/api-key', [SettingController::class, 'generateApiKey']);
        Route::patch('/settings/reminders-send-to', [SettingController::class, 'updateRemindersSendTo']);
        Route::patch('/settings/reminder-options', [SettingController::class, 'updateReminderOptions']);
        Route::patch('/settings', [SettingController::class, 'updateSettings']);

        Route::post('/add', [ItemController::class, 'add']);
        Route::post('/watchlist', [ItemController::class, 'watchlist']);
        Route::patch('/update-alternative-titles/{tmdbId?}', [ItemController::class, 'updateAlternativeTitles']);
        Route::patch('/update-genre', [ItemController::class, 'updateGenre']);
        Route::patch('/toggle-episode/{id}', [ItemController::class, 'toggleEpisode']);
        Route::patch('/toggle-season', [ItemController::class, 'toggleSeason']);
        Route::patch('/refresh/{itemId}', [ItemController::class, 'refresh']);

        Route::get('/userdata', [UserController::class, 'getUserData']);
        Route::patch('/userdata', [UserController::class, 'changeUserData'])->middleware('csrf');

        Route::patch('/review/change-rating/{reviewId}', [ReviewController::class, 'changeRating']);
        Route::delete('/review/{id}', [ReviewController::class, 'delete'])->middleware('csrf');
        Route::post('/review', [ReviewController::class, 'store'])->middleware('csrf');

        Route::get('/search-tmdb', [TMDBController::class, 'search']);

        Route::post('/fetch-files', [FileParserController::class, 'call']);

        Route::get('/video/{type}/{id}', [VideoController::class, 'serve']);
    });
});

Route::middleware([JsonRequestOnly::class])->group(function () {
    Route::get('/users/{username}/review/{id}', [ReviewController::class, 'showObject'])->name('user.review');
});

Route::fallback([HomeController::class, 'app']);
