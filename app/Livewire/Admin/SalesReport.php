<?php

namespace App\Livewire\Admin;

use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReport extends Component
{
    public string $fromDate;
    public string $toDate;
    public ?string $selectedType = null;
    public ?string $selectedKey = null;
    public ?int $selectedSaleId = null;

    public function mount(): void
    {
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
    }

    private function range(): array
    {
        return [
            Carbon::parse($this->fromDate)->startOfDay(),
            Carbon::parse($this->toDate)->endOfDay(),
        ];
    }

    private function salesQuery(): Builder
    {
        return Sale::query()->whereBetween('created_at', $this->range());
    }

    private function salesCollection(): Collection
    {
        return $this->salesQuery()->with('cashier')->oldest()->get();
    }

    private function revenueSummary(string $type): Collection
    {
        return $this->salesCollection()
            ->groupBy(fn (Sale $sale) => match ($type) {
                'daily' => $sale->created_at->toDateString(),
                'weekly' => $sale->created_at->copy()->startOfWeek()->toDateString(),
                'monthly' => $sale->created_at->format('Y-m'),
            })
            ->map(function (Collection $sales, string $key) use ($type) {
                return [
                    'key' => $key,
                    'label' => $this->periodLabel($type, $key),
                    'orders' => $sales->count(),
                    'revenue' => $sales->sum(fn (Sale $sale) => (float) $sale->total),
                    'items' => $sales->sum(fn (Sale $sale) => $sale->items()->sum('quantity')),
                ];
            })
            ->values();
    }

    private function periodLabel(string $type, string $key): string
    {
        return match ($type) {
            'daily' => Carbon::parse($key)->format('d M Y'),
            'weekly' => Carbon::parse($key)->format('d M') . ' - ' . Carbon::parse($key)->endOfWeek()->format('d M Y'),
            'monthly' => Carbon::createFromFormat('Y-m', $key)->format('F Y'),
        };
    }

    private function periodRange(string $type, string $key): array
    {
        return match ($type) {
            'daily' => [Carbon::parse($key)->startOfDay(), Carbon::parse($key)->endOfDay()],
            'weekly' => [Carbon::parse($key)->startOfWeek(), Carbon::parse($key)->endOfWeek()],
            'monthly' => [Carbon::createFromFormat('Y-m', $key)->startOfMonth(), Carbon::createFromFormat('Y-m', $key)->endOfMonth()],
        };
    }

    public function openInvoices(string $type, string $key): void
    {
        $this->selectedType = $type;
        $this->selectedKey = $key;
        $this->selectedSaleId = null;
    }

    public function closeInvoices(): void
    {
        $this->selectedType = null;
        $this->selectedKey = null;
        $this->selectedSaleId = null;
    }

    public function showInvoice(int $saleId): void
    {
        $this->selectedSaleId = $saleId;
    }

    private function selectedInvoices(): Collection
    {
        if (! $this->selectedType || ! $this->selectedKey) {
            return collect();
        }

        return Sale::query()
            ->with(['cashier', 'items'])
            ->whereBetween('created_at', $this->periodRange($this->selectedType, $this->selectedKey))
            ->latest()
            ->get();
    }

    public function exportRevenue(string $type): StreamedResponse
    {
        $rows = $this->revenueSummary($type);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Period', 'Orders', 'Items Sold', 'Revenue']);

            foreach ($rows as $row) {
                fputcsv($handle, [$row['label'], $row['orders'], $row['items'], $row['revenue']]);
            }

            fclose($handle);
        }, "{$type}-revenue.csv");
    }

    public function exportInvoices(?string $type = null, ?string $key = null): StreamedResponse
    {
        $query = Sale::query()->with('cashier');

        if ($type && $key) {
            $query->whereBetween('created_at', $this->periodRange($type, $key));
            $fileName = "{$type}-invoices-{$key}.csv";
        } else {
            $query->whereBetween('created_at', $this->range());
            $fileName = 'invoices.csv';
        }

        $sales = $query->latest()->get();

        return response()->streamDownload(function () use ($sales) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Invoice', 'Date', 'Cashier', 'Payment', 'Subtotal', 'Discount', 'Tax', 'Total', 'Paid', 'Change']);

            foreach ($sales as $sale) {
                fputcsv($handle, [
                    $sale->invoice_no,
                    $sale->created_at->format('Y-m-d H:i:s'),
                    $sale->cashier?->name ?? 'Guest',
                    $sale->payment_method,
                    $sale->subtotal,
                    $sale->discount,
                    $sale->tax,
                    $sale->total,
                    $sale->paid_amount,
                    $sale->change_amount,
                ]);
            }

            fclose($handle);
        }, $fileName);
    }

    public function render()
    {
        $range = $this->range();
        $selectedInvoices = $this->selectedInvoices();
        $selectedSale = $this->selectedSaleId
            ? Sale::with(['cashier', 'items'])->find($this->selectedSaleId)
            : null;

        return view('livewire.admin.sales-report', [
            'dailyRevenue' => $this->revenueSummary('daily'),
            'weeklyRevenue' => $this->revenueSummary('weekly'),
            'monthlyRevenue' => $this->revenueSummary('monthly'),
            'selectedInvoices' => $selectedInvoices,
            'selectedSale' => $selectedSale,
            'selectedLabel' => $this->selectedType && $this->selectedKey ? $this->periodLabel($this->selectedType, $this->selectedKey) : '',
            'topProducts' => SaleItem::query()
                ->selectRaw('product_id, product_name, SUM(quantity) as sold_qty, SUM(line_total) as revenue')
                ->whereHas('sale', fn (Builder $query) => $query->whereBetween('created_at', $range))
                ->groupBy('product_id', 'product_name')
                ->orderByDesc('sold_qty')
                ->limit(10)
                ->get(),
            'totalSales' => (clone $this->salesQuery())->sum('total'),
            'totalOrders' => (clone $this->salesQuery())->count(),
            'totalItems' => SaleItem::query()
                ->whereHas('sale', fn (Builder $query) => $query->whereBetween('created_at', $range))
                ->sum('quantity'),
        ]);
    }
}
