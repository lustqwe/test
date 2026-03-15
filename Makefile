SHELL := /bin/bash

DC := docker compose
APP := laravel.test

.PHONY: up down restart stop install env key migrate storage-link test logs shell ps

up: env install
	$(DC) up -d --build
	$(MAKE) key
	$(MAKE) migrate
	$(MAKE) storage-link

down:
	$(DC) down

stop:
	$(DC) stop

restart: down up

env:
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo ".env created from .env.example"; \
	else \
		echo ".env already exists"; \
	fi

install:
	@if [ ! -f vendor/autoload.php ]; then \
		docker run --rm -u "$$(id -u):$$(id -g)" -v "$$(pwd):/app" -w /app composer:2 composer install; \
	else \
		echo "Composer dependencies already installed"; \
	fi

key:
	@if ! grep -q '^APP_KEY=base64:' .env; then \
		$(DC) exec -T $(APP) php artisan key:generate; \
	else \
		echo "APP_KEY already set"; \
	fi

migrate:
	$(DC) exec -T $(APP) php artisan migrate --force

storage-link:
	$(DC) exec -T $(APP) php artisan storage:link || true

test:
	$(DC) exec -T $(APP) php artisan test

logs:
	$(DC) logs -f --tail=100

shell:
	$(DC) exec $(APP) bash

ps:
	$(DC) ps
