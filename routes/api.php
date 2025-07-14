<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Technician\JobController as TechnicianJobController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\Admin\TechnicianController;
use App\Http\Controllers\ServiceRatingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rute Publik
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rute Umum (Semua Peran yang Login)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/user/update', [ProfileController::class, 'update']);
    // Pelanggan bisa melihat kategori, jadi taruh di sini
    Route::get('/categories', [AdminCategoryController::class, 'index']);
});


// --- RUTE KHUSUS PELANGGAN ---
// Semua rute di sini akan diawali dengan /api/customer/...
Route::middleware(['auth:sanctum'])->prefix('customer')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/upload-photo', [OrderController::class, 'uploadDamagePhoto']);
    Route::post('/orders/{order}/upload-payment', [OrderController::class, 'uploadPaymentProof']);
    Route::delete('/orders/{order}', [OrderController::class, 'cancelOrder']);
      Route::get('/orders/{order}/download-invoice', [OrderController::class, 'downloadInvoice']);
});


// --- RUTE KHUSUS ADMIN ---
Route::middleware(['auth:sanctum', 'is.admin'])->prefix('admin')->group(function () {
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::get('/orders/{order}', [AdminOrderController::class, 'show']);
    Route::post('/orders/{order}', [AdminOrderController::class, 'update']);
    Route::post('/orders/{order}/validate-payment', [AdminOrderController::class, 'validatePayment']);
    Route::get('/orders/{order}/download-invoice', [AdminOrderController::class, 'downloadInvoice']);
    Route::get('/technicians', [TechnicianController::class, 'index']);
    Route::apiResource('/categories', AdminCategoryController::class);
    Route::post('/orders/{order}/set-invoice', [AdminOrderController::class, 'setInvoiceAmount']);
});


// --- RUTE KHUSUS TEKNISI ---
Route::middleware(['auth:sanctum', 'is.technician'])->prefix('technician')->group(function () {
    Route::get('/jobs', [TechnicianJobController::class, 'index']);
    Route::put('/jobs/{order}/status', [TechnicianJobController::class, 'updateStatus']);
    Route::get('/jobs/{order}', [TechnicianJobController::class, 'show']);
    Route::post('/jobs/{order}/notes', [OrderController::class, 'addTechnicianNotes']);
});

// Routes untuk rating
Route::middleware(['auth:sanctum'])->group(function () {
    // Customer bisa rating order yang sudah selesai
    Route::post('/customer/orders/{order}/rate', [ServiceRatingController::class, 'store']);
    Route::get('/customer/orders/{order}/rating', [ServiceRatingController::class, 'show']);
    
    // Teknisi bisa lihat rating mereka
    Route::get('/technician/ratings', [ServiceRatingController::class, 'getTechnicianRatings']);
});