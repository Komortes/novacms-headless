# GraphQL API

NovaCMS exposes a schema-first GraphQL API at `/graphql`.

## Access Model

- Public clients can read published content and run semantic search against published records only.
- Authenticated `web` users can access drafts, prompts, AI summaries, and write mutations.
- Prompt and summary management queries are not public.

## Authentication

Current auth mode uses the Laravel `web` guard.

- Browser/session clients can authenticate through the Filament login flow.
- Authenticated GraphQL requests reuse the same session cookie.
- Mutations and internal queries require an authenticated session.

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
