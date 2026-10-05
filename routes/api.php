<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MaterialCategoryController;
use App\Http\Controllers\Api\DesignController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\ConfigurationController;
use App\Http\Controllers\Api\CostTypeController;
use App\Http\Controllers\Api\IndirectCostController;
use App\Http\Controllers\Api\BenefitTypeController;
use App\Http\Controllers\Api\PackagingController;
use App\Http\Controllers\Api\BenefitController;
use App\Http\Controllers\Api\CalculationController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\StatisticController;
use App\Http\Controllers\Api\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Rutas públicas de recuperación de contraseña — SIN autenticación
Route::post('/password/forgot', [PasswordResetController::class, 'forgotPassword'])->middleware('throttle:3,1');
Route::post('/password/verify-code', [PasswordResetController::class, 'verifyCode'])->middleware('throttle:5,1');
Route::post('/password/reset', [PasswordResetController::class, 'resetPassword'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'updateProfile']);
    Route::put('/password', [AuthController::class, 'updatePassword']);

    Route::apiResource('materials', MaterialController::class);
    Route::apiResource('material-categories', MaterialCategoryController::class);
    Route::apiResource('designs', DesignController::class);
    Route::post('/designs/{design}/image', [DesignController::class, 'uploadImage']);
    Route::delete('/designs/{design}/image', [DesignController::class, 'deleteImage']);

    Route::get('/configuration', [ConfigurationController::class, 'show']);
    Route::put('/configuration', [ConfigurationController::class, 'update']);

    Route::apiResource('cost-types', CostTypeController::class);
    Route::apiResource('indirect-costs', IndirectCostController::class);

    Route::apiResource('benefit-types', BenefitTypeController::class);
    Route::apiResource('benefits', BenefitController::class);

    Route::apiResource('packagings', PackagingController::class);

    Route::get('/calculations', [CalculationController::class, 'index']);
    Route::post('/calculations', [CalculationController::class, 'store']);
    Route::get('/calculations/{calculation}', [CalculationController::class, 'show']);
    Route::delete('/calculations/{calculation}', [CalculationController::class, 'destroy']);
    Route::post('/calculations/{calculation}/mark-sold', [CalculationController::class, 'markSold']);

    Route::get('/histories', [HistoryController::class, 'index']);

    Route::get('/statistics/summary', [StatisticController::class, 'summary']);
});
