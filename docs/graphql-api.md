# GraphQL API

NovaCMS exposes a schema-first GraphQL API at `/graphql`.

## Demo Quickstart

If the Docker demo is already running, the fastest way to prove the headless story is:

```bash
make demo
make demo-token
```

That prints a read-only bearer token for `admin@novacms.test` with `graphql:read-internal`.

Minimal request:

```bash
curl http://localhost:8000/graphql \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer nova_yourGeneratedSecret" \
  -d '{"query":"query { contents(first: 5, locale: \"en\") { data { id slug title status } } }"}'
```

What this proves:

- seeded content is ready for API consumers immediately
- the admin can issue delivery tokens without leaving the product
- a frontend or integration can read AI-enriched content through GraphQL

## Access Model

- Public clients can read published content and run semantic search against published records only.
- Authenticated `web` users can access drafts, prompts, AI summaries, and write mutations.
- Authenticated `api-token` clients can access extra capabilities only if the token has the required ability.
- Prompt and summary management queries are not public by default.

## Authentication

NovaCMS supports two GraphQL auth modes:

- `web` session auth for Filament/admin users
- `api-token` bearer auth for external API clients

Session auth:

- Browser/session clients can authenticate through the Filament login flow.
- Authenticated GraphQL requests reuse the same session cookie.

Bearer token auth:

- Tokens are issued per user and returned once as `Bearer nova_<secret>`.
- Tokens are hashed at rest and support revocation and expiry.
- Use CLI commands to manage them:

```bash
php artisan api-token:create test@example.com external-client --ability=graphql:write --ability=graphql:read-internal
php artisan api-token:list test@example.com
php artisan api-token:revoke 1
```

For the packaged Docker demo, you can also use:

```bash
make demo-token
```

Example header:

```http
Authorization: Bearer nova_yourGeneratedSecret
```

Token abilities:

- `graphql:read-internal` allows draft and archived reads in content queries and semantic search.
- `graphql:write` allows `createContent`, `updateContent`, and `generateContentSummary`.
- `graphql:admin` allows internal registry and AI summary queries.
- `*` grants full token access.

## Request Limits

- Route-wide throttling applies to all GraphQL requests.
- Field throttling is stricter for semantic search and write mutations.
- Query depth and complexity are capped in Lighthouse config.

Default limits are controlled through:

```env
GRAPHQL_ROUTE_RATE_LIMIT_PER_MINUTE=120
GRAPHQL_READ_RATE_LIMIT_PER_MINUTE=120
GRAPHQL_SEARCH_RATE_LIMIT_PER_MINUTE=30
GRAPHQL_WRITE_RATE_LIMIT_PER_MINUTE=20
LIGHTHOUSE_SECURITY_MAX_QUERY_COMPLEXITY=80
LIGHTHOUSE_SECURITY_MAX_QUERY_DEPTH=8
```

## Public Queries

Fetch a single published content item:

```graphql
query GetContent($id: ID!) {
  content(id: $id) {
    id
    slug
    title
    status
    locale
  }
}
```

Paginate visible content:

```graphql
query ListContent {
  contents(first: 10, locale: "en", type: POST) {
    data {
      id
      slug
      title
      status
    }
    paginatorInfo {
      currentPage
      hasMorePages
    }
  }
}
```

Semantic search with filters:

```graphql
query SearchContent {
  semanticSearch(
    query: "headless cms with semantic search",
    limit: 5,
    locale: "en",
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

## Authenticated Queries

Get the active user:

```graphql
query {
  me {
    id
    name
    email
  }
}
```

Read AI prompt registry:

```graphql
query {
  prompts(first: 10) {
    data {
      id
      name
      version
      is_active
    }
  }
}
```

Read draft content with bearer token that has `graphql:read-internal`:

```graphql
query DraftPreview($id: ID!) {
  content(id: $id) {
    id
    slug
    status
  }
}
```

## Authenticated Mutations

Create content:

```graphql
mutation CreateContent {
  createContent(
    type: POST
    slug: "graphql-created-post"
    title: "GraphQL Created Post"
    body: "Markdown body"
    locale: "en"
    status: DRAFT
  ) {
    id
    slug
    status
  }
}
```

Update content:

```graphql
mutation UpdateContent($id: ID!) {
  updateContent(
    id: $id
    title: "Updated title"
    status: PUBLISHED
  ) {
    id
    slug
    status
    updated_at
  }
}
```

Queue summary generation:

```graphql
mutation GenerateSummary($id: ID!) {
  generateContentSummary(content_id: $id, prompt_version: "1.0.0") {
    id
    content_id
    status
  }
}
```

## Validation Notes

- `slug` must be lowercase kebab-case and unique per `locale`.
- `locale` accepts `en` or region variants such as `en-US`.
- Search `limit` is capped at `20`.
- `min_score` must be between `-1` and `1`.
- Token-authenticated writes require `graphql:write`.
- Token-authenticated admin queries require `graphql:admin`.
