<?php

namespace App\Http\Controllers;

use App\Models\MoodBoard;
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

            // Создаем или обновляем доску
            $board = MoodBoard::createOrUpdateBoard($boardId, $boardData);

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

                return response()->json([
                    'success' => true,
                    'data' => $board->getFullData(),
                    'message' => 'Создана новая доска'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $board->getFullData(),
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

            return response()->json([
                'success' => true,
                'data' => [
                    'board' => $board->getFullData(),
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
        try {
            $board = MoodBoard::findByBoardId($boardId);

            if (!$board) {
                return response()->json([
                    'success' => false,
                    'message' => 'Доска не найдена'
                ], 404);
            }

            $board->delete();

            \Log::info("MoodBoard deleted: {$boardId}");

            return response()->json([
                'success' => true,
                'message' => 'Доска удалена'
            ]);

        } catch (\Exception $e) {
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

            return response()->json([
                'success' => true,
                'data' => $newBoard->getFullData(),
                'message' => 'Доска дублирована'
            ]);

        } catch (\Exception $e) {
            \Log::error('MoodBoard duplicate error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка дублирования доски'
            ], 500);
        }
    }
}
