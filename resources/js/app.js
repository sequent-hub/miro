// Отладочная информация о загрузке модулей
console.log('🔍 Начинаем загрузку app.js...');
console.log('📍 Текущий URL:', window.location.href);
console.log('🕐 Время загрузки:', new Date().toISOString());
console.log('🌐 User Agent:', navigator.userAgent);

// Проверяем доступность Vite
if (typeof import.meta !== 'undefined') {
    console.log('✅ Vite доступен, import.meta:', import.meta);
} else {
    console.warn('⚠️ Vite не доступен');
}

// Проверяем доступность модуля
let MoodBoard;
let moodboardStyles;

// Проверяем, что npm установил пакет
console.log('🔍 Проверяем node_modules...');
try {
    const fs = await import('fs');
    console.log('✅ fs модуль доступен');
} catch (e) {
    console.log('❌ fs модуль недоступен в браузере');
}

console.log('🔍 Проверяем доступность пакета...');
try {
    // Пробуем динамический импорт с полным путем
    const moodboardModule = await import('/node_modules/@sequent-org/moodboard/src/index.js');
    console.log('✅ Прямой импорт сработал:', moodboardModule);
} catch (e) {
    console.log('❌ Прямой импорт не сработал:', e.message);
}

try {
    console.log('📦 Пытаемся импортировать MoodBoard...');
    const moodboardModule = await import('@sequent-org/moodboard');
    console.log('✅ Модуль MoodBoard загружен:', moodboardModule);
    console.log('🔍 Структура модуля:', Object.keys(moodboardModule));
    console.log('🔍 Тип модуля:', typeof moodboardModule);
    console.log('🔍 Прототип модуля:', moodboardModule.__proto__);
    
    // Проверяем все возможные варианты экспорта
    MoodBoard = moodboardModule.MoodBoard || moodboardModule.default || moodboardModule;
    console.log('✅ MoodBoard класс получен:', MoodBoard);
    console.log('🔍 Тип MoodBoard:', typeof MoodBoard);
    console.log('🔍 Конструктор MoodBoard:', MoodBoard?.constructor);
    
    if (!MoodBoard) {
        throw new Error('MoodBoard класс не найден в модуле');
    }
    
    if (typeof MoodBoard !== 'function') {
        throw new Error(`MoodBoard не является функцией/классом. Тип: ${typeof MoodBoard}`);
    }
} catch (importError) {
    console.error('❌ Ошибка импорта MoodBoard:', importError);
    console.error('Детали ошибки:', {
        message: importError.message,
        stack: importError.stack,
        name: importError.name
    });
    
    // Показываем ошибку пользователю
    showError(`Ошибка загрузки модуля: ${importError.message}`);
    return;
}

try {
    console.log('🎨 Пытаемся импортировать стили...');
    await import('@sequent-org/moodboard/style.css');
    console.log('✅ Стили загружены');
} catch (styleError) {
    console.warn('⚠️ Ошибка загрузки стилей:', styleError);
    // Стили не критичны, продолжаем
}

document.addEventListener('DOMContentLoaded', async () => {
    console.log('🚀 DOM загружен, начинаем инициализацию MoodBoard...');

    try {
        // Получаем ID доски (можно настроить под свои нужды)
        const boardId = getBoardId();
        console.log('🆔 ID доски получен:', boardId);

        // Проверяем контейнер
        const container = document.getElementById('moodboard-container');
        console.log('📦 Контейнер найден:', container);

        if (!container) {
            throw new Error('Контейнер #moodboard-container не найден на странице');
        }

        // Создаем MoodBoard с автосохранением
        console.log('🔧 Создаем экземпляр MoodBoard...');
        const moodboard = new MoodBoard('#moodboard-container', {
            boardId: boardId,
            saveEndpoint: '/api/moodboard/save',
            loadEndpoint: '/api/moodboard/load',
            theme: 'light'
        });
        console.log('✅ MoodBoard экземпляр создан:', moodboard);

        // Для отладки (как у вас было)
        window.moodboard = moodboard;
        console.log('🌐 MoodBoard добавлен в window.moodboard');

        // Настраиваем обработчики событий автосохранения
        setupEventHandlers(moodboard);
        console.log('✅ Обработчики событий настроены');

        console.log('🎉 MoodBoard успешно инициализирован с автосохранением');

    } catch (error) {
        console.error('❌ Ошибка инициализации MoodBoard:', error);
        console.error('Детали ошибки:', {
            message: error.message,
            stack: error.stack,
            name: error.name
        });
        showError(`Не удалось загрузить редактор: ${error.message}`);
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
       // console.log('💾 Данные сохранены:', data.timestamp);
    });

    // Обработка ошибок сохранения
    moodboard.coreMoodboard.eventBus.on('save:error', (data) => {
       // console.error('❌ Ошибка сохранения:', data.error);

        // Можно показать пользователю уведомление
        if (data.retryCount >= 3) {
            showError('Не удается сохранить данные. Проверьте подключение к интернету.');
        }
    });

    // Обработка статуса сохранения (для отладки)
    moodboard.coreMoodboard.eventBus.on('save:status-changed', (data) => {
      //  console.log(`📊 Статус сохранения: ${data.status}`);
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


