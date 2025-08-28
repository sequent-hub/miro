# Инструкции по деплою и настройке зависимостей

## Проблема с локальными зависимостями

В проекте используется пакет `@sequent-org/moodboard`, который:
- **Локально** подключается с диска `D:/npm-moodboard-futurello`
- **На сервере** должен подключаться как npm пакет

## Файлы конфигурации

### Для локальной разработки (Windows)
- `package.json` - содержит `overrides` для локального пакета
- `vite.config.js` - содержит алиас для локального пути

### Для продакшн сборки
- `package.prod.json` - без локальных зависимостей
- `vite.config.prod.js` - без локальных алиасов

## Команды для разработки

### Локальная разработка
```bash
npm run dev          # Запуск с локальным пакетом
npm run build        # Сборка с локальным пакетом
```

### Продакшн сборка (локально)
```bash
npm run dev:prod     # Запуск с npm пакетом
npm run build:prod   # Сборка с npm пакетом
```

## Автоматический деплой

При деплое через GitHub Actions:
1. Автоматически копируется `package.prod.json` → `package.json`
2. Автоматически копируется `vite.config.prod.js` → `vite.config.js`
3. Устанавливаются зависимости через `npm install`
4. Собираются ассеты через `npm run build`

## Ручная настройка на сервере

Если нужно настроить вручную:

```bash
# Переключиться на продакшн версию
cp package.prod.json package.json
cp vite.config.prod.js vite.config.js

# Установить зависимости
npm install

# Собрать ассеты
npm run build
```

## Структура файлов

```
├── package.json              # Локальная версия с overrides
├── package.prod.json         # Продакшн версия без overrides
├── vite.config.js            # Локальная версия с алиасами
├── vite.config.prod.js       # Продакшн версия без алиасов
└── .github/workflows/deploy.yml  # CI/CD с автоматическим переключением
```

## Устранение проблем

### Ошибка "Failed to resolve import @sequent-org/moodboard"

**Причина**: Vite не может найти локальный пакет

**Решение**: 
1. Убедиться, что путь `D:/npm-moodboard-futurello` существует
2. Проверить, что в `vite.config.js` правильно настроен алиас
3. Перезапустить dev сервер: `npm run dev`

### Пакет не устанавливается на сервере

**Причина**: Локальные пути не работают на Linux

**Решение**:
1. Убедиться, что `package.prod.json` и `vite.config.prod.js` существуют
2. Проверить, что в `deploy.yml` настроено автоматическое переключение
3. Убедиться, что пакет `@sequent-org/moodboard` доступен в npm registry
