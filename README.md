# 📬 LARAVEL NEWS SUBSCRIPTIONS API
**RESTful API для управления подписками на новостные категории (v1 и v2)**

---

## 📌 О ПРОЕКТЕ

API разработано для систем управления подписками на новостные рубрики с возможностью:

- ✉️ **Гибкой подписки** пользователей по email на выбранные категории
- 🔐 **Безопасного управления** подписками через токены доступа
- 📊 **Масштабируемой выдачи данных** с пагинацией (limit/offset)
- 🔄 **Мультиформатного ответа** (JSON/XML)

**Ключевые сценарии использования:**

1. **Рассылка новостей (IT-компании, медиа-ресурсы)**
    - Пользователи подписываются на категории (`tech`, `business`, `sports`)
    - Получают уникальные ссылки для отписки
    - Администраторы отслеживают статистику через API

---

## ⚙️ ТЕХНИЧЕСКИЕ ХАРАКТЕРИСТИКИ

### Основной функционал
- ✅ Подписка/отписка на рубрики
- ✅ Управление через JWT-токены
- ✅ Поддержка пагинации
- ✅ Валидация всех входящих параметров
- ✅ Полное тестовое покрытие (PHPUnit)

---

## 🛠 Требования к окружению
- PHP 8.2+
- PostgreSQL 13+
- Composer 2.5+
- Laravel 10

---

## 🚀 Полная инструкция по установке

### 1. Установка зависимостей
```bash
git clone https://github.com/Lisica050521/Laravel-News-Subscriptions-API.git
cd Laravel-News-Subscriptions-API

# Основные зависимости
composer install
composer require doctrine/dbal laravel/sanctum spatie/laravel-xml spatie/array-to-xml

# Проверка драйвера PostgreSQL
php -m | grep pgsql
```
### 2. Настройка базы данных
```bash
# Создание основной БД
psql -U postgres -c "CREATE DATABASE laravel_news;"
psql -U postgres -c "GRANT ALL PRIVILEGES ON DATABASE laravel_news TO ваш_пользователь;"

# Создание тестовой БД
psql -U postgres -c "CREATE DATABASE laravel_news_api_test;"
psql -U postgres -c "GRANT ALL PRIVILEGES ON DATABASE laravel_news_api_test TO ваш_пользователь;"
```

### 3. Настройка окружения
Создайте `.env` файл со следующими параметрами:
```ini
APP_NAME=Laravel
APP_ENV=local
APP_KEY=ваш сгенерированный код
APP_DEBUG=true
APP_URL=http://localhost:8080 (ваш порт)

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=laravel_news
DB_USERNAME=ваш_пользователь
DB_PASSWORD=ваш_пароль

SESSION_DRIVER=cookie
SESSION_DOMAIN=localhost
SANCTUM_STATEFUL_DOMAINS=localhost:8080
SESSION_SECURE_COOKIE=false
```

### 4. Инициализация проекта
```bash
# Генерация ключа
php artisan key:generate

# Миграции и сиды
php artisan migrate
php artisan db:seed

# Настройка Sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate:status | grep personal_access_tokens
```

### 5. Тестовое окружение
Создайте `.env.testing`:
```ini
APP_NAME=Laravel
APP_ENV=testing
APP_KEY=ваш сгенерированный код
APP_DEBUG=true
APP_URL=http://localhost:8080 (ваш порт)

APP_ENV=testing
DB_DATABASE=laravel_news_api_test
SESSION_DRIVER=array
CACHE_DRIVER=array
QUEUE_CONNECTION=sync

SESSION_DRIVER=array
CACHE_DRIVER=array
QUEUE_CONNECTION=sync

```

Инициализация тестовой БД:
```bash
php artisan migrate:fresh --env=testing
php artisan db:seed --env=testing
```

---

## 🔥 Запуск проекта

### Основной режим

```bash
php artisan serve --port=8080
```

### Тестирование
```bash
php artisan test
```

---

## 📚 Документация API

### Версия 1 (базовая)
```http
POST /api/v1/subscriptions
Content-Type: application/json
Authorization: Bearer {token}

{
  "email": "user@example.com",
  "category": "technology"
}
```

### Версия 2 (с ключами отписки)
```http
POST /api/v2/subscriptions
Content-Type: application/json
Authorization: Bearer {token}

{
  "email": "user@example.com",
  "category": "technology",
  "name": "Irina Balerina"
}
```

---

## 🏗 Структура проекта
```
laravel-news-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── V1/
│   │   │   │   │   └── SubscriptionController.php
│   │   │   │   ├── V2/
│   │   │   │   │   └── SubscriptionController.php
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── Controller.php
```

---

## 💡 Важные заметки
1. Для XML-ответов добавьте заголовок:
   ```
   Accept: application/xml
   ```
2. Все тесты используют отдельную БД и не влияют на продакшен
3. Демо-данные включают:
    - 10 тестовых пользователей
    - 5 категорий новостей
    - Примеры подписок
