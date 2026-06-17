<?php

use App\Http\Controllers\Admin\ReportingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\TagController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{post}', [PostController::class, 'show']);
Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
Route::get('/posts/{post}/comments/{comment}', [CommentController::class, 'show']);

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/reporting')->group(function () {
    Route::get('/posts', [ReportingController::class, 'posts']);
    Route::get('/comments', [ReportingController::class, 'comments']);
    Route::get('/analytics', [ReportingController::class, 'analytics']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::put('/posts/{post}', [PostController::class, 'update']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);
    Route::post('/posts/{post}/tags/{tag}', [TagController::class, 'assign']);
    Route::delete('/posts/{post}/tags/{tag}', [TagController::class, 'unassign']);

    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::put('/posts/{post}/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/posts/{post}/comments/{comment}', [CommentController::class, 'destroy']);
    Route::post('/posts/{post}/comments/{comment}/flag', [CommentController::class, 'flag']);
    Route::delete('/posts/{post}/comments/{comment}/flag', [CommentController::class, 'unflag']);
});
