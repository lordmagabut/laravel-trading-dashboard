<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BotPairSettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarketChartController;
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\SignalDashboardController;
use App\Http\Controllers\TechnicalAnalysisWorkflowController;
use App\Http\Controllers\TechnicalContextController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

Route::middleware('guest')->group(function () {
    Route::get('/auth/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/auth/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/auth/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/auth/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/auth/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])
        ->middleware('permission:view dashboard')
        ->name('dashboard');

    Route::get('/market-chart', [MarketChartController::class, 'index'])
        ->middleware('permission:view market chart')
        ->name('market.chart');

    Route::get('/market-chart/candles', [MarketChartController::class, 'candles'])
        ->middleware('permission:view market chart')
        ->name('market.chart.candles');

    Route::get('/technical-context', [TechnicalContextController::class, 'page'])
        ->middleware('permission:view technical context')
        ->name('technical.context.page');

    Route::get('/api/technical-context', [TechnicalContextController::class, 'api'])
        ->middleware('permission:view technical context')
        ->name('technical.context.api');

    Route::middleware('permission:manage technical analyses')->group(function () {
        Route::get('/technical-analyses', [TechnicalAnalysisWorkflowController::class, 'index'])
            ->name('technical-analyses.index');

        Route::post('/technical-analyses/generate', [TechnicalAnalysisWorkflowController::class, 'generate'])
            ->name('technical-analyses.generate');

        Route::get('/technical-analyses/{technicalAnalysis}', [TechnicalAnalysisWorkflowController::class, 'show'])
            ->name('technical-analyses.show');
    });

    Route::middleware('permission:manage bot pairs')->group(function () {
        Route::get('/bot-pair-settings', [BotPairSettingController::class, 'index'])
            ->name('bot-pairs.index');

        Route::post('/bot-pair-settings', [BotPairSettingController::class, 'store'])
            ->name('bot-pairs.store');

        Route::get('/bot-pair-settings/{tradingBotPair}/edit', [BotPairSettingController::class, 'edit'])
            ->name('bot-pairs.edit');

        Route::put('/bot-pair-settings/{tradingBotPair}', [BotPairSettingController::class, 'update'])
            ->name('bot-pairs.update');

        Route::post('/bot-pair-settings/{tradingBotPair}/toggle-enabled', [BotPairSettingController::class, 'toggleEnabled'])
            ->name('bot-pairs.toggle-enabled');

        Route::post('/bot-pair-settings/{tradingBotPair}/toggle-auto-generate', [BotPairSettingController::class, 'toggleAutoGenerate'])
            ->name('bot-pairs.toggle-auto-generate');

        Route::delete('/bot-pair-settings/{tradingBotPair}', [BotPairSettingController::class, 'destroy'])
            ->name('bot-pairs.destroy');
    });

    Route::middleware('permission:review trade signals')->group(function () {
        Route::get('/signal-dashboard', [SignalDashboardController::class, 'index'])
            ->name('signals.index');

        Route::post('/signal-dashboard/{tradeSignal}/approve', [SignalDashboardController::class, 'approve'])
            ->name('signals.approve');

        Route::post('/signal-dashboard/{tradeSignal}/reject', [SignalDashboardController::class, 'reject'])
            ->name('signals.reject');

        Route::post('/signal-dashboard/{tradeSignal}/cancel', [SignalDashboardController::class, 'cancel'])
            ->name('signals.cancel');

        Route::post('/signal-dashboard/{tradeSignal}/send-to-executor', [SignalDashboardController::class, 'sendToExecutor'])
            ->name('signals.sendToExecutor');
    });

    Route::middleware('permission:manage users')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    });

    Route::middleware('permission:manage roles')->group(function () {
        Route::get('/roles', [RoleManagementController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', [RoleManagementController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RoleManagementController::class, 'store'])->name('roles.store');
        Route::get('/roles/{role}/edit', [RoleManagementController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [RoleManagementController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleManagementController::class, 'destroy'])->name('roles.destroy');
    });

    Route::group(['prefix' => 'email'], function () {
        Route::get('inbox', function () { return view('pages.email.inbox'); });
        Route::get('read', function () { return view('pages.email.read'); });
        Route::get('compose', function () { return view('pages.email.compose'); });
    });

    Route::group(['prefix' => 'apps'], function () {
        Route::get('chat', function () { return view('pages.apps.chat'); });
        Route::get('calendar', function () { return view('pages.apps.calendar'); });
    });

    Route::group(['prefix' => 'ui-components'], function () {
        Route::get('accordion', function () { return view('pages.ui-components.accordion'); });
        Route::get('alerts', function () { return view('pages.ui-components.alerts'); });
        Route::get('badges', function () { return view('pages.ui-components.badges'); });
        Route::get('breadcrumbs', function () { return view('pages.ui-components.breadcrumbs'); });
        Route::get('buttons', function () { return view('pages.ui-components.buttons'); });
        Route::get('button-group', function () { return view('pages.ui-components.button-group'); });
        Route::get('cards', function () { return view('pages.ui-components.cards'); });
        Route::get('carousel', function () { return view('pages.ui-components.carousel'); });
        Route::get('collapse', function () { return view('pages.ui-components.collapse'); });
        Route::get('dropdowns', function () { return view('pages.ui-components.dropdowns'); });
        Route::get('list-group', function () { return view('pages.ui-components.list-group'); });
        Route::get('media-object', function () { return view('pages.ui-components.media-object'); });
        Route::get('modal', function () { return view('pages.ui-components.modal'); });
        Route::get('navs', function () { return view('pages.ui-components.navs'); });
        Route::get('navbar', function () { return view('pages.ui-components.navbar'); });
        Route::get('pagination', function () { return view('pages.ui-components.pagination'); });
        Route::get('popovers', function () { return view('pages.ui-components.popovers'); });
        Route::get('progress', function () { return view('pages.ui-components.progress'); });
        Route::get('scrollbar', function () { return view('pages.ui-components.scrollbar'); });
        Route::get('scrollspy', function () { return view('pages.ui-components.scrollspy'); });
        Route::get('spinners', function () { return view('pages.ui-components.spinners'); });
        Route::get('tabs', function () { return view('pages.ui-components.tabs'); });
        Route::get('tooltips', function () { return view('pages.ui-components.tooltips'); });
    });

    Route::group(['prefix' => 'advanced-ui'], function () {
        Route::get('cropper', function () { return view('pages.advanced-ui.cropper'); });
        Route::get('owl-carousel', function () { return view('pages.advanced-ui.owl-carousel'); });
        Route::get('sortablejs', function () { return view('pages.advanced-ui.sortablejs'); });
        Route::get('sweet-alert', function () { return view('pages.advanced-ui.sweet-alert'); });
    });

    Route::group(['prefix' => 'forms'], function () {
        Route::get('basic-elements', function () { return view('pages.forms.basic-elements'); });
        Route::get('advanced-elements', function () { return view('pages.forms.advanced-elements'); });
        Route::get('editors', function () { return view('pages.forms.editors'); });
        Route::get('wizard', function () { return view('pages.forms.wizard'); });
    });

    Route::group(['prefix' => 'charts'], function () {
        Route::get('apex', function () { return view('pages.charts.apex'); });
        Route::get('chartjs', function () { return view('pages.charts.chartjs'); });
        Route::get('flot', function () { return view('pages.charts.flot'); });
        Route::get('peity', function () { return view('pages.charts.peity'); });
        Route::get('sparkline', function () { return view('pages.charts.sparkline'); });
    });

    Route::group(['prefix' => 'tables'], function () {
        Route::get('basic-tables', function () { return view('pages.tables.basic-tables'); });
        Route::get('data-table', function () { return view('pages.tables.data-table'); });
    });

    Route::group(['prefix' => 'icons'], function () {
        Route::get('feather-icons', function () { return view('pages.icons.feather-icons'); });
        Route::get('mdi-icons', function () { return view('pages.icons.mdi-icons'); });
    });

    Route::group(['prefix' => 'general'], function () {
        Route::get('blank-page', function () { return view('pages.general.blank-page'); });
        Route::get('faq', function () { return view('pages.general.faq'); });
        Route::get('invoice', function () { return view('pages.general.invoice'); });
        Route::get('profile', function () { return view('pages.general.profile'); });
        Route::get('pricing', function () { return view('pages.general.pricing'); });
        Route::get('timeline', function () { return view('pages.general.timeline'); });
    });

    Route::group(['prefix' => 'error'], function () {
        Route::get('404', function () { return view('pages.error.404'); });
        Route::get('500', function () { return view('pages.error.500'); });
    });

    Route::get('/clear-cache', function () {
        Artisan::call('cache:clear');
        return 'Cache is cleared';
    });
});

Route::any('/{page?}', function () {
    return View::make('pages.error.404');
})->where('page', '.*');
