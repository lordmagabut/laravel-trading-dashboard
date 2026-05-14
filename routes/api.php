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

Route::apiResource('fundamental-analyses', App\Http\Controllers\FundamentalAnalysisController::class);
Route::get('fundamental-analyses/latest', [App\Http\Controllers\FundamentalAnalysisController::class, 'latest'])
    ->name('api.fundamental-analyses.latest');
Route::get('fundamental-analyses/pending', [App\Http\Controllers\FundamentalAnalysisController::class, 'pendingFundamentalAnalyses'])
    ->name('api.fundamental-analyses.pending');
Route::post('fundamental-analyses/{id}/submit-result', [App\Http\Controllers\FundamentalAnalysisController::class, 'submitResult'])
    ->name('api.fundamental-analyses.submit-result');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
