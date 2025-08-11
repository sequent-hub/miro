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
    // 1. Из data-атрибута контейнера
    const container = document.getElementById('moodboard-container');
    const dataId = container?.getAttribute('data-board-id');
    if (dataId) return dataId;

    // 2. Из URL (например: /boards/uXjVJdaJhdk)
    const urlMatch = window.location.pathname.match(/\/boards\/([a-zA-Z0-9_-]+)/);
    if (urlMatch) return urlMatch[1];

    // 3. Из meta тега
    const metaId = document.querySelector('meta[name="board-id"]')?.getAttribute('content');
    if (metaId) return metaId;

    // 4. Генерируем новый короткий ID
    return generateShortId();
}

function generateShortId() {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = '';
    for (let i = 0; i < 11; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
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


