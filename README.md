# API личного кабинета

## Быстрый старт

```bash
make up
```

Базовый URL API: `http://localhost/api/v1`

Расширенная документация API: [`docs/API.md`](docs/API.md)
Ответы на теоретические вопросы: [`docs/LARAVEL_QA.md`](docs/LARAVEL_QA.md)

## База данных

- host: `127.0.0.1`
- port: `3307`
- database: `laravel`
- user: `sail`
- password: `password`

## API роуты

Все роуты под префиксом `/api/v1`.

- Auth:
  - `POST /auth/register`
  - `POST /auth/login`
  - `GET /auth/me`
  - `POST /auth/logout`
- Profile:
  - `GET /profile`
  - `POST /profile`
  - `PUT /profile`
  - `PATCH /profile`
  - `DELETE /profile`
- Activity:
  - `GET /activities`
- Files:
  - `GET /files`
  - `POST /files` (`multipart/form-data`, `file` до 10 MB, `disk=public|local`)
  - `DELETE /files/{id}`

## Проверка в Postman

1. Вызвать `POST /auth/register` или `POST /auth/login`
2. Взять `token` из ответа
3. Передавать заголовок `Authorization: Bearer <token>`
4. Дергать защищенные эндпоинты

## Rate limiting

- `auth`: 30 запросов/мин на IP и 10 запросов/мин на `email+IP`
- защищенные API: 120 запросов/мин на пользователя

## Тесты

```bash
make test
```
