<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $sale->invoice_no }} - Zia Traders</title>
    <style>
        body {
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 24px;
        }

        .receipt {
            background: #fff;
            margin: 0 auto;
            max-width: 360px;
            padding: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .08);
        }

        .center { text-align: center; }
        .muted { color: #6b7280; font-size: 12px; }
        .line { border-top: 1px dashed #9ca3af; margin: 12px 0; }
        table { border-collapse: collapse; width: 100%; }
        th, td { font-size: 12px; padding: 5px 0; text-align: left; vertical-align: top; }
        th:last-child, td:last-child { text-align: right; }
        .totals td { font-size: 13px; }
        .total td { font-size: 15px; font-weight: 700; }
        .actions { display: flex; gap: 8px; justify-content: center; margin: 16px 0; }
        .button {
            background: #111827;
            border: 1px solid #111827;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            padding: 9px 13px;
            text-decoration: none;
        }
        .button.secondary { background: #fff; color: #111827; }

        @media print {
            body { background: #fff; padding: 0; }
            .receipt { box-shadow: none; max-width: none; width: 80mm; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button class="button" onclick="window.print()">Print</button>
        <a class="button secondary" href="{{ route('sales.receipt.download', $sale) }}">Download</a>
    </div>

    <main class="receipt">
        <div class="center">
            <h1 style="margin:0;font-size:22px;">Zia Traders</h1>
            <p class="muted" style="margin:4px 0 0;">Sales Receipt</p>
        </div>

        <div class="line"></div>

        <table>
            <tr><td>Invoice</td><td>{{ $sale->invoice_no }}</td></tr>
            <tr><td>Date</td><td>{{ $sale->created_at->format('d M Y H:i') }}</td></tr>
            <tr><td>Cashier</td><td>{{ $sale->cashier?->name ?? 'Guest' }}</td></tr>
            <tr><td>Payment</td><td>{{ ucfirst($sale->payment_method) }}</td></tr>
        </table>

        <div class="line"></div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td>
                            {{ $item->product_name }}<br>
                            <span class="muted">{{ $item->barcode }} x Rs {{ number_format($item->unit_price, 2) }}</span>
                        </td>
                        <td>{{ $item->quantity }}</td>
                        <td>Rs {{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="line"></div>

        <table class="totals">
            <tr><td>Subtotal</td><td>Rs {{ number_format($sale->subtotal, 2) }}</td></tr>
            <tr><td>Discount</td><td>Rs {{ number_format($sale->discount, 2) }}</td></tr>
            <tr><td>Tax</td><td>Rs {{ number_format($sale->tax, 2) }}</td></tr>
            <tr class="total"><td>Total</td><td>Rs {{ number_format($sale->total, 2) }}</td></tr>
            <tr><td>Paid</td><td>Rs {{ number_format($sale->paid_amount, 2) }}</td></tr>
            <tr><td>Change</td><td>Rs {{ number_format($sale->change_amount, 2) }}</td></tr>
        </table>

        <div class="line"></div>

        <p class="center muted" style="margin-bottom:0;">Thank you for shopping at Zia Traders.</p>
    </main>

    @if ($print)
        <script>
            window.addEventListener('load', () => window.print());
        </script>
    @endif
</body>
</html>
