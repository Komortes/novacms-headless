# NovaCMS

NovaCMS is an AI-powered headless CMS built on Laravel.  
It stores and serves structured content through APIs and processes AI tasks asynchronously in the background.

## What NovaCMS Provides

- content management for `Post` and `Page`
- async AI summarization pipeline
- per-run provider/model selection in admin actions
- central AI settings page for provider defaults and API credentials
- real-time generation status updates
- semantic search with vector embeddings
- versioned prompt management for controlled AI outputs
- runtime smoke checks and health dashboard for ops visibility

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
| Backend | Laravel 12, PHP 8.2+ | Core domain, jobs, events, API integration |
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
cp .env.example .env
composer install
npm install

docker compose up -d
php artisan key:generate
php artisan migrate

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

Prompt registry workflow:

- use `/admin/prompts` to create and edit prompt versions
- use `Activate` action to switch the active version per prompt name
- use `/admin/prompts/compare` to review template and parameter differences
- use `Export all`, `Export active`, and row-level `Export` for JSON bundles
- use `Import JSON` to upsert exported bundles back into the registry

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
```

Live end-to-end smoke:

```bash
# validates DB + Redis + Ollama, runs queued summary + embeddings, then checks semantic search
php artisan stack:e2e-smoke

# keep the generated smoke record for manual inspection
php artisan stack:e2e-smoke --keep-records

# require full local runtime, including Horizon and Reverb
php artisan stack:e2e-smoke --require-horizon --require-reverb
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
- domain events are published to Reverb and Redis Streams
- Queue Center shows queue depth, pending age, throughput, and success/failure rates
- System Health page verifies DB/pgvector, Redis, Horizon, Reverb, and Ollama
- live smoke command validates queued summary, queued embeddings, and semantic search against the running stack

## License

MIT
