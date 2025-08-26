<?php

namespace App\Http\Controllers;

use App\Models\MoodBoard;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MoodBoardController extends Controller
{
    /**
     * Сохранение данных в БД
     */
    public function save(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'boardId' => 'required|string|max:255',
            'boardData' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Некорректные данные',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $boardId = $request->input('boardId');
            $boardData = $request->input('boardData');

            // Очищаем данные изображений от base64, оставляем только imageId
            $cleanedBoardData = $this->cleanImageData($boardData);

            // Создаем или обновляем доску
            $board = MoodBoard::createOrUpdateBoard($boardId, $cleanedBoardData);

            DB::commit();

            \Log::info("MoodBoard saved: {$boardId} (version {$board->version})");

            return response()->json([
                'success' => true,
                'message' => 'Данные успешно сохранены',
                'timestamp' => $board->last_saved_at->toISOString(),
                'version' => $board->version
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('MoodBoard save error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка сохранения данных'
            ], 500);
        }
    }

    /**
     * Загрузка данных из БД
     */
    public function load(string $boardId): JsonResponse
    {
        try {
            $board = MoodBoard::findByBoardId($boardId);

            if (!$board) {
                // Создаем новую доску
                $board = MoodBoard::create([
                    'board_id' => $boardId,
                    'name' => 'New Board',
                    'data' => [
                        'objects' => []
                    ],
                    'settings' => MoodBoard::getDefaultSettings()
                ]);

                $boardData = $board->getFullData();

                return response()->json([
                    'success' => true,
                    'data' => $boardData,
                    'message' => 'Создана новая доска'
                ]);
            }

            // Восстанавливаем URL изображений
            $boardData = $board->getFullData();
            $restoredData = $this->restoreImageUrls($boardData);

            return response()->json([
                'success' => true,
                'data' => $restoredData,
                'message' => 'Данные загружены'
            ]);

        } catch (\Exception $e) {
            \Log::error('MoodBoard load error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки данных'
            ], 500);
        }
    }

    /**
     * Список всех досок
     */
    public function index(): JsonResponse
    {
        try {
            $boards = MoodBoard::select([
                'board_id',
                'name',
                'description',
                'version',
                'last_saved_at',
                'created_at',
                'updated_at'
            ])
                ->orderBy('last_saved_at', 'desc')
                ->get()
                ->map(function ($board) {
                    return [
                        'id' => $board->board_id,
                        'name' => $board->name,
                        'description' => $board->description,
                        'version' => $board->version,
                        'lastSaved' => $board->last_saved_at->toISOString(),
                        'created' => $board->created_at->toISOString(),
                        'objectStats' => $board->getObjectStats()
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $boards
            ]);

        } catch (\Exception $e) {
            \Log::error('MoodBoard index error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения списка досок'
            ], 500);
        }
    }

    /**
     * Получение информации о доске
     */
    public function show(string $boardId): JsonResponse
    {
        try {
            $board = MoodBoard::findByBoardId($boardId);

            if (!$board) {
                return response()->json([
                    'success' => false,
                    'message' => 'Доска не найдена'
                ], 404);
            }

            // Восстанавливаем URL изображений
            $boardData = $board->getFullData();
            $restoredData = $this->restoreImageUrls($boardData);

            return response()->json([
                'success' => true,
                'data' => [
                    'board' => $restoredData,
                    'stats' => $board->getObjectStats()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('MoodBoard show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения данных доски'
            ], 500);
        }
    }

    /**
     * Удаление доски
     */
    public function destroy(string $boardId): JsonResponse
    {
        DB::beginTransaction();

        try {
            $board = MoodBoard::findByBoardId($boardId);

            if (!$board) {
                return response()->json([
                    'success' => false,
                    'message' => 'Доска не найдена'
                ], 404);
            }

            // Получаем ID изображений перед удалением доски
            $imageIds = $this->extractImageIds($board->getFullData());

            // Удаляем доску
            $board->delete();

            // Удаляем неиспользуемые изображения (опционально)
            $this->cleanupUnusedImages($imageIds);

            DB::commit();

            \Log::info("MoodBoard deleted: {$boardId}");

            return response()->json([
                'success' => true,
                'message' => 'Доска удалена'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('MoodBoard delete error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка удаления доски'
            ], 500);
        }
    }

    /**
     * Дублирование доски
     */
    public function duplicate(string $boardId): JsonResponse
    {
        DB::beginTransaction();

        try {
            $originalBoard = MoodBoard::findByBoardId($boardId);

            if (!$originalBoard) {
                return response()->json([
                    'success' => false,
                    'message' => 'Доска не найдена'
                ], 404);
            }

            // Создаем копию
            $newBoard = MoodBoard::create([
                'name' => $originalBoard->name . ' (копия)',
                'description' => $originalBoard->description,
                'data' => $originalBoard->data,
                'settings' => $originalBoard->settings,
            ]);

            DB::commit();

            // Восстанавливаем URL изображений для ответа
            $boardData = $newBoard->getFullData();
            $restoredData = $this->restoreImageUrls($boardData);

            return response()->json([
                'success' => true,
                'data' => $restoredData,
                'message' => 'Доска дублирована'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('MoodBoard duplicate error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка дублирования доски'
            ], 500);
        }
    }

    /**
     * Очищает данные изображений от base64, оставляет только imageId
     *
     * @param array $boardData
     * @return array
     */
    private function cleanImageData(array $boardData): array
    {
        if (!isset($boardData['objects']) || !is_array($boardData['objects'])) {
            return $boardData;
        }

        $cleanedObjects = array_map(function ($obj) {
            if (isset($obj['type']) && $obj['type'] === 'image') {
                $cleanedObj = $obj;

                // Если есть imageId, убираем src (base64)
                if (isset($obj['imageId'])) {
                    if (isset($cleanedObj['properties']['src'])) {
                        unset($cleanedObj['properties']['src']);
                    }
                    if (isset($cleanedObj['src'])) {
                        unset($cleanedObj['src']);
                    }
                }

                return $cleanedObj;
            }
            return $obj;
        }, $boardData['objects']);

        return array_merge($boardData, ['objects' => $cleanedObjects]);
    }

    /**
     * Восстанавливает URL изображений при загрузке
     *
     * @param array $boardData
     * @return array
     */
    private function restoreImageUrls(array $boardData): array
    {
        if (!isset($boardData['objects']) || !is_array($boardData['objects'])) {
            return $boardData;
        }

        $restoredObjects = array_map(function ($obj) {
            if (isset($obj['type']) && $obj['type'] === 'image' &&
                isset($obj['imageId']) &&
                (!isset($obj['properties']['src']) || empty($obj['properties']['src']))) {

                try {
                    // Проверяем, существует ли изображение
                    $image = Image::find($obj['imageId']);
                    if ($image) {
                        $obj['properties'] = $obj['properties'] ?? [];
                        $obj['properties']['src'] = route('images.file', $obj['imageId']);
                    }
                } catch (\Exception $e) {
                    \Log::warning("Не удалось восстановить URL для изображения {$obj['imageId']}: " . $e->getMessage());
                }
            }
            return $obj;
        }, $boardData['objects']);

        return array_merge($boardData, ['objects' => $restoredObjects]);
    }

    /**
     * Извлекает ID изображений из данных доски
     *
     * @param array $boardData
     * @return array
     */
    private function extractImageIds(array $boardData): array
    {
        if (!isset($boardData['objects']) || !is_array($boardData['objects'])) {
            return [];
        }

        $imageIds = [];

        foreach ($boardData['objects'] as $obj) {
            if (isset($obj['type']) && $obj['type'] === 'image' && isset($obj['imageId'])) {
                $imageIds[] = $obj['imageId'];
            }
        }

        return array_unique($imageIds);
    }

    /**
     * Очищает неиспользуемые изображения (опционально)
     * Можно запускать как отдельную задачу в фоне
     *
     * @param array $imageIds
     * @return void
     */
    private function cleanupUnusedImages(array $imageIds): void
    {
        if (empty($imageIds)) {
            return;
        }

        try {
            // Проверяем, используются ли изображения в других досках
            $allBoards = MoodBoard::all();
            $usedImageIds = [];

            foreach ($allBoards as $board) {
                $boardImageIds = $this->extractImageIds($board->getFullData());
                $usedImageIds = array_merge($usedImageIds, $boardImageIds);
            }

            $usedImageIds = array_unique($usedImageIds);
            $unusedImageIds = array_diff($imageIds, $usedImageIds);

            // Удаляем неиспользуемые изображения
            if (!empty($unusedImageIds)) {
                Image::whereIn('id', $unusedImageIds)->delete();
                \Log::info('Cleaned up unused images: ' . count($unusedImageIds));
            }

        } catch (\Exception $e) {
            \Log::error('Error cleaning up images: ' . $e->getMessage());
        }
    }

    /**
     * Получить статистику изображений для доски
     *
     * @param string $boardId
     * @return JsonResponse
     */
    public function getImageStats(string $boardId): JsonResponse
    {
        try {
            $board = MoodBoard::findByBoardId($boardId);

            if (!$board) {
                return response()->json([
                    'success' => false,
                    'message' => 'Доска не найдена'
                ], 404);
            }

            $imageIds = $this->extractImageIds($board->getFullData());
            $images = Image::whereIn('id', $imageIds)->get();

            $stats = [
                'totalImages' => $images->count(),
                'totalSize' => $images->sum('size'),
                'averageSize' => $images->avg('size'),
                'formats' => $images->groupBy('mime_type')->map->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            \Log::error('MoodBoard image stats error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения статистики изображений'
            ], 500);
        }
    }
}
