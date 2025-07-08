<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Technician\JobController as TechnicianJobController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//user
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders/{order}/upload-photo', [OrderController::class, 'uploadDamagePhoto']);
    Route::post('/orders/{order}/upload-payment', [OrderController::class, 'uploadPaymentProof']);

    Route::get('/categories', [AdminCategoryController::class, 'index']);
});

//admin
Route::middleware(['auth:sanctum', 'is.admin'])->prefix('admin')->group(function () {
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::put('/orders/{order}', [AdminOrderController::class, 'update']);
    Route::apiResource('/categories', AdminCategoryController::class);
    Route::post('/orders/{order}/validate-payment', [AdminOrderController::class, 'validatePayment']);
    Route::get('/orders/{order}/download-invoice', [AdminOrderController::class, 'downloadInvoice']);
});

//teknisi
Route::middleware(['auth:sanctum', 'is.technician'])->prefix('technician')->group(function () {
    Route::get('/jobs', [TechnicianJobController::class, 'index']);
    Route::put('/jobs/{order}/status', [TechnicianJobController::class, 'updateStatus']);
});
