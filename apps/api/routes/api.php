<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\AuthenticatedUserController;
use App\Http\Controllers\Api\V1\AssetCategoryController;
use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\CurrentSaleDraftController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\ProductCategoryController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductStockAdjustmentController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\SaleDraftItemController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthController::class)->name('api.v1.health');

    Route::prefix('auth')->group(function (): void {
        Route::post('/login', LoginController::class)->name('api.v1.auth.login');
        Route::get('/me', AuthenticatedUserController::class)
            ->middleware('auth:sanctum')
            ->name('api.v1.auth.me');
        Route::post('/logout', LogoutController::class)
            ->middleware('auth:sanctum')
            ->name('api.v1.auth.logout');
    });

    Route::middleware(['auth:sanctum', 'role.admin'])->group(function (): void {
        Route::get('/roles', RoleController::class)->name('api.v1.roles.index');
        Route::apiResource('users', UserController::class);
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::apiResource('product-categories', ProductCategoryController::class)->only(['index', 'show']);
        Route::apiResource('products', ProductController::class)->only(['index', 'show']);
        Route::apiResource('asset-categories', AssetCategoryController::class)->only(['index', 'show']);
        Route::apiResource('assets', AssetController::class)->only(['index', 'show']);
        Route::apiResource('customers', CustomerController::class)->only(['index', 'show']);
        Route::get('/sales/draft', [CurrentSaleDraftController::class, 'show'])->name('sales.draft.show');
        Route::patch('/sales/draft', [CurrentSaleDraftController::class, 'update'])->name('sales.draft.update');
        Route::post('/sales/draft/items', [SaleDraftItemController::class, 'store'])->name('sales.draft-items.store');
        Route::patch('/sales/draft/items/{saleDraftItem}', [SaleDraftItemController::class, 'update'])
            ->name('sales.draft-items.update');
        Route::delete('/sales/draft/items/{saleDraftItem}', [SaleDraftItemController::class, 'destroy'])
            ->name('sales.draft-items.destroy');
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
        Route::apiResource('suppliers', SupplierController::class)->only(['index', 'show']);
    });

    Route::middleware(['auth:sanctum', 'role.admin'])->group(function (): void {
        Route::apiResource('product-categories', ProductCategoryController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('products', ProductController::class)->only(['store', 'update', 'destroy']);
        Route::post('/products/{product}/stock-adjustments', ProductStockAdjustmentController::class)
            ->name('products.stock-adjustments.store');
        Route::apiResource('asset-categories', AssetCategoryController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('assets', AssetController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('customers', CustomerController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('suppliers', SupplierController::class)->only(['store', 'update', 'destroy']);
    });
});
