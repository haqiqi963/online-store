<?php

use App\Http\Controllers\admin\ProductController;
use App\Http\Controllers\admin\TransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\MyTransactionController;
use App\Http\Controllers\ProductGalleryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [FrontendController::class, 'index'])->name('frontend.index');
Route::get('/details/{slug}', [FrontendController::class, 'details'])->name('frontend.details');

Route::middleware(['auth:sanctum', 'verified',])->group(function () {
    Route::get('/cart', [FrontendController::class, 'cart'])->name('frontend.cart');
    Route::post('/cart/{id}', [FrontendController::class, 'cartAdd'])->name('frontend.cart-add');
    Route::delete('/cart/{id}', [FrontendController::class, 'cartDelete'])->name('frontend.cart-delete');
    Route::post('/checkout', [FrontendController::class, 'checkout'])->name('frontend.checkout');
    Route::get('/checkout/success', [FrontendController::class, 'success'])->name('checkout-success');



});

Route::middleware(['auth:sanctum', 'verified',])->name('dashboard.')->prefix('dashboard')->group(function () {
   Route::get('/', [DashboardController::class, 'index'])->name('index');

    Route::resource('my-transaction', MyTransactionController::class)->only([
        'index', 'show'
    ]);

   Route::middleware(['admin'])->group(function () {
       Route::resource('product', ProductController::class);
       Route::resource('product.gallery', ProductGalleryController::class)->shallow()->only([
           'index', 'create', 'store', 'destroy'
       ]);
       Route::resource('transaction', TransactionController::class)->only([
           'index', 'show', 'edit', 'update'
       ]);
       Route::resource('user', UserController::class)->only([
           'index', 'edit', 'update', 'destroy'
       ]);
   });
});
