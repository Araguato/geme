<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductImportExportController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierInvoiceController;
use App\Http\Controllers\SupplierPaymentController;
use App\Http\Controllers\AccountsPayableDashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ErrorReportController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\CashShiftController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\UnitConversionAdminController;
use App\Http\Controllers\RecurringSupplierInvoiceController;
use App\Http\Controllers\HelpCenterController;
use App\Http\Controllers\FiscalLedgerController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PayrollPeriodController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\PayrollEntryController;
use App\Http\Controllers\PublicOrderController;
use App\Http\Controllers\CustomerRegistrationController;
use App\Http\Controllers\LoginLogController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
});

// Catálogo público para clientes
Route::get('catalogo', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('catalogo/{product}', [CatalogController::class, 'show'])->name('catalog.show');

// Páginas legales
Route::get('terminos', function () {
    return view('legal.terms');
})->name('legal.terms');

Route::get('privacidad', function () {
    return view('legal.privacy');
})->name('legal.privacy');

// Registro de clientes (protegido con Cloudflare Turnstile)
Route::get('registro-cliente', [CustomerRegistrationController::class, 'create'])->name('customer.register');
Route::post('registro-cliente', [CustomerRegistrationController::class, 'store'])->name('customer.register.store');

// Registro de logins (solo admin)
Route::middleware('auth')->get('admin/login-logs', [LoginLogController::class, 'index'])->name('login-logs.index');

// Pedidos en línea (solo usuarios autenticados, se cobra al retirar / en entrega)
Route::middleware('auth')->group(function () {
    Route::get('pedido', [PublicOrderController::class, 'index'])->name('public.order.index');
    Route::post('pedido/carrito', [PublicOrderController::class, 'addToCart'])->name('public.cart.add');
    Route::get('pedido/carrito', [PublicOrderController::class, 'cart'])->name('public.cart');
    Route::post('pedido/carrito/quitar', [PublicOrderController::class, 'removeFromCart'])->name('public.cart.remove');
    Route::get('pedido/checkout', [PublicOrderController::class, 'checkout'])->name('public.checkout');
    Route::post('pedido', [PublicOrderController::class, 'store'])->name('public.order.store');
    Route::get('pedido/confirmado', [PublicOrderController::class, 'success'])->name('public.order.success');
});

Route::get('/license', [LicenseController::class, 'show'])->name('license.show');
Route::post('/license', [LicenseController::class, 'activate'])->name('license.activate');

Route::get('/debug-env', function () {
    return [
        'env_DB_PASSWORD' => env('DB_PASSWORD'),
        'mysql' => config('database.connections.mysql'),
    ];
});

Route::get('/debug-db-ping', function () {
    return DB::select('SELECT 1 as ok');
});

// Dashboard Jetstream
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

// Rutas protegidas
Route::middleware('auth')->group(function () {
    Route::get('staff', function () {
        return redirect()->route('dashboard');
    })->name('staff.home');

    Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Redirección raíz autenticada
    Route::redirect('/', '/categories');

    // Catálogo y configuración
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('products', ProductController::class)->except(['show']);
    Route::get('products/{product}/label', [ProductController::class, 'label'])->name('products.label');
    Route::get('products/labels/bulk', [ProductController::class, 'bulkLabels'])->name('products.labels.bulk');
    Route::resource('units', UnitController::class)->except(['show']);
    Route::resource('unit-conversions', UnitConversionAdminController::class)->except(['show']);
    Route::resource('suppliers', SupplierController::class)->except(['show', 'destroy']);
    Route::get('parties/create', [PartyController::class, 'create'])->name('parties.create');
    Route::post('parties', [PartyController::class, 'store'])->name('parties.store');

    Route::resource('supplier-invoices', SupplierInvoiceController::class)->except(['destroy']);
    Route::post('supplier-invoices/{supplierInvoice}/payments', [SupplierPaymentController::class, 'store'])
        ->name('supplier-invoices.payments.store');

    Route::resource('recurring-supplier-invoices', RecurringSupplierInvoiceController::class)->except(['show']);
    Route::post('recurring-supplier-invoices/generate', [RecurringSupplierInvoiceController::class, 'generate'])
        ->name('recurring-supplier-invoices.generate');

    Route::get('supplier-ap/dashboard', [AccountsPayableDashboardController::class, 'index'])->name('supplier-ap.dashboard');

    Route::resource('employees', EmployeeController::class)->except(['show', 'destroy']);
    Route::resource('payroll-periods', PayrollPeriodController::class)->except(['show', 'destroy']);
    Route::resource('payroll-runs', PayrollRunController::class)->except(['show', 'destroy']);
    Route::post('payroll-runs/{payroll_run}/generate', [PayrollRunController::class, 'generate'])->name('payroll-runs.generate');
    Route::post('payroll-runs/{payroll_run}/recalculate', [PayrollRunController::class, 'recalculate'])->name('payroll-runs.recalculate');
    Route::resource('payroll-entries', PayrollEntryController::class)->only(['index', 'show', 'edit', 'update']);

    // Reporte de errores / problemas
    Route::get('error-report', [ErrorReportController::class, 'create'])->name('error-report.create');
    Route::post('error-report', [ErrorReportController::class, 'store'])->name('error-report.store');

    // Tasa BCV
    Route::get('settings/bcv', [SettingController::class, 'editBcv'])->name('settings.bcv.edit');
    Route::post('settings/bcv', [SettingController::class, 'updateBcv'])->name('settings.bcv.update');

    // Apariencia / tema visual
    Route::get('settings/appearance', [SettingController::class, 'editAppearance'])->name('settings.appearance.edit');
    Route::post('settings/appearance', [SettingController::class, 'updateAppearance'])->name('settings.appearance.update');

    // Configuración de finanzas (activar/desactivar módulo de costos)
    Route::get('settings/finances', [SettingController::class, 'editFinances'])->name('settings.finances.edit');
    Route::post('settings/finances', [SettingController::class, 'updateFinances'])->name('settings.finances.update');

    // Datos fiscales de la empresa (SENIAT)
    Route::get('settings/company', [SettingController::class, 'editCompany'])->name('settings.company.edit');
    Route::post('settings/company', [SettingController::class, 'updateCompany'])->name('settings.company.update');

    // Idioma y moneda
    Route::get('settings/localization', [SettingController::class, 'editLocalization'])->name('settings.localization.edit');
    Route::post('settings/localization', [SettingController::class, 'updateLocalization'])->name('settings.localization.update');

    // Finanzmodul: gastos y consumos (solo si está activado en settings)
    Route::get('finances', [ExpenseController::class, 'index'])->name('finances.index');
    Route::get('finances/create', [ExpenseController::class, 'create'])->name('finances.create');
    Route::post('finances', [ExpenseController::class, 'store'])->name('finances.store');
    Route::get('finances/{expense}/edit', [ExpenseController::class, 'edit'])->name('finances.edit');
    Route::put('finances/{expense}', [ExpenseController::class, 'update'])->name('finances.update');
    Route::delete('finances/{expense}', [ExpenseController::class, 'destroy'])->name('finances.destroy');

    // Finanzreports
    Route::get('finances/reports/monthly', [ExpenseController::class, 'monthlyReport'])->name('finances.reports.monthly');

    // Libro electrónico SENIAT (Compras / Ventas)
    Route::get('fiscal-ledger', [FiscalLedgerController::class, 'index'])->name('fiscal-ledger.index');
    Route::get('fiscal-ledger/tax-report', [FiscalLedgerController::class, 'taxReport'])->name('fiscal-ledger.tax-report');
    Route::get('fiscal-ledger/export-xml', [FiscalLedgerController::class, 'exportXml'])->name('fiscal-ledger.export-xml');

    // Categorías de gastos
    Route::get('finances/categories', [ExpenseCategoryController::class, 'index'])->name('finances.categories.index');
    Route::get('finances/categories/create', [ExpenseCategoryController::class, 'create'])->name('finances.categories.create');
    Route::post('finances/categories', [ExpenseCategoryController::class, 'store'])->name('finances.categories.store');
    Route::get('finances/categories/{category}/edit', [ExpenseCategoryController::class, 'edit'])->name('finances.categories.edit');
    Route::put('finances/categories/{category}', [ExpenseCategoryController::class, 'update'])->name('finances.categories.update');
    Route::delete('finances/categories/{category}', [ExpenseCategoryController::class, 'destroy'])->name('finances.categories.destroy');

    Route::get('ayuda', [HelpCenterController::class, 'index'])->name('help.index');

    // Backup
    Route::get('backup/database', [BackupController::class, 'create'])->name('backup.database');

    // Importación / exportación de productos
    Route::get('products/export/csv', [ProductImportExportController::class, 'exportCsv'])->name('products.export.csv');
    Route::get('products/import', [ProductImportExportController::class, 'importForm'])->name('products.import.form');
    Route::post('products/import', [ProductImportExportController::class, 'import'])->name('products.import');

    // Recetas de productos preparados
    Route::get('products/{product}/recipe', [RecipeController::class, 'edit'])->name('products.recipe.edit');
    Route::put('products/{product}/recipe', [RecipeController::class, 'update'])->name('products.recipe.update');

    // Inventario: stock actual, ajustes y movimientos (kardex)
    Route::get('stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('stock/adjust', [StockController::class, 'adjustForm'])->name('stock.adjust.form');
    Route::post('stock/adjust', [StockController::class, 'adjust'])->name('stock.adjust');
    Route::get('stock/{product}/movements', [StockController::class, 'movements'])->name('stock.movements');

    // TPV / Punto de venta
    Route::get('pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('pos', [PosController::class, 'store'])->name('pos.store');
    Route::post('cash-shifts/open', [CashShiftController::class, 'open'])->name('cash-shifts.open');
    Route::post('cash-shifts/close', [CashShiftController::class, 'close'])->name('cash-shifts.close');

    // Sitios / Depósitos
    Route::resource('warehouses', WarehouseController::class)->except(['show']);
    Route::resource('locations', LocationController::class)->except(['show']);
});

require __DIR__.'/auth.php';