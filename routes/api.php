<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\QuizController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('quizzes', [QuizController::class, 'index']);
        Route::get('quizzes/{quiz}', [QuizController::class, 'show']);
        Route::post('quizzes/{quiz}/attempts', [QuizController::class, 'submitAttempt']);

        Route::middleware('role:admin|staff')->group(function () {
            Route::post('quizzes', [QuizController::class, 'store']);
            Route::put('quizzes/{quiz}', [QuizController::class, 'update']);
            Route::delete('quizzes/{quiz}', [QuizController::class, 'destroy']);

            Route::get('questions', [QuestionController::class, 'index']);
            Route::post('questions', [QuestionController::class, 'store']);
            Route::put('questions/{question}', [QuestionController::class, 'update']);
            Route::delete('questions/{question}', [QuestionController::class, 'destroy']);

            Route::get('members', [MemberController::class, 'index']);
            Route::post('members', [MemberController::class, 'store']);

            Route::get('attendance', [AttendanceController::class, 'index']);
            Route::post('attendance', [AttendanceController::class, 'store']);
        });
    });
});
