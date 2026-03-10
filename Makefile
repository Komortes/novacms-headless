SHELL := /bin/sh
.DEFAULT_GOAL := help

OLLAMA_MODEL ?= qwen2.5:1.5b
OLLAMA_EMBEDDING_MODEL ?= nomic-embed-text

.PHONY: help env php-deps node-deps infra-up infra-down infra-ps key migrate seed-prompts demo-data models up dev smoke smoke-e2e lint schema ci test

help:
	@printf "%s\n" \
	  "NovaCMS developer workflow" \
	  "" \
	  "make up          Prepare .env, install deps if missing, start Docker infra, generate key, migrate, seed prompts" \
	  "make demo-data   Import the bundled demo content dataset" \
	  "make dev         Run local app stack: serve + horizon + reverb + pail + vite" \
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

php-deps:
	@if [ ! -d vendor ]; then composer install; else echo "PHP dependencies already installed"; fi

node-deps:
	@if [ ! -d node_modules ]; then npm install; else echo "Node dependencies already installed"; fi

infra-up:
	@docker compose up -d

infra-down:
	@docker compose down

infra-ps:
	@docker compose ps

key:
	@php artisan key:generate --force

migrate:
	@php artisan migrate --force

seed-prompts:
	@php artisan db:seed --class=PromptSeeder --force

demo-data:
	@php artisan db:seed --class=DemoContentSeeder --force

models:
	@docker compose exec ollama ollama pull $(OLLAMA_MODEL)
	@docker compose exec ollama ollama pull $(OLLAMA_EMBEDDING_MODEL)

up: env php-deps node-deps infra-up key migrate seed-prompts
	@printf "%s\n" \
	  "NovaCMS local stack is prepared." \
	  "Run 'make models' if Ollama models are not present yet." \
	  "Run 'make dev' to start the local application services."

dev: up
	@npx concurrently \
	  -c "#f59e0b,#14b8a6,#fb7185,#60a5fa,#94a3b8" \
	  "php artisan serve" \
	  "php artisan horizon" \
	  "php artisan reverb:start" \
	  "php artisan pail --timeout=0" \
	  "npm run dev" \
	  --names=app,horizon,reverb,logs,vite \
	  --kill-others

smoke: up
	@php artisan stack:smoke

smoke-e2e: up
	@php artisan stack:e2e-smoke

lint:
	@vendor/bin/pint --test

schema:
	@php artisan lighthouse:validate-schema

ci: lint test schema

test:
	@php artisan test
