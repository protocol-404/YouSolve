<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\QuizController;
use App\Http\Controllers\API\ClassController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\QuestionController;
use App\Http\Controllers\API\AnswerController;
use App\Http\Controllers\API\QuizResultController;
use App\Http\Middleware\JWTAuthentication;




Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']);


Route::middleware([JWTAuthentication::class])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);


    Route::apiResource('users', UserController::class);


    Route::apiResource('quizzes', QuizController::class);


    Route::apiResource('classes', ClassController::class);


    Route::apiResource('categories', CategoryController::class);


    Route::apiResource('questions', QuestionController::class);


    Route::apiResource('answers', AnswerController::class);


    Route::apiResource('quiz-results', QuizResultController::class);
});
