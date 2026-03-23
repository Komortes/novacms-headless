@php
    $positioning = [
        [
            'title' => 'Headless by default',
            'copy' => 'Store posts and pages once, then deliver them to websites, apps, or internal tools through GraphQL instead of coupling content to one frontend.',
        ],
        [
            'title' => 'AI inside the workflow',
            'copy' => 'Generate TL;DR, bullets, FAQ, meta description, and tags as part of editorial flow, not as a sidecar script.',
        ],
        [
            'title' => 'Prompt and runtime governance',
            'copy' => 'Treat models, prompts, queue health, and failed runs as product operations that belong in the admin, not hidden shell commands.',
        ],
    ];

    $scenarios = [
        [
            'eyebrow' => 'Editorial',
            'title' => 'Write markdown, review AI output, publish with confidence',
            'copy' => 'Editors work in one content workspace where draft status, generated summary quality, and publish readiness are visible together.',
            'href' => url('/admin/contents'),
        ],
        [
            'eyebrow' => 'Headless API',
            'title' => 'Expose content through GraphQL for real frontend clients',
            'copy' => 'Use API Access to issue tokens, then deliver content and semantic search to websites, preview apps, or internal tools.',
            'href' => url('/graphql'),
        ],
        [
            'eyebrow' => 'Operations',
            'title' => 'See queue pressure, failed runs, and runtime posture',
            'copy' => 'Queue Center, System Health, and AI Settings make the AI layer observable instead of magical.',
            'href' => url('/admin'),
        ],
    ];

    $adminAreas = [
        [
            'title' => 'Content Workspace',
            'description' => 'Seeded posts and pages, AI status lanes, bulk generation, and review-ready drafts.',
            'path' => '/admin/contents',
        ],
        [
            'title' => 'Prompt Registry',
            'description' => 'Versioned prompts with compare and activate flow so output contracts can evolve deliberately.',
            'path' => '/admin/prompts',
        ],
        [
            'title' => 'API Access',
            'description' => 'Issue GraphQL tokens from the UI and inspect privilege, ownership, and rotation posture.',
            'path' => '/admin/settings/api-access',
        ],
        [
            'title' => 'AI Runtime',
            'description' => 'Provider defaults, model profiles, queue center, and system health in the same product shell.',
            'path' => '/admin/settings/ai',
        ],
    ];

    $demoAccounts = [
        [
            'role' => 'Admin',
            'email' => \Database\Seeders\DemoEnvironmentSeeder::ADMIN_EMAIL,
            'password' => \Database\Seeders\DemoEnvironmentSeeder::PASSWORD,
            'description' => 'Full product view across content, prompts, AI settings, API access, queue, and health.',
        ],
        [
            'role' => 'Editor',
            'email' => \Database\Seeders\DemoEnvironmentSeeder::EDITOR_EMAIL,
            'password' => \Database\Seeders\DemoEnvironmentSeeder::PASSWORD,
            'description' => 'Editorial-only path for draft review, content edits, and publish decisions.',
        ],
        [
            'role' => 'Operator',
            'email' => \Database\Seeders\DemoEnvironmentSeeder::OPERATOR_EMAIL,
            'password' => \Database\Seeders\DemoEnvironmentSeeder::PASSWORD,
            'description' => 'Queue and runtime view for monitoring failed runs and system posture.',
        ],
    ];

    $demoSteps = [
        [
            'title' => 'Launch the demo stack',
            'copy' => 'Run `make demo` and open `http://localhost:8000`.',
        ],
        [
            'title' => 'Sign in as admin',
            'copy' => 'Open `/admin` and use `admin@novacms.test` with password `password`.',
        ],
        [
            'title' => 'Review seeded content',
            'copy' => 'Inspect published records, draft content, and one intentionally failed AI summary.',
        ],
        [
            'title' => 'Test headless delivery',
            'copy' => 'Issue a token from `API Access`, then query the GraphQL endpoint at `/graphql`.',
        ],
    ];

    $graphqlExample = <<<'GRAPHQL'
