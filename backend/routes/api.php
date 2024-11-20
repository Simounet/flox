<?php

use App\Http\Controllers\ActorController;
use App\Http\Controllers\ExportImportController;
use App\Http\Controllers\FileParserController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WebFingerController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'api'], function() {
    Route::group(['middleware' => 'auth.basic'], function () {
        Route::patch('/update-files', [FileParserController::class, 'receive']);
        Route::post('/import', [ExportImportController::class, 'import']);
        Route::get('/export', [ExportImportController::class, 'export']);
    });

    Route::get('/last-fetched', [FileParserController::class, 'lastFetched']);
    Route::get('/review/{id}', [ReviewController::class, 'show']);
});

Route::get('.well-known/webfinger', [WebFingerController::class, 'handle'])->name('well-known.webfinger');
Route::get('/users/{username}/followers', [ActorController::class, 'followers'])->name('federation.user.followers');
Route::get('/users/{username}/following', [ActorController::class, 'following'])->name('federation.user.following');
Route::post('/users/{username}/inbox', [ActorController::class, 'inbox'])->name('federation.user.inbox');
Route::get('/users/{username}/outbox', [ActorController::class, 'outbox'])->name('federation.user.outbox');
Route::get('/users/{username}', [ActorController::class, 'actor'])->name('federation.user');
Route::post('/inbox', [ActorController::class, 'sharedInbox'])->name('federation.shared-inbox');
