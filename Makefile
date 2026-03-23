SHELL := /bin/sh
.DEFAULT_GOAL := help

OLLAMA_MODEL ?= qwen2.5:1.5b
OLLAMA_EMBEDDING_MODEL ?= nomic-embed-text

.PHONY: help env php-deps node-deps infra-up infra-down infra-ps key migrate seed-prompts demo-data models demo demo-down demo-models demo-token demo-reset demo-check demo-logs demo-wait up dev smoke smoke-e2e lint schema ci test

help:
	@printf "%s\n" \
	  "NovaCMS developer workflow" \
	  "" \
	  "make up          Prepare .env, install deps if missing, start Docker infra, generate key, migrate, seed prompts" \
	  "make demo        Build and run the product demo in Docker with seeded users and content" \
	  "make demo-down   Stop the Docker demo stack" \
	  "make demo-models Pull Ollama models for live generation inside the Docker demo" \
	  "make demo-token  Print a read-only GraphQL token for the Docker demo" \
	  "make demo-check  Validate demo runtime plus seeded users, content, and prompts" \
	  "make demo-reset  Rebuild the demo DB state and restore seeded walkthrough data" \
	  "make demo-logs   Tail Docker logs for the demo stack" \
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

demo:
	@docker compose --profile demo up --build -d
	@$(MAKE) demo-wait
	@printf "%s\n" \
	  "NovaCMS demo is ready in Docker." \
	  "Open http://localhost:8000 for the landing page." \
	  "Open http://localhost:8000/admin and sign in as admin@novacms.test / password." \
	  "Run 'make demo-models' if you want live Ollama generation." \
	  "Run 'make demo-check' to validate the seeded scenario and runtime." \
	  "Run 'make demo-logs' if you want to watch the demo services." \
	  "Run 'make demo-down' to stop the demo."

demo-down:
	@docker compose --profile demo down

demo-wait:
	@docker/demo/wait-for-health.sh demo-app 180
	@docker/demo/wait-for-health.sh demo-horizon 180
	@docker/demo/wait-for-health.sh demo-reverb 180

demo-models:
	@docker compose exec ollama ollama pull $(OLLAMA_MODEL)
	@docker compose exec ollama ollama pull $(OLLAMA_EMBEDDING_MODEL)

demo-token:
	@docker compose --profile demo up -d db redis ollama demo-app
	@docker/demo/wait-for-health.sh demo-app 180
	@docker compose --profile demo exec -T demo-app php artisan api-token:create admin@novacms.test demo-readonly --ability=graphql:read-internal --expires-days=30

demo-check:
	@docker compose --profile demo up -d db redis ollama demo-app demo-horizon demo-reverb
	@$(MAKE) demo-wait
	@docker compose --profile demo exec -T demo-app php artisan demo:check

demo-reset:
	@docker compose --profile demo up -d db redis ollama demo-app demo-reverb
	@docker/demo/wait-for-health.sh demo-app 180
	@docker compose --profile demo stop demo-horizon >/dev/null 2>&1 || true
	@docker compose --profile demo exec -T demo-app php artisan demo:reset --force
	@docker compose --profile demo up -d demo-horizon
	@docker/demo/wait-for-health.sh demo-horizon 180
	@docker compose --profile demo exec -T demo-app php artisan demo:check

demo-logs:
	@docker compose --profile demo logs -f demo-app demo-horizon demo-reverb db redis ollama

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
