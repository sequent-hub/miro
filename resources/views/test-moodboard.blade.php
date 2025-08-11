<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoodBoard Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="board-id" content="{{ $boardId ?? 'default' }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="container mx-auto py-8">
    <!-- Только контейнер - все остальное делает пакет -->
    <div id="moodboard-container" style="height: 900px; width: 100%;"></div>
</div>
</body>
</html>
