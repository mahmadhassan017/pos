<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zia Traders Point of Sale</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #111827;
            --muted: #64748b;
            --line: #e5e7eb;
            --soft: #f8fafc;
            --brand: #0f766e;
            --brand-dark: #115e59;
            --accent: #f59e0b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(15, 118, 110, 0.12), transparent 32rem),
                linear-gradient(135deg, #ffffff 0%, #f8fafc 48%, #eef2ff 100%);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .page {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            padding: 32px;
        }

        .shell {
            width: min(1120px, 100%);
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 42px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .brand-mark {
            display: grid;
            width: 44px;
            height: 44px;
            place-items: center;
            border-radius: 12px;
            color: #ffffff;
            background: linear-gradient(135deg, var(--brand), var(--brand-dark));
            box-shadow: 0 18px 38px rgba(15, 118, 110, 0.24);
        }

        .nav-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            border-radius: 8px;
            padding: 0 18px;
            font-weight: 700;
            border: 1px solid var(--line);
            background: #ffffff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .button.primary {
            color: #ffffff;
            border-color: var(--brand);
            background: var(--brand);
        }

        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(320px, 0.95fr);
            gap: 28px;
            align-items: stretch;
        }

        .copy,
        .panel {
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.86);
            box-shadow: 0 26px 70px rgba(15, 23, 42, 0.09);
            backdrop-filter: blur(14px);
        }

        .copy {
            padding: clamp(28px, 5vw, 56px);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            color: var(--brand-dark);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .dot {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: var(--accent);
        }

        h1 {
            max-width: 760px;
            margin: 0;
            font-size: clamp(38px, 7vw, 76px);
            line-height: 0.96;
            letter-spacing: -0.04em;
        }

        .lead {
            max-width: 660px;
            margin: 22px 0 0;
            color: var(--muted);
            font-size: clamp(17px, 2vw, 21px);
            line-height: 1.65;
        }

        .cta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 30px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 34px;
        }

        .stat {
            border-radius: 12px;
            padding: 16px;
            background: var(--soft);
            border: 1px solid var(--line);
        }

        .stat strong {
            display: block;
            font-size: 24px;
        }

        .stat span {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: 13px;
        }

        .panel {
            padding: 24px;
        }

        .terminal {
            display: grid;
            gap: 14px;
        }

        .terminal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .badge {
            border-radius: 999px;
            padding: 6px 10px;
            color: var(--brand-dark);
            font-size: 12px;
            font-weight: 800;
            background: #ccfbf1;
        }

        .screen {
            border-radius: 14px;
            padding: 18px;
            background: #0f172a;
            color: #e2e8f0;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08);
        }

        .screen-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .screen-row:last-child {
            border-bottom: 0;
        }

        .screen-row span {
            color: #94a3b8;
        }

        .total {
            color: #ffffff;
            font-size: 26px;
            font-weight: 900;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .feature {
            border-radius: 12px;
            padding: 14px;
            background: #ffffff;
            border: 1px solid var(--line);
        }

        .feature strong {
            display: block;
            margin-bottom: 4px;
        }

        .feature span {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.4;
        }

        @media (max-width: 880px) {
            .hero {
                grid-template-columns: 1fr;
            }

            .nav {
                align-items: flex-start;
                flex-direction: column;
            }

            .stats,
            .features {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="shell">
            <header class="nav">
                <a class="brand" href="{{ url('/') }}" aria-label="Zia Traders Point of Sale">
                    <span class="brand-mark">ZT</span>
                    <span>Zia Traders POS</span>
                </a>

                <nav class="nav-actions" aria-label="Primary navigation">
                    @auth
                        <a class="button" href="{{ route('pos') }}">Open POS</a>
                        @if (auth()->user()->isAdmin())
                            <a class="button primary" href="{{ route('admin.reports.sales') }}">View Reports</a>
                        @endif
                    @else
                        <a class="button" href="{{ route('login') }}">Sign in</a>
                    @endauth
                </nav>
            </header>

            <main class="hero">
                <section class="copy">
                    <div class="eyebrow"><span class="dot"></span> Retail checkout system</div>
                    <h1>Zia Traders Point of Sale</h1>
                    <p class="lead">
                        A focused POS workspace for fast barcode checkout, product control, receipt printing,
                        user management, and sales reports built for daily shop operations.
                    </p>

                    <div class="cta-row">
                        @auth
                            <a class="button primary" href="{{ route('pos') }}">Start selling</a>
                            <a class="button" href="{{ route('admin.products') }}">Manage products</a>
                        @else
                            <a class="button primary" href="{{ route('login') }}">Login to dashboard</a>
                        @endauth
                    </div>

                    <div class="stats" aria-label="POS highlights">
                        <div class="stat">
                            <strong>Fast</strong>
                            <span>Barcode-ready checkout</span>
                        </div>
                        <div class="stat">
                            <strong>Clear</strong>
                            <span>Receipts and invoices</span>
                        </div>
                        <div class="stat">
                            <strong>Smart</strong>
                            <span>Daily revenue reports</span>
                        </div>
                    </div>
                </section>

                <aside class="panel" aria-label="POS preview">
                    <div class="terminal">
                        <div class="terminal-head">
                            <strong>Today at checkout</strong>
                            <span class="badge">Live-ready</span>
                        </div>

                        <div class="screen">
                            <div class="screen-row">
                                <span>Demo Product 1</span>
                                <strong>Rs 220</strong>
                            </div>
                            <div class="screen-row">
                                <span>Demo Product 2</span>
                                <strong>Rs 150</strong>
                            </div>
                            <div class="screen-row">
                                <span>Total</span>
                                <strong class="total">Rs 370</strong>
                            </div>
                        </div>

                        <div class="features">
                            <div class="feature">
                                <strong>Sales reports</strong>
                                <span>Daily, weekly, and monthly revenue views.</span>
                            </div>
                            <div class="feature">
                                <strong>User roles</strong>
                                <span>Admin and cashier access for safer operations.</span>
                            </div>
                            <div class="feature">
                                <strong>Inventory</strong>
                                <span>Products, stock, barcode, and images in one place.</span>
                            </div>
                            <div class="feature">
                                <strong>Receipts</strong>
                                <span>Printable and downloadable checkout receipts.</span>
                            </div>
                        </div>
                    </div>
                </aside>
            </main>
        </div>
    </div>
</body>
</html>
