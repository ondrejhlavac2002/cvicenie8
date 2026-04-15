<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::put('/password', [AuthController::class, 'changePassword']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/profile-photo', [AuthController::class, 'storeProfilePhoto']);
        Route::delete('/profile-photo', [AuthController::class, 'destroyProfilePhoto']);
    });
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('notes/my', [NoteController::class, 'myNotes']);
    Route::get('notes/stats/status', [NoteController::class, 'statsByStatus']);
    Route::patch('notes/actions/archive-old-drafts', [NoteController::class, 'archiveOldDrafts']);
    Route::get('users/{userId}/notes', [NoteController::class, 'userNotesWithCategories']);
    Route::get('notes-actions/search', [NoteController::class, 'search']);
    Route::patch('notes/{note}/pin', [NoteController::class, 'pin']);
    Route::patch('notes/{note}/unpin', [NoteController::class, 'unpin']);
    Route::patch('notes/{note}/publish', [NoteController::class, 'publish']);
    Route::patch('notes/{note}/archive', [NoteController::class, 'archive']);

    Route::apiResource('notes', NoteController::class);
    Route::apiResource('notes.tasks', TaskController::class)->scoped();

    Route::get('notes/{note}/comments', [CommentController::class, 'indexForNote']);
    Route::post('notes/{note}/comments', [CommentController::class, 'storeForNote']);
    Route::get('tasks/{task}/comments', [CommentController::class, 'indexForTask']);
    Route::post('tasks/{task}/comments', [CommentController::class, 'storeForTask']);
    Route::put('comments/{comment}', [CommentController::class, 'update']);
    Route::delete('comments/{comment}', [CommentController::class, 'destroy']);

    Route::middleware('premium')->group(function () {
        Route::post('notes/{note}/attachments', [AttachmentController::class, 'store']);
    });

    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy']);
    Route::get('attachments/{attachment}/link', [AttachmentController::class, 'link']);

    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);

    Route::middleware('admin')->group(function () {
        Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
    });
});
