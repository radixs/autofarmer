COMPOSE   := docker compose
PHP       := $(COMPOSE) exec php
COMPOSER ?= composer
NPM ?= npm
DAYS ?= 30
HOST_UID := $(shell id -u)
HOST_GID := $(shell id -g)

.PHONY: up down docker-up composer-install npm-install build migrate seed logs shell ensure-storage

up: ensure-storage composer-install npm-install build docker-up migrate ## Install deps, build assets, prep dirs, start stack, run migrations

down: ## Stop all containers
	$(COMPOSE) down

docker-up: ## Start containers in detached mode (builds images when needed)
	$(COMPOSE) up -d --build php nginx mysql reverb

composer-install: ## Install PHP dependencies
	$(COMPOSER) install

npm-install: ## Install JS dependencies (resolves peer conflict)
	$(NPM) install --legacy-peer-deps

build: ## Build the production SPA bundle
	$(NPM) run build

ensure-storage: ## Ensure storage/bootstrap directories are host-accessible
	@mkdir -p storage/logs bootstrap/cache
	$(COMPOSE) run --rm -T php sh -c "chown -R $(HOST_UID):$(HOST_GID) storage bootstrap/cache || chown -R www-data:www-data storage bootstrap/cache"
	chmod -R 777 storage bootstrap/cache

migrate: ## Run database migrations inside the php container
	$(PHP) php artisan migrate --force

seed: ## Seed realistic aquarium data (usage: make seed DAYS=45)
	$(PHP) php artisan measurements:seed --days=$(DAYS)

logs: ## Follow docker compose logs
	$(COMPOSE) logs -f

shell: ## Open a shell inside a service (usage: make shell SERVICE=php)
	@if [ -z "$(SERVICE)" ]; then \
		echo "Usage: make shell SERVICE=php"; \
		exit 1; \
	fi
	$(COMPOSE) exec $(SERVICE) sh
