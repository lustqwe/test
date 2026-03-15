# Ответы на вопросы

### Разница между `@extends` и `@include` в Blade

- `@extends` используется для наследования базового лэйаут шаблона
- `@include` используется для вставки частичного шаблона в конкретное место


```blade
@extends('layouts.app')

@section('content')
    <h1>Профиль</h1>
    @include('partials.flash')
@endsection
```

### Что такое сервис-провайдеры и для чего они нужны

Сервис-провайдеры это класс, в котором ты регистрируешь биндинги в контейнере и инициализируешь любую логику при старте
приложения, через методы register() для биндингов и boot() для всего остального

В проекте это видно в `app/Providers/AppServiceProvider.php`, где настроены лимиты `auth` и `api`

### Как работает система маршрутизации в Laravel

принимает запрос, сопоставляет HTTP-метод и URI с маршрутом из routes/web.php или routes/api.php, прогоняет через
middleware и вызывает контроллер или closure, который возвращает ответ

В этом проекте API маршруты сгруппированы под префиксом `/api/v1`

### Разница между `get()` и `first()` в Eloquent

get() возвращает коллекцию всех подходящих записей, first() — только первую запись или null, и его используют
когда ожидается один объект

## 2) База данных и Eloquent ORM

### Миграция для `users` с полями: имя, email, пароль, статус

Создание миграции:

```bash
php artisan make:migration create_users_table
```

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->string('status')->default('active');
    $table->timestamps();
});
```

### Запрос: все активные пользователи с сортировкой по дате регистрации

```php
$users = User::query()
    ->where('status', 'active')
    ->orderByDesc('created_at')
    ->get();
```

Для сортировки от старых к новым: `orderBy('created_at')`

### Отношение "один ко многим" между пользователями и постами

Один пользователь имеет много постов (`User -> hasMany(Post)`), каждый пост принадлежит одному пользователю (`Post -> belongsTo(User)`)

Миграция `posts`:

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->text('body');
    $table->timestamps();
});
```

Модель `User`:

```php
public function posts(): HasMany
{
    return $this->hasMany(Post::class);
}
```

Модель `Post`:

```php
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

```php
$user = User::findOrFail(1);
$posts = $user->posts()->latest()->get();
```
