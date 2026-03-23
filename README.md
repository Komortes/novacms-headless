# NovaCMS

[![CI](https://img.shields.io/github/actions/workflow/status/Komortes/novacms-headless/ci.yml?branch=main&label=CI)](https://github.com/Komortes/novacms-headless/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.4%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![License](https://img.shields.io/badge/License-MIT-0f766e.svg)](https://opensource.org/licenses/MIT)

NovaCMS is a headless CMS with built-in AI summarization and content operations.  
It gives content teams one product surface for structured content, async AI generation, prompt governance, and API delivery.

## Product Positioning

NovaCMS is not “just Laravel with a CMS table”.

It is designed as:
- a headless CMS for API-first content delivery
- an editorial workspace for markdown content and AI-assisted review
- an operations surface for queue state, failed runs, and runtime health
- a prompt-governed AI layer where output contracts can be versioned and activated

The default product story is:
1. create or edit markdown content
2. run AI summarization in the background
3. review TL;DR, bullets, FAQ, tags, and meta description
4. publish through a headless API for frontend consumers

## Demo In 3 Minutes

Run the full product demo in Docker:

```bash
make demo
```

Then open:
- landing page: `http://localhost:8000`
- admin: `http://localhost:8000/admin`
- GraphQL endpoint: `http://localhost:8000/graphql`

Seeded demo accounts:

| Role | Email | Password | What to explore |
|---|---|---|---|
| Admin | `admin@novacms.test` | `password` | Full product view: content, prompts, AI settings, API access, runtime pages |
| Editor | `editor@novacms.test` | `password` | Editorial workflow: drafts, AI review, publish path |
| Operator | `operator@novacms.test` | `password` | Queue Center, System Health, runtime posture |

The Docker demo ships with ready content and AI summaries so the UI is immediately usable.  
If you want live local generation inside the demo, pull models after the stack is up:

```bash
make demo-models
```

Useful demo operations:

```bash
# validate runtime plus seeded users/content/prompt baseline
make demo-check

# rebuild the database and restore the demo story
make demo-reset

# print a read-only GraphQL token for demo consumers
make demo-token

# tail demo services when startup or runtime needs inspection
make demo-logs
```

`make demo-check` validates the seeded walkthrough and runtime sockets.  
`make demo-models` is only required when you want live Ollama generation inside the demo.

Supporting demo assets:
- walkthrough script: [docs/demo-script.md](/Users/oleksandrskoruk/projects/novacms-headless/docs/demo-script.md)
- developer quickstart: [docs/graphql-api.md](/Users/oleksandrskoruk/projects/novacms-headless/docs/graphql-api.md)

## What To Click First

For a quick product walkthrough:

1. open the landing page to understand the product story
2. sign in as `admin@novacms.test`
3. open `Content Workspace` and inspect the seeded published records and failed draft
4. open `View Content` to see generated TL;DR, bullets, FAQ, tags, and editorial state together
5. open `Prompt Registry` to see versioned prompt governance
6. open `API Access` to issue a client token for GraphQL consumers
7. open `AI Settings`, `Queue Center`, and `System Health` to see the runtime side of the product

## What NovaCMS Provides

- content management for `Post` and `Page`
- async AI summarization pipeline
- per-run provider/model selection in admin actions
- central AI settings page for provider defaults and API credentials
- real-time generation status updates
- semantic search with vector embeddings
- versioned prompt management for controlled AI outputs
- runtime smoke checks and health dashboard for ops visibility

## Development Workflow

Use the local stack when you want the full runtime, including queue workers, Reverb, Redis, PostgreSQL, and Ollama.

```bash
# prepare local environment and infrastructure
make up

# import demo content for a usable admin walkthrough
make demo-data

# pull required local models
make models

# run app, horizon, reverb, logs, and vite
make dev

# run formatting, tests, and GraphQL schema validation
make ci
```

If you only need a quick backend quality gate, `make lint`, `make test`, and `make schema` can be run independently.

## System Architecture

```text
Filament Admin / API Client
           |
           v
      Content Write
           |
           v
   content_hash invalidation
           |
           v
 Redis Queue -> GenerateContentSummaryJob -> AI Provider
           |                                  (Ollama/OpenAI)
           v
  content_ai_summaries + metadata
           |
           v
   Reverb broadcast events
           |
           v
Realtime status in admin/API consumers
```

## Technology Stack

| Layer | Technology | Purpose |
|---|---|---|
| Backend | Laravel 12, PHP 8.4+ | Core domain, jobs, events, API integration |
| Database | PostgreSQL 16 | Relational content storage |
| Vector Search | pgvector | Embeddings storage and similarity queries |
| Queue/Cache | Redis + Predis | Async jobs and cache backend |
| Queue Monitoring | Horizon | Worker management and queue visibility |
| Realtime | Reverb | WebSocket broadcasting for status updates |
| Admin | Filament | Content operations and editorial UI |
| API | Lighthouse GraphQL | Schema-driven content API |
| AI (default) | Ollama | Local LLM inference without external costs |

## How It Works

### 1. Content Lifecycle
- editor creates or updates content (`Post` or `Page`)
- system computes `content_hash` from content fields
- if hash changed, AI summary status becomes `pending`

### 2. Async AI Processing
- on create/update with changed `content_hash`, summary is marked `pending` and job is auto-queued
- `GenerateContentSummaryJob` is pushed to Redis queue
- worker sets status to `generating`
- for long text, map-reduce summarization strategy is applied
- generated output includes:
  - TL;DR
  - bullet points
  - meta description
  - FAQ
  - tags
- summary record is updated and status becomes `ready` (or `failed` with error details)

### 3. Realtime Status
- on status changes, domain events are broadcast via Reverb
- admin UI can render live transitions:
  - `pending -> generating -> ready`
  - `pending -> generating -> failed`
- primary events:
  - `content.updated`
  - `summary.generated`
  - `summary.failed`
  - `summary.cancelled`
  - `embedding.created`

### 4. Semantic Search (v1.1)
- content embeddings are stored in PostgreSQL (`pgvector`)
- supported operations:
  - `semanticSearch(query, locale, status, type, min_score)`
  - `relatedContent(contentId, locale, status, type, min_score)`
- embeddings are generated asynchronously on content create/update
- reindex supports:
  - incremental backfill for stale or missing embeddings
  - full rebuild for provider/model migrations or search-quality resets

## Data Model

### `contents`
- `id`
- `type` (`post`, `page`)
- `slug`
- `title`
- `body` (markdown)
- `locale`
- `status` (`draft`, `published`, `archived`)
- `content_hash`
- timestamps

### `content_ai_summaries`
- `content_id`
- `summary_tldr`
- `summary_bullets` (json)
- `summary_meta_description`
- `summary_faq` (json)
- `summary_tags` (json)
- `status` (`pending`, `generating`, `ready`, `failed`)
- `model`
- `prompt_version`
- `tokens_in`
- `tokens_out`
- `last_error`
- timestamps

### `prompts`
- `name`
- `version`
- `template`
- `parameters`
- `is_active`

### `content_embeddings`
- `content_id`
- `source` (`body`, `summary`, etc.)
- `chunk_index`
- `content_hash`
- `provider`
- `model`
- `dimensions`
- `embedding` (`vector(n)` in PostgreSQL with pgvector)
- `meta` (json)
- timestamps

## Prompt and Model Governance

- prompt templates are versioned in `prompts`
- generated summaries store:
  - prompt version
  - model name
  - token usage (`tokens_in`, `tokens_out`)
- this provides reproducibility and traceability for AI output changes
- prompt versions can be managed from Filament (`/admin/prompts`) and activated without code changes

## Local Infrastructure

`docker-compose.yml` runs:
- PostgreSQL (`pgvector/pgvector:pg16`)
- Redis (`redis:7-alpine`)
- Ollama (`ollama/ollama:latest`)

## Configuration

```env
REDIS_CLIENT=predis
QUEUE_CONNECTION=redis
CACHE_STORE=redis

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=local
REVERB_APP_KEY=localkey
REVERB_APP_SECRET=localsecret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

AI_PROVIDER=ollama
OLLAMA_BASE_URL=http://127.0.0.1:11434
OLLAMA_MODEL=qwen2.5:1.5b
AI_EMBEDDINGS_PROVIDER=ollama
AI_EMBEDDINGS_MODEL=nomic-embed-text
AI_EMBEDDINGS_DIMENSIONS=1024
AI_EMBEDDINGS_CHUNK_CHARS=1200
AI_EMBEDDINGS_MAX_CHUNKS=32
AI_EMBEDDINGS_AUTO_DISPATCH=true

AI_HTTP_RETRY_TIMES=2
AI_HTTP_RETRY_SLEEP_MS=250
AI_RATE_LIMIT_PER_MINUTE=60
AI_SUMMARY_QUEUE=ai
AI_SUMMARY_JOB_TRIES=3
AI_SUMMARY_JOB_TIMEOUT=300
AI_SUMMARY_JOB_BACKOFF_SECONDS=15
AI_EMBEDDINGS_QUEUE=ai
AI_EMBEDDINGS_JOB_TRIES=3
AI_EMBEDDINGS_JOB_TIMEOUT=300
AI_EMBEDDINGS_JOB_BACKOFF_SECONDS=15

DOMAIN_EVENTS_BROADCAST_ENABLED=true
DOMAIN_EVENTS_BROADCAST_CHANNEL=novacms.domain-events
DOMAIN_EVENTS_STREAM_ENABLED=true
DOMAIN_EVENTS_STREAM_NAME=novacms:domain-events
DOMAIN_EVENTS_STREAM_MAXLEN=10000
```

## Run Locally

```bash
# one-time setup for dependencies + infra + key + migrations + prompts
make up

# import the bundled demo dataset for a usable admin walkthrough
make demo-data

# pull local Ollama models if they are not available yet
make models

# start app server, horizon, reverb, logs, and vite
make dev
```

Underlying manual steps:

```bash
cp .env.example .env
composer install
npm install
docker compose up -d
php artisan key:generate
php artisan migrate
php artisan db:seed --class=PromptSeeder --force
php artisan serve
php artisan horizon
php artisan reverb:start
```

Pull model in Ollama:

```bash
docker compose exec ollama ollama pull qwen2.5:1.5b
docker compose exec ollama ollama pull nomic-embed-text
```

Reindex embeddings:

```bash
# queue only stale or missing embeddings
php artisan content:reindex-embeddings

# queue a full rebuild for all content
php artisan content:reindex-embeddings --mode=full

# sync single content by slug
php artisan content:reindex-embeddings sample-post --sync
```

Summary generation:

```bash
# queue generation (default)
php artisan content:generate-summary sample-post --provider=ollama --model=qwen2.5:1.5b

# force synchronous generation (debug/local)
php artisan content:generate-summary sample-post --sync --provider=ollama --model=qwen2.5:1.5b
```

Content bundle workflow:

```bash
# import the bundled demo dataset
php artisan content:import --demo

# export current content to a reusable JSON bundle
php artisan content:export

# export only published english content
php artisan content:export --status=published --locale=en

# import a custom bundle back into the system
php artisan content:import storage/app/exports/content-bundle-20260310-120000.json
```

Demo dataset notes:

- includes published records with ready summaries so the admin UI is useful immediately
- includes one failed summary to make Queue Center retry flows visible on first run
- does not ship embeddings, so run `php artisan content:reindex-embeddings --mode=full` after import if you want semantic search populated from the dataset

Prompt registry workflow:

- use `/admin/prompts` to create and edit prompt versions
- use `Activate` action to switch the active version per prompt name
- use `/admin/prompts/compare` to review template and parameter differences
- use `Export all`, `Export active`, and row-level `Export` for JSON bundles
- use `Import JSON` to upsert exported bundles back into the registry

External API tokens:

```bash
# issue a bearer token for external GraphQL clients
php artisan api-token:create test@example.com external-client --ability=graphql:write --ability=graphql:read-internal

# inspect issued tokens for a user
php artisan api-token:list test@example.com

# revoke a token by id
php artisan api-token:revoke 1
```

Failed jobs (DLQ-style operational flow):

```bash
php artisan queue:failed
php artisan queue:retry all
php artisan queue:flush
```

Runtime smoke checks:

```bash
# human-readable output
php artisan stack:smoke

# machine-readable JSON (CI friendly)
php artisan stack:smoke --json

# via Makefile wrapper
make smoke
```

Live end-to-end smoke:

```bash
# validates DB + Redis + Ollama, runs queued summary + embeddings, then checks semantic search
php artisan stack:e2e-smoke

# keep the generated smoke record for manual inspection
php artisan stack:e2e-smoke --keep-records

# require full local runtime, including Horizon and Reverb
php artisan stack:e2e-smoke --require-horizon --require-reverb

# via Makefile wrapper
make smoke-e2e
```

Quality checks:

```bash
# formatting in check mode
make lint

# GraphQL schema validation
make schema

# local CI-equivalent run
make ci
```

## API Endpoints

- GraphQL: `/graphql`
- Admin panel: `/admin`
- AI settings page: `/admin/settings/ai`
- System health page: `/admin/system-health`
- Prompt registry: `/admin/prompts`
- Prompt compare: `/admin/prompts/compare`
- API guide: [docs/graphql-api.md](/Users/oleksandrskoruk/projects/novacms-headless/docs/graphql-api.md)

### Semantic GraphQL Queries

```graphql
query {
  semanticSearch(
    query: "headless cms with ai",
    limit: 5,
    locale: "en",
    status: PUBLISHED,
    type: POST,
    min_score: 0.65
  ) {
    score
    content {
      id
      slug
      title
    }
  }
}
```

```graphql
query {
  relatedContent(
    content_id: 1,
    limit: 5,
    locale: "en",
    status: PUBLISHED,
    type: POST,
    min_score: 0.65
  ) {
    score
    content {
      id
      slug
      title
    }
  }
}
```

## Current Status

- async summary + embeddings pipeline is running via Redis/Horizon
- GraphQL and Filament both use queued summary generation by default
- prompt registry, compare, import/export flows are active
- content bundle import/export and bundled demo dataset are available for local demos
- domain events are published to Reverb and Redis Streams
- Queue Center shows queue depth, pending age, throughput, and success/failure rates
- System Health page verifies DB/pgvector, Redis, Horizon, Reverb, and Ollama
- live smoke command validates queued summary, queued embeddings, and semantic search against the running stack
- GitHub Actions CI runs Pint, tests, and GraphQL schema validation on pushes and pull requests

## License

MIT
