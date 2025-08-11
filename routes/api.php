<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MoodBoardController;

// API маршруты для moodboard
Route::prefix('moodboard')->group(function () {
    Route::post('/save', [MoodBoardController::class, 'save']);
    Route::get('/load/{boardId}', [MoodBoardController::class, 'load']);
    Route::get('/list', [MoodBoardController::class, 'index']);
    Route::get('/show/{boardId}', [MoodBoardController::class, 'show']);
    Route::delete('/delete/{boardId}', [MoodBoardController::class, 'destroy']);
    Route::post('/duplicate/{boardId}', [MoodBoardController::class, 'duplicate']);
});

Route::post('/test', function (){
    return 1111;
});
