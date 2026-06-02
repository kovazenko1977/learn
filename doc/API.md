# API Документация wesbooking Pro

## Базовый URL
`http://your-domain.com/api/`

## Аутентификация
Все запросы должны содержать заголовок `X-API-Key: {your_user_token}`. Токен можно найти в профиле пользователя.

## Методы

### 1. Получение списка бронирований
`GET /api/bookings.php?action=list`
**Ответ:** JSON массив объектов бронирования.

### 2. Создание бронирования
`POST /api/bookings.php?action=create`
**Параметры:**
- `room_id` (int)
- `check_in` (Y-m-d H:i)
- `check_out` (Y-m-d H:i)
- `client_name` (string)
- `phone` (string)

### 3. Статус синхронизации
`GET /api/sync.php?action=status`
Возвращает хэши локальных файлов для сравнения версий.

### 4. Изменение статуса уборки
`POST /api/rooms.php?action=set_cleaning_status`
**Параметры:**
- `room_id` (int)
- `status` (string: clean|dirty|cleaning)
