<?php

namespace Database\Seeders;

use App\Models\MoodBoard;
use Illuminate\Database\Seeder;

class MoodBoardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем тестовую доску настроения с указанным board_id
        MoodBoard::create([
            'board_id' => 'OMiJ8YdfdPY',
            'name' => 'Тестовая доска настроения',
            'description' => 'Доска для тестирования функциональности приложения',
            'data' => [
                'name' => 'Тестовая доска настроения',
                'description' => 'Доска для тестирования функциональности приложения',
                'objects' => [
                    [
                        'id' => 'obj_1',
                        'type' => 'image',
                        'x' => 100,
                        'y' => 100,
                        'width' => 200,
                        'height' => 150,
                        'rotation' => 0,
                        'src' => '/storage/images/test-image-1.jpg',
                        'alt' => 'Тестовое изображение 1'
                    ],
                    [
                        'id' => 'obj_2',
                        'type' => 'text',
                        'x' => 350,
                        'y' => 120,
                        'width' => 300,
                        'height' => 100,
                        'rotation' => 0,
                        'content' => 'Это тестовый текст на доске настроения',
                        'fontSize' => 24,
                        'fontFamily' => 'Arial',
                        'color' => '#333333'
                    ],
                    [
                        'id' => 'obj_3',
                        'type' => 'shape',
                        'x' => 200,
                        'y' => 300,
                        'width' => 150,
                        'height' => 150,
                        'rotation' => 45,
                        'shapeType' => 'rectangle',
                        'fillColor' => '#FF6B6B',
                        'strokeColor' => '#FF5252',
                        'strokeWidth' => 2
                    ]
                ],
                'metadata' => [
                    'createdBy' => 'test-user',
                    'tags' => ['тест', 'демо', 'настроение'],
                    'category' => 'дизайн'
                ]
            ],
            'settings' => [
                'backgroundColor' => '#F8F9FA',
                'grid' => [
                    'type' => 'line',
                    'size' => 25,
                    'visible' => true,
                    'color' => '#E9ECEF'
                ],
                'zoom' => [
                    'min' => 0.1,
                    'max' => 5.0,
                    'default' => 1.0
                ],
                'canvas' => [
                    'width' => 2500,
                    'height' => 2000
                ],
                'theme' => 'light',
                'snapToGrid' => true
            ],
            'version' => 1,
            'last_saved_at' => now()
        ]);

        // Создаем еще несколько тестовых досок для разнообразия
        MoodBoard::create([
            'board_id' => 'TestBoard123',
            'name' => 'Вторая тестовая доска',
            'description' => 'Дополнительная доска для тестирования',
            'data' => [
                'name' => 'Вторая тестовая доска',
                'description' => 'Дополнительная доска для тестирования',
                'objects' => [
                    [
                        'id' => 'obj_1',
                        'type' => 'image',
                        'x' => 50,
                        'y' => 50,
                        'width' => 180,
                        'height' => 120,
                        'rotation' => 0,
                        'src' => '/storage/images/test-image-2.jpg',
                        'alt' => 'Тестовое изображение 2'
                    ]
                ],
                'metadata' => [
                    'createdBy' => 'test-user',
                    'tags' => ['тест', 'дополнительно'],
                    'category' => 'архитектура'
                ]
            ],
            'settings' => [
                'backgroundColor' => '#E3F2FD',
                'grid' => [
                    'type' => 'dot',
                    'size' => 30,
                    'visible' => true,
                    'color' => '#BBDEFB'
                ],
                'zoom' => [
                    'min' => 0.1,
                    'max' => 5.0,
                    'default' => 1.0
                ],
                'canvas' => [
                    'width' => 2000,
                    'height' => 1500
                ],
                'theme' => 'light',
                'snapToGrid' => false
            ],
            'version' => 1,
            'last_saved_at' => now()
        ]);
    }
}
