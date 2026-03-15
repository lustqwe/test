# Документация API

## Базовая информация

- Base URL: `http://localhost/api/v1`
- Формат: JSON
- Аутентификация: `Bearer` токен (Laravel Sanctum)
- Защищенные методы требуют заголовок:

```http
Authorization: Bearer <TOKEN>
```

## Быстрый флоу

1. `POST /auth/register` - регистрация и получение токена
2. `GET /auth/me` - проверить авторизацию
3. `GET /profile` - до создания вернется `200` и `data: null`
4. `POST /profile` - создать профиль
5. `PATCH /profile` - обновить профиль
6. `GET /activities` - посмотреть историю действий
7. `POST /files` - загрузить файл, потом `GET /files`, `DELETE /files/{id}`
8. `POST /auth/logout` - завершить текущую сессию (текущий токен инвалидируется)

## Ошибки API

- `401`:

```json
{
  "message": "Unauthenticated."
}
```

- `404` для несуществующего API endpoint:

```json
{
  "message": "Endpoint not found."
}
```

- `405` для неверного HTTP метода:

```json
{
  "message": "Method not allowed for this endpoint.",
  "allowed_methods": ["GET", "HEAD"]
}
```

- `422` для валидации:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["..."]
  }
}
```

## Rate limiting

- `auth` (`/auth/register`, `/auth/login`): `30` запросов/мин на IP
- `auth` (`/auth/register`, `/auth/login`): `10` запросов/мин на `email + IP`
- `api` (защищенные маршруты): `120` запросов/мин на пользователя (или на IP, если пользователь не определен)
- При превышении лимита: `429 Too Many Requests`

## Auth

### POST `/auth/register`

Регистрация пользователя и выдача токена.

Пример запроса:

```json
{
  "name": "Ivan Petrov",
  "email": "ivan@example.com",
  "password": "secret12345",
  "password_confirmation": "secret12345"
}
```

Пример ответа `201`:

```json
{
  "message": "Registered successfully.",
  "token": "1|plainTextToken...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "Ivan Petrov",
    "email": "ivan@example.com",
    "created_at": "2026-03-15T04:00:00.000000Z"
  }
}
```

### POST `/auth/login`

Логин и выдача нового токена.

Пример запроса:

```json
{
  "email": "ivan@example.com",
  "password": "secret12345"
}
```

Пример ответа `200`:

```json
{
  "message": "Logged in successfully.",
  "token": "2|plainTextToken...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "Ivan Petrov",
    "email": "ivan@example.com",
    "created_at": "2026-03-15T04:00:00.000000Z"
  }
}
```

### GET `/auth/me`

Текущий авторизованный пользователь.

Пример ответа `200`:

```json
{
  "user": {
    "id": 1,
    "name": "Ivan Petrov",
    "email": "ivan@example.com",
    "created_at": "2026-03-15T04:00:00.000000Z"
  }
}
```

### POST `/auth/logout`

Логаут текущего токена.

Пример ответа `200`:

```json
{
  "message": "Logged out successfully."
}
```

## Profile

### GET `/profile`

Получить профиль текущего пользователя.

Если профиль еще не создан:

```json
{
  "data": null
}
```

Если профиль существует:

```json
{
  "data": {
    "id": 1,
    "first_name": "Ivan",
    "last_name": "Petrov",
    "phone": "+79991234567",
    "bio": "Backend developer",
    "date_of_birth": null,
    "address": "Krasnoyarsk",
    "created_at": "2026-03-15T04:00:00.000000Z",
    "updated_at": "2026-03-15T04:00:00.000000Z"
  }
}
```

### POST `/profile`

Создать профиль (один профиль на пользователя).

Поля:

- `first_name` - required, string, max 100
- `last_name` - nullable, string, max 100
- `phone` - nullable, string, max 30
- `bio` - nullable, string, max 1000
- `date_of_birth` - nullable, date
- `address` - nullable, string, max 255

Пример запроса:

```json
{
  "first_name": "Ivan",
  "last_name": "Petrov",
  "phone": "+79991234567",
  "bio": "Backend developer",
  "address": "Krasnoyarsk"
}
```

Пример ответа `201`:

```json
{
  "message": "Profile created successfully.",
  "data": {
    "id": 1,
    "first_name": "Ivan",
    "last_name": "Petrov",
    "phone": "+79991234567",
    "bio": "Backend developer",
    "date_of_birth": null,
    "address": "Krasnoyarsk",
    "created_at": "2026-03-15T04:00:00.000000Z",
    "updated_at": "2026-03-15T04:00:00.000000Z"
  }
}
```

Если профиль уже есть: `409`

```json
{
  "message": "Profile already exists."
}
```

### PUT/PATCH `/profile`

Обновление профиля.

Пример запроса:

```json
{
  "bio": "Senior backend developer"
}
```

Пример ответа `200`:

```json
{
  "message": "Profile updated successfully.",
  "data": {
    "id": 1,
    "first_name": "Ivan",
    "last_name": "Petrov",
    "phone": "+79991234567",
    "bio": "Senior backend developer",
    "date_of_birth": null,
    "address": "Krasnoyarsk",
    "created_at": "2026-03-15T04:00:00.000000Z",
    "updated_at": "2026-03-15T04:05:00.000000Z"
  }
}
```

Если профиль не создан: `404`

```json
{
  "message": "Profile not found."
}
```

### DELETE `/profile`

Удалить профиль.

Пример ответа `200`:

```json
{
  "message": "Profile deleted successfully."
}
```

## Activities

### GET `/activities`

История действий текущего пользователя.

Query параметры:

- `per_page` - от `1` до `100`, по умолчанию `15`

Пример ответа `200`:

```json
{
  "data": [
    {
      "id": 5,
      "action": "profile.update",
      "description": "User updated profile.",
      "subject_type": "App\\Models\\Profile",
      "subject_id": 1,
      "metadata": null,
      "ip_address": "172.18.0.1",
      "created_at": "2026-03-15T04:10:00.000000Z"
    }
  ],
  "links": {
    "first": "http://localhost/api/v1/activities?page=1",
    "last": "http://localhost/api/v1/activities?page=3",
    "prev": null,
    "next": "http://localhost/api/v1/activities?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "path": "http://localhost/api/v1/activities",
    "per_page": 1,
    "to": 1,
    "total": 3
  }
}
```

Действия, которые логируются:

- `auth.register`
- `auth.login`
- `auth.logout`
- `profile.create`
- `profile.update`
- `profile.delete`
- `file.upload`
- `file.delete`

## Files

### GET `/files`

Список файлов пользователя (пагинация).

Query параметры:

- `per_page` - от `1` до `100`, по умолчанию `15`

Пример ответа `200`:

```json
{
  "data": [
    {
      "id": 1,
      "original_name": "resume.pdf",
      "disk": "public",
      "path": "uploads/1/abc123.pdf",
      "url": "http://localhost/storage/uploads/1/abc123.pdf",
      "mime_type": "application/pdf",
      "size": 122880,
      "created_at": "2026-03-15T04:15:00.000000Z"
    }
  ],
  "links": {
    "first": "http://localhost/api/v1/files?page=1",
    "last": "http://localhost/api/v1/files?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "http://localhost/api/v1/files",
    "per_page": 1,
    "to": 1,
    "total": 1
  }
}
```

### POST `/files`

Загрузка файла.

- `Content-Type`: `multipart/form-data`
- Поле `file`: required, max `10240 KB`
- Поле `disk`: optional, `public` или `local`

Пример `curl`:

```bash
curl -X POST http://localhost/api/v1/files \
  -H "Authorization: Bearer <TOKEN>" \
  -F "file=@/absolute/path/to/resume.pdf" \
  -F "disk=public"
```

Пример ответа `201`:

```json
{
  "message": "File uploaded successfully.",
  "data": {
    "id": 1,
    "original_name": "resume.pdf",
    "disk": "public",
    "path": "uploads/1/abc123.pdf",
    "url": "http://localhost/storage/uploads/1/abc123.pdf",
    "mime_type": "application/pdf",
    "size": 122880,
    "created_at": "2026-03-15T04:15:00.000000Z"
  }
}
```

### DELETE `/files/{id}`

Удаление файла текущего пользователя.

Пример ответа `200`:

```json
{
  "message": "File deleted successfully."
}
```