query DemoContentFeed {
  contents(first: 3, status: PUBLISHED) {
    data {
      id
      title
      slug
      type
      locale
      summary {
        summary_tldr
        summary_tags
      }
    }
  }
}
GRAPHQL;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>NovaCMS | Headless CMS with AI summarization</title>
        <meta name="description" content="NovaCMS is a headless CMS with built-in AI summarization, prompt governance, and operational visibility for content teams.">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
        <style>
            :root {
                --bg: #f3eee4;
                --panel: rgba(255, 255, 255, 0.78);
                --panel-strong: rgba(255, 255, 255, 0.92);
                --line: rgba(33, 24, 14, 0.11);
                --ink: #1f1a15;
                --muted: #655a4b;
                --accent: #b95b23;
                --accent-soft: #f6d4bb;
                --signal: #136f63;
                --danger: #9b2c2c;
                --radius-xl: 30px;
                --radius-lg: 22px;
                --shadow: 0 24px 60px -34px rgba(31, 26, 21, 0.28);
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                color: var(--ink);
                background:
                    radial-gradient(circle at top left, rgba(245, 181, 126, 0.45), transparent 34%),
                    radial-gradient(circle at top right, rgba(28, 144, 125, 0.18), transparent 30%),
                    linear-gradient(180deg, #fbf8f1 0%, var(--bg) 56%, #efe5d7 100%);
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            code,
            pre {
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            }

            .shell {
                width: min(1180px, calc(100% - 32px));
                margin: 0 auto;
                padding: 28px 0 64px;
            }

            .topbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                margin-bottom: 28px;
            }

            .brand {
                display: inline-flex;
                align-items: center;
                gap: 12px;
                padding: 10px 14px;
                border-radius: 999px;
                border: 1px solid rgba(255, 255, 255, 0.5);
                background: rgba(255, 255, 255, 0.55);
                backdrop-filter: blur(14px);
            }

            .brand-mark {
                width: 12px;
                height: 12px;
                border-radius: 999px;
                background: linear-gradient(135deg, #df7a32 0%, #1a8b79 100%);
                box-shadow: 0 0 0 6px rgba(223, 122, 50, 0.14);
            }

            .brand-copy strong,
            h1,
            h2,
            h3 {
                font-family: 'Space Grotesk', 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            }

            .eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 6px 10px;
                border-radius: 999px;
                background: rgba(185, 91, 35, 0.1);
                color: var(--accent);
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0.14em;
                text-transform: uppercase;
            }

            .hero {
                display: grid;
                grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
                gap: 22px;
                align-items: stretch;
            }

            .card,
            .hero-main,
            .hero-side,
            .band,
            .strip {
                position: relative;
                overflow: hidden;
                border: 1px solid var(--line);
                background: var(--panel);
                backdrop-filter: blur(18px);
                border-radius: var(--radius-xl);
                box-shadow: var(--shadow);
            }

            .hero-main,
            .hero-side {
                padding: 28px;
            }

            .hero-main::before,
            .hero-side::before,
            .card::before,
            .band::before,
            .strip::before {
                content: '';
                position: absolute;
                inset: 0 auto auto 0;
                width: 100%;
                height: 1px;
                background: linear-gradient(90deg, rgba(223, 122, 50, 0.42), rgba(24, 125, 111, 0.18), transparent);
            }

            h1 {
                margin: 18px 0 14px;
                font-size: clamp(42px, 6vw, 72px);
                line-height: 0.95;
                letter-spacing: -0.04em;
            }

            .lead {
                max-width: 720px;
                margin: 0;
                color: var(--muted);
                font-size: 18px;
                line-height: 1.7;
            }

            .cta-row,
            .pill-row,
            .stats-row {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
            }

            .cta-row {
                margin-top: 24px;
            }

            .button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                min-height: 48px;
                padding: 0 18px;
                border-radius: 999px;
                border: 1px solid transparent;
                font-weight: 600;
                transition: transform 0.16s ease, border-color 0.16s ease, background 0.16s ease;
            }

            .button:hover {
                transform: translateY(-1px);
            }

            .button-primary {
                background: linear-gradient(135deg, #c56229 0%, #d78841 100%);
                color: #fff7f0;
                box-shadow: 0 18px 30px -20px rgba(197, 98, 41, 0.65);
            }

            .button-secondary {
                border-color: rgba(31, 26, 21, 0.12);
                background: rgba(255, 255, 255, 0.62);
                color: var(--ink);
            }

            .pill {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 9px 12px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.7);
                border: 1px solid rgba(31, 26, 21, 0.08);
                color: #403629;
                font-size: 13px;
                font-weight: 600;
            }

            .hero-list,
            .step-list {
                display: grid;
                gap: 12px;
                margin-top: 18px;
            }

            .hero-list-item,
            .step {
                padding: 16px 18px;
                border-radius: var(--radius-lg);
                border: 1px solid rgba(255, 255, 255, 0.44);
                background: rgba(255, 255, 255, 0.56);
            }

            .hero-list-item strong,
            .step strong,
            .card h3,
            .account-role,
            .scenario-card h3 {
                display: block;
                margin-bottom: 6px;
                font-size: 16px;
            }

            .section {
                margin-top: 26px;
            }

            .section-heading {
                display: flex;
                justify-content: space-between;
                align-items: end;
                gap: 16px;
                margin-bottom: 16px;
            }

            .section-heading h2 {
                margin: 0;
                font-size: clamp(30px, 3vw, 42px);
                line-height: 1.02;
                letter-spacing: -0.03em;
            }

            .section-heading p {
                max-width: 680px;
                margin: 0;
                color: var(--muted);
                line-height: 1.7;
            }

            .grid-3,
            .grid-4,
            .grid-2 {
                display: grid;
                gap: 16px;
            }

            .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }

            .card,
            .scenario-card,
            .account-card {
                padding: 22px;
            }

            .card p,
            .scenario-card p,
            .account-card p,
            .step,
            .command-copy {
                margin: 0;
                color: var(--muted);
                line-height: 1.7;
            }

            .scenario-card {
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                gap: 18px;
                min-height: 240px;
            }

            .link-chip {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 10px 12px;
                border-radius: 999px;
                background: rgba(19, 111, 99, 0.1);
                color: var(--signal);
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0.12em;
                text-transform: uppercase;
            }

            .command-band {
                display: grid;
                grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.05fr);
                gap: 18px;
                padding: 24px;
            }

            .command-block,
            .code-panel {
                padding: 18px;
                border-radius: var(--radius-lg);
                background: #17130f;
                color: #f7efe6;
                border: 1px solid rgba(255, 255, 255, 0.08);
                overflow: auto;
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
            }

            .command-block pre,
            .code-panel pre {
                margin: 0;
                font-size: 13px;
                line-height: 1.7;
            }

            .account-meta {
                display: flex;
                flex-direction: column;
                gap: 8px;
                margin-top: 12px;
            }

            .account-kv {
                display: flex;
                justify-content: space-between;
                gap: 10px;
                padding: 10px 12px;
                border-radius: 14px;
                background: rgba(255, 255, 255, 0.55);
                border: 1px solid rgba(31, 26, 21, 0.08);
                font-size: 13px;
            }

            .account-kv span:last-child,
            .strong {
                font-weight: 700;
                color: var(--ink);
            }

            .footer-note {
                margin-top: 18px;
                padding: 18px 20px;
                border-radius: var(--radius-lg);
                background: rgba(19, 111, 99, 0.08);
                border: 1px solid rgba(19, 111, 99, 0.16);
                color: #264740;
                line-height: 1.7;
            }

            @media (max-width: 980px) {
                .hero,
                .command-band,
                .grid-4,
                .grid-3,
                .grid-2 {
                    grid-template-columns: 1fr;
                }

                .section-heading {
                    align-items: start;
                    flex-direction: column;
                }
            }

            @media (max-width: 640px) {
                .shell {
                    width: min(100% - 20px, 1180px);
                    padding-top: 18px;
                }

                .hero-main,
                .hero-side,
                .card,
                .scenario-card,
                .account-card,
                .command-band {
                    padding: 20px;
                }

                h1 {
                    font-size: 38px;
                }
            }
        </style>
    </head>
    <body>
        <main class="shell">
            <div class="topbar">
                <div class="brand">
                    <span class="brand-mark"></span>
                    <div class="brand-copy">
                        <strong>NovaCMS</strong>
                    </div>
                </div>
                <a class="button button-secondary" href="{{ url('/admin') }}">Open Admin</a>
            </div>

            <section class="hero">
                <div class="hero-main">
                    <span class="eyebrow">Headless CMS + AI workflow</span>
                    <h1>Headless CMS with built-in AI summarization for real content teams.</h1>
                    <p class="lead">
                        NovaCMS combines structured content, AI-generated summaries, prompt governance, queue visibility, and GraphQL delivery in one product surface.
                        It is meant to feel like a product you can demo, not just a backend with tables.
                    </p>

                    <div class="cta-row">
                        <a class="button button-primary" href="{{ url('/admin') }}">Sign In To Admin</a>
                        <a class="button button-secondary" href="{{ url('/graphql') }}">Open GraphQL Endpoint</a>
                    </div>

                    <div class="pill-row" style="margin-top: 18px;">
                        <span class="pill">Markdown content + structured API delivery</span>
                        <span class="pill">Async AI TL;DR, bullets, FAQ, tags</span>
                        <span class="pill">Prompt registry, queue, runtime health</span>
                    </div>
                </div>

                <aside class="hero-side">
                    <span class="eyebrow">Demo scenario</span>
                    <div class="step-list">
                        @foreach ($demoSteps as $index => $step)
                            <div class="step">
                                <strong>{{ $index + 1 }}. {{ $step['title'] }}</strong>
                                <span>{{ $step['copy'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="footer-note">
                        The Docker demo ships with seeded content and ready AI summaries so the admin is useful immediately. Pull local Ollama models only if you want to test live generation inside the demo.
                    </div>
                </aside>
            </section>

            <section class="section">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">Positioning</span>
                        <h2>What NovaCMS actually is</h2>
                    </div>
                    <p>
                        NovaCMS is positioned as a headless CMS with AI-assisted editorial workflow. The CMS, AI layer, prompt contract, and operational surface all belong to one product.
                    </p>
                </div>
                <div class="grid-3">
                    @foreach ($positioning as $item)
                        <article class="card">
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['copy'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="section">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">Usage scenarios</span>
                        <h2>One product, three clear modes of use</h2>
                    </div>
                    <p>
                        The demo is easiest to understand when you think of NovaCMS as editorial workspace, headless delivery surface, and AI operations console at the same time.
                    </p>
                </div>
                <div class="grid-3">
                    @foreach ($scenarios as $scenario)
                        <a href="{{ $scenario['href'] }}" class="scenario-card card">
                            <div>
                                <span class="eyebrow">{{ $scenario['eyebrow'] }}</span>
                                <h3 style="margin-top: 16px;">{{ $scenario['title'] }}</h3>
                                <p>{{ $scenario['copy'] }}</p>
                            </div>
                            <span class="link-chip">Open area</span>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="section">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">Admin surface</span>
                        <h2>What you can actually click in the demo</h2>
                    </div>
                    <p>
                        These links are the minimum admin face for the product story: content workflow, prompt governance, API-first delivery, and runtime controls.
                    </p>
                </div>
                <div class="grid-4">
                    @foreach ($adminAreas as $area)
                        <a class="card" href="{{ url($area['path']) }}">
                            <h3>{{ $area['title'] }}</h3>
                            <p>{{ $area['description'] }}</p>
                            <div style="margin-top: 16px;">
                                <span class="link-chip">{{ $area['path'] }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="section">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">Seeded access</span>
                        <h2>Demo accounts with role-aware entry points</h2>
                    </div>
                    <p>
                        The Docker demo seeds three users so you can inspect the product through admin, editorial, and operator lenses without creating anything manually.
                    </p>
                </div>
                <div class="grid-3">
                    @foreach ($demoAccounts as $account)
                        <article class="account-card card">
                            <span class="eyebrow">{{ $account['role'] }}</span>
                            <p class="account-role" style="margin-top: 16px;">{{ $account['description'] }}</p>
                            <div class="account-meta">
                                <div class="account-kv">
                                    <span>Email</span>
                                    <span>{{ $account['email'] }}</span>
                                </div>
                                <div class="account-kv">
                                    <span>Password</span>
                                    <span>{{ $account['password'] }}</span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="section strip command-band">
                <div>
                    <span class="eyebrow">Docker demo</span>
                    <h2 style="margin: 16px 0 10px; font-size: clamp(30px, 3vw, 44px); line-height: 1.02; letter-spacing: -0.03em;">Run the product, not just the database.</h2>
                    <p class="command-copy">
                        The demo profile builds the app image, boots PostgreSQL, Redis, Ollama, Horizon, and Reverb, then seeds ready content plus role-aware demo users.
                    </p>
                    <div class="hero-list" style="margin-top: 18px;">
                        <div class="hero-list-item">
                            <strong>Default demo</strong>
                            <span>`make demo` starts the product shell with seeded data.</span>
                        </div>
                        <div class="hero-list-item">
                            <strong>Headless delivery token</strong>
                            <span>`make demo-token` prints a read-only GraphQL token for frontend consumers and API clients.</span>
                        </div>
                        <div class="hero-list-item">
                            <strong>Optional live generation</strong>
                            <span>`make demo-models` pulls local Ollama models if you want to test new summary runs.</span>
                        </div>
                    </div>
                </div>
                <div style="display: grid; gap: 16px;">
                    <div class="command-block">
                        <pre><code>make demo</code></pre>
                    </div>
                    <div class="command-block">
                        <pre><code>make demo-token</code></pre>
                    </div>
                    <div class="command-block">
                        <pre><code>make demo-models</code></pre>
                    </div>
                    <div class="command-block">
                        <pre><code>make demo-down</code></pre>
                    </div>
                </div>
            </section>

            <section class="section grid-2">
                <article class="band" style="padding: 24px;">
                    <span class="eyebrow">GraphQL example</span>
                    <h2 style="margin: 16px 0 12px; font-size: 34px; line-height: 1.04;">A headless scenario is part of the demo.</h2>
                    <p class="command-copy">
                        After signing in as admin, issue a token from <span class="strong">API Access</span> and query `/graphql` from any frontend or API client.
                        The demo content already includes structured summaries, so API consumers can immediately retrieve AI-enriched content.
                    </p>
                    <div class="footer-note" style="margin-top: 18px;">
                        Recommended path: admin login → API Access or `make demo-token` → issue read-only token → call `contents` query.
                    </div>
                </article>
                <article class="band" style="padding: 24px;">
                    <span class="eyebrow">Query sample</span>
                    <div class="code-panel" style="margin-top: 16px;">
                        <pre><code>{{ $graphqlExample }}</code></pre>
                    </div>
                </article>
            </section>
        </main>
    </body>
</html>
