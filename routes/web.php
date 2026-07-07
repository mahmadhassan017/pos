<?php

use App\Http\Controllers\ProfileController;
use App\Livewire\Admin\NotificationsPanel;
use App\Livewire\Admin\ProductManager;
use App\Livewire\Admin\SalesReport;
use App\Models\Sale;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return redirect()->route('pos');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::view('/pos', 'pos')->name('pos');
    Route::get('/sales/{sale}/receipt', function (Sale $sale) {
        abort_unless(auth()->user()->is_admin || $sale->user_id === auth()->id(), 403);

        return view('sales.receipt', [
            'sale' => $sale->load(['items', 'cashier']),
            'print' => request()->boolean('print'),
        ]);
    })->name('sales.receipt');

    Route::get('/sales/{sale}/receipt/download', function (Sale $sale) {
        abort_unless(auth()->user()->is_admin || $sale->user_id === auth()->id(), 403);

        $html = view('sales.receipt', [
            'sale' => $sale->load(['items', 'cashier']),
            'print' => false,
        ])->render();

        return new Response($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'attachment; filename="receipt-' . $sale->invoice_no . '.html"',
        ]);
    })->name('sales.receipt.download');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::view('/users', 'admin.users')->name('users');
        Route::view('/products', 'admin.products')->name('products');
        Route::view('/reports/sales', 'admin.sales-report')->name('reports.sales');
        Route::get('/notifications', NotificationsPanel::class)->name('notifications');
    });
});

require __DIR__.'/auth.php';
