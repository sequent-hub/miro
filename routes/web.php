<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Http\Controllers\MoodBoardController;

Route::get('/', function () {
    return view('moodboard');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

// Тестовые маршруты для проверки пакета moodboard
Route::get('/test-moodboard', function () {
    return view('test-moodboard');
});

Route::get('/test-moodboard-api', function () {
    // Проверяем, что модели пакета доступны
    try {
        $moodboardClass = \Futurello\MoodBoard\Models\MoodBoard::class;
        $imageClass = \Futurello\MoodBoard\Models\Image::class;
        $fileClass = \Futurello\MoodBoard\Models\File::class;
        
        return response()->json([
            'success' => true,
            'message' => 'Пакет moodboard успешно подключен!',
            'models' => [
                'MoodBoard' => $moodboardClass,
                'Image' => $imageClass,
                'File' => $fileClass
            ],
            'routes' => [
                'moodboard_list' => url('/api/moodboard/list'),
                'moodboard_save' => url('/api/moodboard/save'),
                'images_upload' => url('/api/images/upload'),
                'files_upload' => url('/api/files/upload')
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Ошибка подключения пакета: ' . $e->getMessage()
        ], 500);
    }
});

// API маршруты для moodboard
Route::prefix('api/moodboard')->group(function () {
    Route::post('/save', [MoodBoardController::class, 'save']);
    Route::get('/load/{boardId}', [MoodBoardController::class, 'load']);
    Route::get('/list', [MoodBoardController::class, 'index']);
    Route::get('/show/{boardId}', [MoodBoardController::class, 'show']);
    Route::delete('/delete/{boardId}', [MoodBoardController::class, 'destroy']);
    Route::post('/duplicate/{boardId}', [MoodBoardController::class, 'duplicate']);
});

Route::get('/test', function () {
    return 'Вот теперь точно должно все работать!';
});

require __DIR__.'/auth.php';
