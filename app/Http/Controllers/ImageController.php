<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image as InterventionImage;

class ImageController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB макс
            'name' => 'string|max:255',
            'width' => 'integer|min:1',
            'height' => 'integer|min:1'
        ]);

        try {
            $file = $request->file('image');
            $hash = md5_file($file->getPathname());

            // Проверяем дубликаты
            $existingImage = Image::where('hash', $hash)->first();
            if ($existingImage) {
                Log::info("Reusing existing image: {$existingImage->id}");

                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $existingImage->id,
                        'url' => route('images.file', $existingImage->id),
                        'name' => $existingImage->name,
                        'width' => $existingImage->width,
                        'height' => $existingImage->height,
                        'size' => $existingImage->size
                    ],
                    'message' => 'Использовано существующее изображение'
                ]);
            }

            // Генерируем уникальное имя файла
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . Str::random(10) . '.' . $extension;
            $path = 'images/' . date('Y/m') . '/' . $filename; // Организуем по папкам год/месяц

            // Создаем директорию если не существует
            $directory = dirname($path);
            if (!Storage::exists($directory)) {
                Storage::makeDirectory($directory);
            }

            // Сохраняем файл
            Storage::put($path, file_get_contents($file));

            // Получаем размеры изображения
            $imageInfo = getimagesize($file->getPathname());
            $width = $request->input('width', $imageInfo[0] ?? 100);
            $height = $request->input('height', $imageInfo[1] ?? 100);

            // Сохраняем в БД
            $image = Image::create([
                'name' => $request->input('name', $file->getClientOriginalName()),
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'width' => $width,
                'height' => $height,
                'hash' => $hash
            ]);

            Log::info("Image uploaded: {$image->id} ({$image->name})");

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $image->id,
                    'url' => route('images.file', $image->id),
                    'name' => $image->name,
                    'width' => $image->width,
                    'height' => $image->height,
                    'size' => $image->size
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Image upload error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $image = Image::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $image->id,
                    'url' => route('images.file', $image->id),
                    'name' => $image->name,
                    'original_name' => $image->original_name,
                    'mime_type' => $image->mime_type,
                    'width' => $image->width,
                    'height' => $image->height,
                    'size' => $image->size,
                    'created_at' => $image->created_at->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Изображение не найдено'
            ], 404);
        }
    }

    public function file($id)
    {
        try {
            $image = Image::findOrFail($id);

            if (!Storage::exists($image->path)) {
                Log::warning("Image file not found: {$image->path}");
                abort(404, 'Файл изображения не найден');
            }

            $filePath = Storage::path($image->path);

            // Для больших файлов используем stream
            if (filesize($filePath) > 2 * 1024 * 1024) { // 2MB
                return response()->stream(function () use ($filePath) {
                    $stream = fopen($filePath, 'rb');
                    fpassthru($stream);
                    fclose($stream);
                }, 200, [
                    'Content-Type' => $image->mime_type,
                    'Content-Length' => filesize($filePath),
                    'Cache-Control' => 'public, max-age=31536000',
                    'ETag' => '"' . md5_file($filePath) . '"'
                ]);
            }

            return response()->file($filePath, [
                'Content-Type' => $image->mime_type,
                'Cache-Control' => 'public, max-age=31536000',
                'ETag' => '"' . md5_file($filePath) . '"'
            ]);

        } catch (\Exception $e) {
            Log::error("Error serving image file {$id}: " . $e->getMessage());
            abort(404, 'Файл изображения не найден');
        }
    }

    public function destroy($id)
    {
        try {
            $image = Image::findOrFail($id);

            // Проверяем, используется ли изображение в досках
            $isUsed = \App\Models\MoodBoard::whereJsonContains('data->objects', function ($query) use ($id) {
                // Этот запрос может быть сложным, лучше проверить через PHP
            })->exists();

            // Альтернативная проверка через PHP
            $boards = \App\Models\MoodBoard::all();
            $isUsed = false;

            foreach ($boards as $board) {
                $boardData = $board->getFullData();
                if (isset($boardData['objects'])) {
                    foreach ($boardData['objects'] as $object) {
                        if (isset($object['type']) && $object['type'] === 'image' &&
                            isset($object['imageId']) && $object['imageId'] === $id) {
                            $isUsed = true;
                            break 2;
                        }
                    }
                }
            }

            if ($isUsed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Нельзя удалить изображение - оно используется в досках'
                ], 409);
            }

            $image->delete();

            Log::info("Image deleted: {$id}");

            return response()->json([
                'success' => true,
                'message' => 'Изображение удалено'
            ]);

        } catch (\Exception $e) {
            Log::error("Error deleting image {$id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка удаления изображения'
            ], 500);
        }
    }

    /**
     * Список всех изображений с пагинацией
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');

            $query = Image::select(['id', 'name', 'original_name', 'mime_type', 'size', 'width', 'height', 'created_at']);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('original_name', 'like', "%{$search}%");
                });
            }

            $images = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Добавляем URL к каждому изображению
            $images->getCollection()->transform(function ($image) {
                $image->url = route('images.file', $image->id);
                return $image;
            });

            return response()->json([
                'success' => true,
                'data' => $images->items(),
                'pagination' => [
                    'current_page' => $images->currentPage(),
                    'last_page' => $images->lastPage(),
                    'per_page' => $images->perPage(),
                    'total' => $images->total()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching images: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения списка изображений'
            ], 500);
        }
    }

    /**
     * Массовое удаление изображений
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string|exists:images,id'
        ]);

        try {
            $ids = $request->input('ids');
            $protectedIds = [];

            // Проверяем, какие изображения используются
            $boards = \App\Models\MoodBoard::all();

            foreach ($boards as $board) {
                $boardData = $board->getFullData();
                if (isset($boardData['objects'])) {
                    foreach ($boardData['objects'] as $object) {
                        if (isset($object['type']) && $object['type'] === 'image' &&
                            isset($object['imageId']) && in_array($object['imageId'], $ids)) {
                            $protectedIds[] = $object['imageId'];
                        }
                    }
                }
            }

            $protectedIds = array_unique($protectedIds);
            $deletableIds = array_diff($ids, $protectedIds);

            $deletedCount = 0;
            if (!empty($deletableIds)) {
                $deletedCount = Image::whereIn('id', $deletableIds)->delete();
            }

            Log::info("Bulk delete: {$deletedCount} images deleted, " . count($protectedIds) . " protected");

            return response()->json([
                'success' => true,
                'message' => "Удалено изображений: {$deletedCount}",
                'deleted_count' => $deletedCount,
                'protected_count' => count($protectedIds),
                'protected_ids' => $protectedIds
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk delete error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка массового удаления: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Очистка неиспользуемых изображений
     */
    public function cleanup(Request $request)
    {
        try {
            // Получаем все ID изображений
            $allImageIds = Image::pluck('id')->toArray();

            // Получаем ID изображений, используемых в досках
            $usedImageIds = [];
            $boards = \App\Models\MoodBoard::all();

            foreach ($boards as $board) {
                $boardData = $board->getFullData();
                if (isset($boardData['objects'])) {
                    foreach ($boardData['objects'] as $object) {
                        if (isset($object['type']) && $object['type'] === 'image' && isset($object['imageId'])) {
                            $usedImageIds[] = $object['imageId'];
                        }
                    }
                }
            }

            $usedImageIds = array_unique($usedImageIds);
            $unusedImageIds = array_diff($allImageIds, $usedImageIds);

            if (empty($unusedImageIds)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Неиспользуемых изображений не найдено'
                ]);
            }

            $deletedCount = Image::whereIn('id', $unusedImageIds)->delete();

            Log::info("Cleanup: {$deletedCount} unused images deleted");

            return response()->json([
                'success' => true,
                'message' => "Очищено неиспользуемых изображений: {$deletedCount}",
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Cleanup error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка очистки: ' . $e->getMessage()
            ], 500);
        }
    }
}
