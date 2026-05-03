SHELL := /bin/sh
.DEFAULT_GOAL := help

OLLAMA_MODEL ?= qwen2.5:1.5b
OLLAMA_EMBEDDING_MODEL ?= nomic-embed-text
COMPOSE ?= docker compose
APP_RUN = $(COMPOSE) run --rm app
NODE_RUN = $(COMPOSE) run --rm node

.PHONY: help env php-deps node-deps infra-up infra-down infra-ps config-clear key migrate seed-prompts demo-data models up dev smoke smoke-e2e lint schema ci test

help:
	@printf "%s\n" \
	  "NovaCMS developer workflow" \
	  "" \
	  "make up          Prepare .env, install deps in Docker, start Docker infra, generate key, migrate, seed prompts" \
	  "make demo-data   Import the bundled demo content dataset" \
	  "make dev         Run Docker app stack: serve + horizon + reverb + pail + vite" \
	  "make models      Pull required Ollama models" \
	  "make smoke       Run infrastructure smoke checks" \
	  "make smoke-e2e   Run live end-to-end smoke scenario" \
	  "make lint        Run Pint in check mode" \
	  "make schema      Validate Lighthouse schema" \
	  "make ci          Run lint + tests + schema validation" \
	  "make infra-down  Stop Docker infrastructure" \
	  "make infra-ps    Show Docker service status" \
	  "make test        Run test suite"

env:
	@if [ ! -f .env ]; then cp .env.example .env && echo "Created .env from .env.example"; else echo ".env already exists"; fi

php-deps: env
	@if [ ! -d vendor ]; then $(APP_RUN) composer install; else echo "PHP dependencies already installed"; fi

node-deps:
	@if [ ! -d node_modules ]; then $(NODE_RUN) npm install; else echo "Node dependencies already installed"; fi

infra-up:
	@$(COMPOSE) up -d db redis ollama

infra-down:
	@$(COMPOSE) down

infra-ps:
	@$(COMPOSE) ps

config-clear:
	@$(APP_RUN) php artisan config:clear

key:
	@$(APP_RUN) php artisan key:generate --force

migrate:
	@$(APP_RUN) php artisan migrate --force

seed-prompts:
	@$(APP_RUN) php artisan db:seed --class=PromptSeeder --force

demo-data:
	@$(APP_RUN) php artisan db:seed --class=DemoContentSeeder --force

models:
	@$(COMPOSE) exec ollama ollama pull $(OLLAMA_MODEL)
	@$(COMPOSE) exec ollama ollama pull $(OLLAMA_EMBEDDING_MODEL)

up: env php-deps node-deps infra-up config-clear key migrate seed-prompts
	@printf "%s\n" \
	  "NovaCMS local stack is prepared." \
	  "Run 'make models' if Ollama models are not present yet." \
	  "Run 'make dev' to start the local application services."

dev: up
	@$(COMPOSE) up app horizon reverb pail node

smoke: up
	@$(APP_RUN) php artisan stack:smoke

smoke-e2e: up
	@$(APP_RUN) php artisan stack:e2e-smoke

lint:
	@$(APP_RUN) vendor/bin/pint --test

schema:
	@$(APP_RUN) php artisan lighthouse:validate-schema

ci: lint test schema

test:
	@$(APP_RUN) php artisan test
