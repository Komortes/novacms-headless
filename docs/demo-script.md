# NovaCMS Demo Script

NovaCMS is easiest to demo as one product with three connected layers:

1. headless content operations
2. AI summarization and prompt governance
3. runtime and delivery controls

## Before You Start

Run:

```bash
make demo
make demo-check
```

Optional for live generation:

```bash
make demo-models
```

Optional for API consumers:

```bash
make demo-token
```

## 60-Second Version

Use this when you need a short product pass.

1. Open `http://localhost:8000`
2. Explain that NovaCMS is a headless CMS with built-in AI summarization, prompt governance, and runtime visibility.
3. Open `http://localhost:8000/admin`
4. Sign in as `admin@novacms.test / password`
5. Open `Content Workspace` and show ready, draft, and failed AI states
6. Open one record and show TL;DR, FAQ, tags, and review flow
7. Open `API Access` and mention that the same product issues GraphQL delivery tokens

## 5-Minute Product Walkthrough

### 1. Product framing

Say:

> NovaCMS is not just an admin panel. It combines structured content, AI summarization, prompt governance, and headless delivery in one product surface.

### 2. Content workflow

Open `Content Workspace`.

Show:

- draft vs published records
- AI-ready vs AI-failed states
- bulk and row-level operational actions

Say:

> Editors do not leave the product to understand AI state. Content and AI workflow are part of the same workspace.

### 3. Record-level review

Open a seeded record with ready AI output.

Show:

- source markdown
- TL;DR
- bullets
- FAQ
- tags
- status control

Say:

> The output is review material for content teams and structured data for downstream consumers.

### 4. Prompt governance

Open `Prompt Registry`.

Show:

- prompt families
- active version
- historical versions
- compare flow

Say:

> Prompt changes are versioned and govern the production AI contract.

### 5. Delivery story

Open `API Access`.

Show:

- token registry
- ability shapes
- client bootstrap snippets

Then mention:

```bash
make demo-token
```

Say:

> Delivery is headless by default. The admin issues GraphQL tokens, and frontend consumers pull structured content plus AI-enriched fields.

### 6. Runtime story

Open `Queue Center` and `System Health`.

Show:

- queue pressure
- failed runs
- runtime checks
- escalation paths

Say:

> When AI or infrastructure degrades, operators can diagnose it from the same product rather than from ad hoc shell commands.

## Demo Accounts

| Role | Email | Password | Best for |
|---|---|---|---|
| Admin | `admin@novacms.test` | `password` | Full product story |
| Editor | `editor@novacms.test` | `password` | Content review path |
| Operator | `operator@novacms.test` | `password` | Queue and runtime path |

## Fallback Notes

- If `make demo-check` reports missing models, the seeded walkthrough still works.
- Run `make demo-models` only if you want fresh Ollama generation during the demo.
- If someone changed the seeded data, run `make demo-reset`.
