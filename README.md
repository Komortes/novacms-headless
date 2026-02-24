# NovaCMS

NovaCMS is an AI-powered headless CMS built on Laravel.  
It stores and serves structured content through APIs and processes AI tasks asynchronously in the background.

## What NovaCMS Provides

- content management for `Post` and `Page`
- async AI summarization pipeline
- real-time generation status updates
- semantic search with vector embeddings
- versioned prompt management for controlled AI outputs

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

### 4. Semantic Search (v1.1)
- content embeddings are stored in PostgreSQL (`pgvector`)
- supported operations:
  - `semanticSearch(query)`
  - `relatedContent(contentId)`

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

## Prompt and Model Governance

- prompt templates are versioned in `prompts`
- generated summaries store:
  - prompt version
  - model name
  - token usage (`tokens_in`, `tokens_out`)
- this provides reproducibility and traceability for AI output changes

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
OLLAMA_MODEL=llama3.1
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
docker compose exec ollama ollama pull llama3.1
```

## API Endpoints

- GraphQL: `/graphql`
- Admin panel: `/admin`

## Current Development Focus

1. complete `contents` and `content_ai_summaries` migrations
2. implement Filament `ContentResource`
3. implement `GenerateContentSummaryJob`
4. finalize Ollama provider integration
5. broadcast summary-completed events via Reverb

## License

MIT
