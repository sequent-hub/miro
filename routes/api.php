<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MoodBoardController;
use App\Http\Controllers\ImageController;

// API маршруты для moodboard
Route::prefix('moodboard')->group(function () {
    Route::post('/save', [MoodBoardController::class, 'save']);
    Route::get('/load/{boardId}', [MoodBoardController::class, 'load']);
    Route::get('/{boardId}', [MoodBoardController::class, 'load']); // Новый: для совместимости с frontend
    Route::get('/list', [MoodBoardController::class, 'index']);
    Route::get('/show/{boardId}', [MoodBoardController::class, 'show']);
    Route::delete('/delete/{boardId}', [MoodBoardController::class, 'destroy']);
    Route::post('/duplicate/{boardId}', [MoodBoardController::class, 'duplicate']);

    // Новый: статистика изображений для доски
    Route::get('/{boardId}/images/stats', [MoodBoardController::class, 'getImageStats']);
});

// Изображения
Route::prefix('images')->group(function () {
    Route::post('/upload', [ImageController::class, 'upload']);
    Route::get('/{id}', [ImageController::class, 'show']);
    Route::get('/{id}/file', [ImageController::class, 'file'])->name('images.file');
    Route::delete('/{id}', [ImageController::class, 'destroy']);

    // Дополнительные маршруты для управления изображениями
    Route::get('/', [ImageController::class, 'index']); // Список всех изображений
    Route::post('/bulk-delete', [ImageController::class, 'bulkDelete']); // Массовое удаление
    Route::post('/cleanup', [ImageController::class, 'cleanup']); // Очистка неиспользуемых
});

Route::post('/test', function (){
    return 1111;
});
