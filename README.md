# URL Shortener Service

Сервис для сокращения URL-адресов с возможностью перенаправления на оригинальные ссылки.

## Архитектура

Сервис построен на микросервисной архитектуре с использованием следующих компонентов:
- **PHP 8.4** - основной язык программирования
- **Nginx** - веб-сервер
- **MySQL 8.0** - база данных
- **Redis** - кэширование
- **RabbitMQ** - очередь сообщений

## Быстрый старт

### 1. Настройка базы данных

Перед запуском сервиса необходимо создать базу данных и таблицу:

```bash
# Подключение к MySQL
docker exec -it url_shortener_db mysql -u root -p

# Создание базы данных
CREATE DATABASE url_shortener CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Создание таблицы
USE url_shortener;
CREATE TABLE urls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_url TEXT NOT NULL,
    short_code VARCHAR(10) UNIQUE NOT NULL,
    clicks INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_short_code (short_code),
    INDEX idx_original_url (original_url(255))
);
```

### 2. Запуск сервиса

```bash
# Запустить все сервисы
docker-compose up -d

# Проверить статус контейнеров
docker-compose ps
```

### 3. Доступ к сервисам

- **Основное приложение**: http://localhost:8876
- **MySQL**: localhost:8104 (root/root)
- **RabbitMQ Management**: http://localhost:15672
- **Redis Insight**: http://localhost:8001

## API Эндпоинты

### Создание короткой ссылки

**POST** `/api/shorten`

Создает короткую ссылку для указанного URL.

**Тело запроса:**
```json
{
    "url": "https://example.com/very/long/url"
}
```

**Ответ при успехе:**
```json
{
    "short_url": "http://localhost:8876/abc123",
    "short_code": "abc123",
    "original_url": "https://example.com/very/long/url"
}
```

**Ответ при ошибке:**
```json
{
    "error": "Invalid URL provided"
}
```

### Перенаправление на оригинальный URL

**GET** `/{shortCode}`

Выполняет перенаправление на оригинальный URL и увеличивает счетчик кликов.

**Пример:**
```
GET http://localhost:8876/abc123
```

*Возвращает 301 редирект на оригинальный URL*

## Примеры использования

### С помощью curl

```bash
# Создание короткой ссылки
curl -X POST http://localhost:8876/api/shorten \
  -H "Content-Type: application/json" \
  -d '{"url": "https://google.com"}'

# Проверка перенаправления
curl -I http://localhost:8876/abc123
```

### С помощью JavaScript

```javascript
// Создание короткой ссылки
async function createShortUrl(originalUrl) {
    const response = await fetch('http://localhost:8876/api/shorten', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ url: originalUrl })
    });
    
    const data = await response.json();
    return data.short_url;
}

// Использование
createShortUrl('https://example.com')
    .then(shortUrl => console.log('Короткая ссылка:', shortUrl));
```

### С помощью Python

```python
import requests

# Создание короткой ссылки
def create_short_url(original_url):
    response = requests.post(
        'http://localhost:8876/api/shorten',
        json={'url': original_url}
    )
    return response.json()

# Использование
result = create_short_url('https://example.com')
print(f"Короткая ссылка: {result['short_url']}")
```

## Структура проекта

```
url_shortener/
├── app/                    # Основное приложение
│   ├── src/
│   │   ├── Controllers/    # Контроллеры
│   │   ├── Models/         # Модели данных
│   │   ├── Services/       # Бизнес-логика
│   │   └── Router.php      # Маршрутизация
│   ├── public/
│   │   └── index.php       # Точка входа
│   └── composer.json       # Зависимости PHP
├── _docker/                # Конфигурация Docker
│   ├── nginx/              # Конфигурация Nginx
│   ├── app/                # Dockerfile для PHP
│   └── redis/              # Данные Redis
└── docker-compose.yaml     # Оркестрация контейнеров
```

## Разработка

### Локальная разработка

1. Убедитесь, что установлены Docker и Docker Compose
2. Склонируйте репозиторий
3. Запустите `docker-compose up -d`
4. Приложение будет доступно на http://localhost:8876

### Добавление новых функций

- Контроллеры находятся в `app/src/Controllers/`
- Модели данных в `app/src/Models/`
- Бизнес-логика в `app/src/Services/`
- Новые маршруты добавляются в `app/src/Router.php`

## Мониторинг

### Проверка логов

```bash
# Логи всех сервисов
docker-compose logs

# Логи конкретного сервиса
docker-compose logs app
docker-compose logs nginx
docker-compose logs db
```

### Управление базой данных

```bash
# Подключение к MySQL
docker exec -it url_shortener_db mysql -u root -p

# Просмотр таблиц
USE url_shortener;
SHOW TABLES;

# Просмотр структуры таблицы
DESCRIBE urls;

# Просмотр данных
SELECT * FROM urls ORDER BY created_at DESC LIMIT 10;

# Статистика
SELECT COUNT(*) as total_urls, SUM(clicks) as total_clicks FROM urls;
```

### Redis Insight

Доступ к интерфейсу Redis Insight для мониторинга Redis:
http://localhost:8001

### RabbitMQ Management

Доступ к панели управления RabbitMQ:
http://localhost:15672
(По умолчанию: guest/guest)

## Траблшутинг

### Проблемы с запуском

1. **Порты уже заняты**: Измените порты в `docker-compose.yaml`
2. **Проблемы с правами доступа**: Проверьте права на папку `tmp/`
3. **MySQL не запускается**: Очистите папку `tmp/db/` и перезапустите

### Проверка работоспособности

```bash
# Проверить статус всех контейнеров
docker-compose ps

# Проверить доступность API
curl -f http://localhost:8876/api/shorten -X POST -d '{"url":"https://test.com"}' -H "Content-Type: application/json"
```

## Безопасность

- Все входные URL валидируются
- Используются подготовленные выражения для работы с БД
- Настроены заголовки безопасности в Nginx
- Отключено кэширование для динамического контента

## Лицензия

Проект распространяется под лицензией MIT.