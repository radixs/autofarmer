COMPOSE      := docker compose
PHP          := $(COMPOSE) exec php
COMPOSER     := $(COMPOSE) run --rm -T php composer
NPM         ?= npm
DAYS        ?= 30
MERGE       ?= true
HOST_UID    := $(shell id -u)
HOST_GID    := $(shell id -g)
SENSOR_STATE := $(word 2,$(MAKECMDGOALS))
BRANCH     ?= main

.PHONY: up down docker-up composer-install npm-install build migrate seed logs shell ensure-storage resetdb backupdb restoredb sensor on off pi-deploy key-generate

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

test: ## Run the backend PHPUnit suite inside the php container (usage: make test [TEST_TARGET=tests/Feature/Auth] [TEST_FILTER="UserTest::method"])
	$(PHP) php artisan test $(ARGS)

testf:
	npm run test:unit

seed: ## Seed realistic aquarium data (usage: make seed DAYS=45)
	$(PHP) php artisan measurements:seed --days=$(DAYS)

resetdb: ## Truncate measurement + cache tables without reseeding
	$(PHP) php artisan measurements:reset

backupdb: ## Dump measurements + caches into storage/db_backups
	$(PHP) php artisan measurements:backup

restoredb: ## Restore SQL dump (usage: make restoredb FILE=storage/db_backups/db.sql [MERGE=true|false])
	@if [ -z "$(FILE)" ]; then \
		echo "Usage: make restoredb FILE=storage/db_backups/db.sql [MERGE=true|false]"; \
		exit 1; \
	fi
	$(PHP) php artisan measurements:restore $(FILE) --merge=$(MERGE)

sensor: ## Toggle sensor ingestion (usage: make sensor on|off)
	@if [ -z "$(SENSOR_STATE)" ]; then \
		echo "Usage: make sensor on|off"; \
		exit 1; \
	fi
	$(PHP) php artisan sensor:set $(SENSOR_STATE)

on off:
	@:

logs: ## Follow docker compose logs
	$(COMPOSE) logs -f

shell: ## Open a shell inside a service (usage: make shell SERVICE=php)
	@if [ -z "$(SERVICE)" ]; then \
		echo "Usage: make shell SERVICE=php"; \
		exit 1; \
	fi
	$(COMPOSE) exec $(SERVICE) sh

key-generate: composer-install ## Generate the APP_KEY inside a disposable php container
	$(COMPOSE) run --rm php php artisan key:generate --force

pi-deploy: ## Pull the specified branch and rebuild/start everything on Raspberry Pi (usage: make pi-deploy [BRANCH=main])
	git fetch origin
	git checkout $(BRANCH)
	git pull --ff-only origin $(BRANCH)
	$(MAKE) up
