<?php
// app/Http/Controllers/FileController.php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    /**
     * Загрузить файл
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB максимум
            'name' => 'sometimes|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $uploadedFile = $request->file('file');
            $originalName = $request->input('name', $uploadedFile->getClientOriginalName());

            // Генерируем уникальное имя файла
            $extension = $uploadedFile->getClientOriginalExtension();
            $filename = Str::random(40) . '.' . $extension;

            // Создаем хеш файла для дедупликации
            $hash = hash_file('sha256', $uploadedFile->getRealPath());

            // Проверяем, не существует ли уже такой файл
            $existingFile = File::where('hash', $hash)->first();
            if ($existingFile) {
                return response()->json([
                    'success' => true,
                    'message' => 'Файл уже существует',
                    'data' => [
                        'id' => $existingFile->id,
                        'name' => $existingFile->name,
                        'url' => $existingFile->url,
                        'size' => $existingFile->size,
                        'mime_type' => $existingFile->mime_type,
                        'formatted_size' => $existingFile->formatted_size
                    ]
                ]);
            }

            // Сохраняем файл
            $path = $uploadedFile->storeAs('files', $filename, 'public');

            // Создаем запись в БД
            $file = File::create([
                'name' => $originalName,
                'filename' => $filename,
                'path' => $path,
                'mime_type' => $uploadedFile->getMimeType(),
                'size' => $uploadedFile->getSize(),
                'extension' => $extension,
                'hash' => $hash
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Файл успешно загружен',
                'data' => [
                    'id' => $file->id,
                    'name' => $file->name,
                    'url' => $file->url,
                    'size' => $file->size,
                    'mime_type' => $file->mime_type,
                    'formatted_size' => $file->formatted_size
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки файла: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить информацию о файле
     */
    public function show($id)
    {
        $file = File::find($id);

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'Файл не найден'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $file->id,
                'name' => $file->name,
                'url' => $file->url,
                'size' => $file->size,
                'mime_type' => $file->mime_type,
                'formatted_size' => $file->formatted_size,
                'created_at' => $file->created_at,
                'updated_at' => $file->updated_at
            ]
        ]);
    }

    /**
     * Скачать файл
     */
    public function download($id)
    {
        try {
            Log::info("Запрос на скачивание файла ID: {$id}");

            $file = File::find($id);

            if (!$file) {
                Log::warning("Файл с ID {$id} не найден в базе данных");
                return response()->json([
                    'success' => false,
                    'message' => "Файл с ID {$id} не найден в базе данных"
                ], 404);
            }

            Log::info("Файл найден в БД: {$file->name}, путь: {$file->path}");

            // Полный путь к файлу
            $filePath = storage_path('app/public/' . $file->path);

            Log::info("Проверяем путь к файлу: {$filePath}");

            // Проверяем существование файла
            if (!file_exists($filePath)) {
                Log::error("Файл не найден на диске: {$filePath}");
                return response()->json([
                    'success' => false,
                    'message' => "Файл {$file->name} отсутствует на сервере"
                ], 404);
            }

            Log::info("Файл найден на диске, размер: " . filesize($filePath) . " байт");

            // Отдаем файл для скачивания
            return response()->download($filePath, $file->filename, [
                'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            ]);

        } catch (\Exception $e) {
            Log::error("Критическая ошибка скачивания файла {$id}: {$e->getMessage()}", [
                'exception' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Критическая ошибка скачивания: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удалить файл
     */
    public function destroy($id)
    {
        $file = File::find($id);

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'Файл не найден'
            ], 404);
        }

        try {
            $file->delete();

            return response()->json([
                'success' => true,
                'message' => 'Файл успешно удален'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка удаления файла: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'fileName' => 'sometimes|string|max:255' // alias для name
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = File::find($id);

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'Файл не найден'
                ], 404);
            }

            // Обновляем название файла
            if ($request->has('name')) {
                $file->name = $request->input('name');
            }

            if ($request->has('fileName')) {
                $file->name = $request->input('fileName');
            }

            $file->save();

            return response()->json([
                'success' => true,
                'message' => 'Метаданные файла успешно обновлены',
                'data' => [
                    'id' => $file->id,
                    'name' => $file->name,
                    'url' => $file->url,
                    'size' => $file->size,
                    'mime_type' => $file->mime_type,
                    'formatted_size' => $file->formatted_size,
                    'updated_at' => $file->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка обновления файла: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Очистка неиспользуемых файлов
     */
    public function cleanup()
    {
        // Здесь вы можете реализовать логику очистки неиспользуемых файлов
        // Например, удалить файлы старше определенного времени без связанных объектов

        return response()->json([
            'success' => true,
            'message' => 'Очистка завершена',
            'data' => [
                'deleted_count' => 0,
                'errors' => []
            ]
        ]);
    }
}
