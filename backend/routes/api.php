<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SpaceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/spaces', [SpaceController::class, 'index']);
Route::put('/spaces/{id}', [SpaceController::class, 'update']);
// Public Routes (No login required)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes (Login required)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    // Profile routes
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/profile/password', [AuthController::class, 'changePassword']);
    // You can move your '/spaces' route here later if you want it private!
    
    // Admin routes
    Route::get('/admin/dashboard-stats', [AdminController::class, 'getDashboardStats']);
    Route::get('/admin/users', [AdminController::class, 'getUsers']);
    Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser']);
    Route::post('/admin/spaces', [AdminController::class, 'createSpace']);
    Route::delete('/admin/spaces/{id}', [AdminController::class, 'deleteSpace']);
    Route::get('/admin/activity-logs', [AdminController::class, 'getActivityLogs']);
});