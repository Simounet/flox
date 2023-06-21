<?php

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
