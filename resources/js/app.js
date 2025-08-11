import { MoodBoard } from '@futurello/moodboard';
import '@futurello/moodboard/style.css';

document.addEventListener('DOMContentLoaded', async () => {
    console.log('🚀 Инициализация MoodBoard...');

    try {
        // Получаем ID доски (можно настроить под свои нужды)
        const boardId = getBoardId();

        // Создаем MoodBoard с автосохранением
        const moodboard = new MoodBoard('#moodboard-container', {
            boardId: boardId,
            saveEndpoint: '/api/moodboard/save',
            loadEndpoint: '/api/moodboard/load',
            theme: 'light'
        });

        // Для отладки (как у вас было)
        window.moodboard = moodboard;

        // Настраиваем обработчики событий автосохранения
        setupEventHandlers(moodboard);

        console.log('✅ MoodBoard успешно инициализирован с автосохранением');

    } catch (error) {
        console.error('❌ Ошибка инициализации MoodBoard:', error);
        showError('Не удалось загрузить редактор. Попробуйте обновить страницу.');
    }
});

/**
 * Получение ID доски
 */
function getBoardId() {
    // Пробуем получить из data-атрибута контейнера
    const container = document.getElementById('moodboard-container');
    const dataId = container?.getAttribute('data-board-id');
    if (dataId) return dataId;

    // Пробуем получить из URL (например: /boards/123)
    const urlMatch = window.location.pathname.match(/\/boards\/(\w+)/);
    if (urlMatch) return urlMatch[1];

    // Получаем из meta тега
    const metaId = document.querySelector('meta[name="board-id"]')?.getAttribute('content');
    if (metaId) return metaId;

    // По умолчанию
    return 'default-board';
}

/**
 * Настройка обработчиков событий
 */
function setupEventHandlers(moodboard) {
    // Обработка успешного сохранения
    moodboard.coreMoodboard.eventBus.on('save:success', (data) => {
        console.log('💾 Данные сохранены:', data.timestamp);
    });

    // Обработка ошибок сохранения
    moodboard.coreMoodboard.eventBus.on('save:error', (data) => {
        console.error('❌ Ошибка сохранения:', data.error);

        // Можно показать пользователю уведомление
        if (data.retryCount >= 3) {
            showError('Не удается сохранить данные. Проверьте подключение к интернету.');
        }
    });

    // Обработка статуса сохранения (для отладки)
    moodboard.coreMoodboard.eventBus.on('save:status-changed', (data) => {
        console.log(`📊 Статус сохранения: ${data.status}`);
    });

    // Предотвращение случайного закрытия с несохраненными изменениями
    window.addEventListener('beforeunload', (e) => {
        const saveManager = moodboard.coreMoodboard?.saveManager;
        if (saveManager?.hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = 'У вас есть несохраненные изменения. Вы действительно хотите покинуть страницу?';
            return e.returnValue;
        }
    });
}

/**
 * Показ ошибки пользователю
 */
function showError(message) {
    // Простое уведомление (можно заменить на ваш UI)
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #f87171;
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        font-family: system-ui;
        font-size: 14px;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    notification.textContent = message;

    document.body.appendChild(notification);

    // Убираем через 5 секунд
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}

// В app.js после создания moodboard добавьте тест
console.log('🧪 Тестируем сохранение напрямую...');

// Простой тест POST запроса
fetch('/api/test', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({
        boardId: 'test',
        boardData: { objects: [] }
    })
})
    .then(response => {
        console.log('✅ Тест ответ:', response.data);
        return response.json();
    })
    .then(data => console.log('✅ Тест данные:', data))
    .catch(error => console.error('❌ Тест ошибка:', error));
