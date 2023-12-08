<?php

use Illuminate\Support\Facades\Route;

  Route::group(['prefix' => 'api'], function() {
    Route::group(['middleware' => 'auth.basic'], function () {
        Route::patch('/update-files', 'FileParserController@receive');
        Route::post('/import', 'ExportImportController@import');
        Route::get('/export', 'ExportImportController@export');
    });

    Route::get('/last-fetched', 'FileParserController@lastFetched');
    Route::get('/review/{id}', 'ReviewController@show');
  });

  Route::get('.well-known/webfinger', 'WebFingerController@handle')->name('well-known.webfinger');
  Route::get('/users/{username}/followers', 'ActorController@followers')->name('federation.user.followers');
  Route::get('/users/{username}/following', 'ActorController@following')->name('federation.user.following');
  Route::post('/users/{username}/inbox', 'ActorController@inbox')->name('federation.user.inbox');
  Route::get('/users/{username}/outbox', 'ActorController@outbox')->name('federation.user.outbox');
  Route::get('/users/{username}', 'ActorController@actor')->name('federation.user');
  Route::post('/inbox', 'ActorController@sharedInbox')->name('federation.shared-inbox');
