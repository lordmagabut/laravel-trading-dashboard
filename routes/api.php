<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
use App\Http\Controllers\TechnicalAnalysisWorkflowController;

Route::post('/technical-analyses/generate', [TechnicalAnalysisWorkflowController::class, 'generate'])
    ->name('api.technical-analyses.generate');

Route::get('/technical-analyses/pending', [TechnicalAnalysisWorkflowController::class, 'pendingTechnicalAnalyses'])
    ->name('api.technical-analyses.pending');

Route::post('/technical-analyses/{technicalAnalysis}/technical-result', [TechnicalAnalysisWorkflowController::class, 'technicalResult'])
    ->name('api.technical-analyses.technical-result');

Route::post('/technical-analyses/{technicalAnalysis}/ai-result', [TechnicalAnalysisWorkflowController::class, 'aiResult'])
    ->name('api.technical-analyses.ai-result');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
