<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\ProblemController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\UserController;

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

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Permission routes
    Route::get('/roles', [PermissionController::class, 'getRoles']);
    Route::get('/check-role/{role}', [PermissionController::class, 'checkRole']);
    Route::get('/permissions', [PermissionController::class, 'getUserPermissions']);
    
    // User management routes
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
    Route::get('/users/{user}/progress', [UserController::class, 'progress']);
    Route::get('/my-progress', [UserController::class, 'progress']);
    
    // Evaluation routes
    Route::get('/evaluations', [EvaluationController::class, 'index']);
    Route::get('/evaluations/{evaluation}', [EvaluationController::class, 'show']);
    
    // Problem routes
    Route::get('/problems', [ProblemController::class, 'index']);
    Route::get('/problems/{problem}', [ProblemController::class, 'show']);
    
    // Submission routes
    Route::get('/submissions', [SubmissionController::class, 'index']);
    Route::post('/submissions', [SubmissionController::class, 'store']);
    Route::get('/submissions/{submission}', [SubmissionController::class, 'show']);
    Route::delete('/submissions/{submission}', [SubmissionController::class, 'destroy']);
    
    // Result routes
    Route::get('/results', [ResultController::class, 'index']);
    Route::get('/results/{result}', [ResultController::class, 'show']);
    
    // Admin only routes
    Route::middleware('role:administrator')->group(function () {
        // Evaluation management
        Route::post('/evaluations', [EvaluationController::class, 'store']);
        Route::put('/evaluations/{evaluation}', [EvaluationController::class, 'update']);
        Route::delete('/evaluations/{evaluation}', [EvaluationController::class, 'destroy']);
        
        // Problem management
        Route::post('/problems', [ProblemController::class, 'store']);
        Route::put('/problems/{problem}', [ProblemController::class, 'update']);
        Route::delete('/problems/{problem}', [ProblemController::class, 'destroy']);
        
        // Result management
        Route::delete('/results/{result}', [ResultController::class, 'destroy']);
    });
    
    // Instructor routes
    Route::middleware('role:administrator,instructor')->group(function () {
        // Result management for instructors
        Route::post('/results', [ResultController::class, 'store']);
        Route::put('/results/{result}', [ResultController::class, 'update']);
        
        // Submission management for instructors
        Route::put('/submissions/{submission}', [SubmissionController::class, 'update']);
    });
});
