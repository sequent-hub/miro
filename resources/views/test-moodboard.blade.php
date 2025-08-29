<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест пакета MoodBoard (Herd)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        button { padding: 10px 15px; margin: 5px; cursor: pointer; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🧪 Тестирование пакета MoodBoard (Herd)</h1>
    <p><strong>URL проекта:</strong> <code>http://miro.test</code></p>
    
    <div class="test-section">
        <h3>1. Проверка подключения пакета</h3>
        <button onclick="testPackageConnection()">Проверить подключение</button>
        <div id="connection-result"></div>
    </div>
    
    <div class="test-section">
        <h3>2. Тест API маршрутов</h3>
        <button onclick="testMoodboardList()">GET /api/moodboard/list</button>
        <button onclick="testMoodboardCreate()">POST /api/moodboard/save</button>
        <div id="api-result"></div>
    </div>
    
    <div class="test-section">
        <h3>3. Тест загрузки изображения</h3>
        <input type="file" id="test-image" accept="image/*">
        <button onclick="testImageUpload()">Загрузить изображение</button>
        <div id="upload-result"></div>
    </div>

    <script>
        async function testPackageConnection() {
            try {
                const response = await fetch('/test-moodboard-api');
                const data = await response.json();
                
                const resultDiv = document.getElementById('connection-result');
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            <h4>✅ Успешно!</h4>
                            <p>${data.message}</p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h4>❌ Ошибка!</h4>
                            <p>${data.message}</p>
                        </div>
                    `;
                }
            } catch (error) {
                document.getElementById('connection-result').innerHTML = `
                    <div class="error">
                        <h4>❌ Ошибка запроса!</h4>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }

        async function testMoodboardList() {
            try {
                const response = await fetch('/api/moodboard/list');
                const data = await response.json();
                
                const resultDiv = document.getElementById('api-result');
                resultDiv.innerHTML = `
                    <div class="success">
                        <h4>✅ API маршрут работает!</h4>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            } catch (error) {
                document.getElementById('api-result').innerHTML = `
                    <div class="error">
                        <h4>❌ Ошибка API!</h4>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }

        async function testMoodboardCreate() {
            try {
                const testData = {
                    boardId: 'test-' + Date.now(),
                    boardData: {
                        name: 'Тестовая доска',
                        description: 'Доска для тестирования',
                        objects: []
                    }
                };
                
                const response = await fetch('/api/moodboard/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify(testData)
                });
                
                const data = await response.json();
                
                const resultDiv = document.getElementById('api-result');
                resultDiv.innerHTML = `
                    <div class="success">
                        <h4>✅ Создание доски работает!</h4>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            } catch (error) {
                document.getElementById('api-result').innerHTML = `
                    <div class="error">
                        <h4>❌ Ошибка создания доски!</h4>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }

        async function testImageUpload() {
            const fileInput = document.getElementById('test-image');
            const file = fileInput.files[0];
            
            if (!file) {
                alert('Выберите файл для загрузки');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('image', file);
                formData.append('name', 'Тестовое изображение');
                
                const response = await fetch('/api/images/upload', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                const resultDiv = document.getElementById('upload-result');
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            <h4>✅ Загрузка изображения работает!</h4>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h4>❌ Ошибка загрузки!</h4>
                            <p>${data.message}</p>
                        </div>
                    `;
                }
            } catch (error) {
                document.getElementById('upload-result').innerHTML = `
                    <div class="error">
                        <h4>❌ Ошибка запроса!</h4>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }
    </script>
</body>
</html>
