<?php

use App\Http\Controllers\CallController;
use App\Http\Controllers\CallStatuseController;
use App\Http\Controllers\CounterAssignmentController;
use App\Http\Controllers\CounterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TvController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Dentro de tu grupo middleware(['auth', 'verified'])
Route::middleware(['auth'])->group(function () {

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

    Route::resource('counters', CounterController::class)->except('show');
    Route::resource('callStatuses', CallStatuseController::class)->only('index');
    Route::resource('services', ServiceController::class)->except('show');
    Route::resource('users', UserController::class)->except('show');
    Route::resource('roles', \App\Http\Controllers\RoleController::class)->except('show');
    Route::resource('permissions', \App\Http\Controllers\PermissionController::class)->except('show');
    Route::resource('counterAssignments', CounterAssignmentController::class)->only('index', 'create', 'store', 'destroy');

    Route::get('/sistema', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/sistema', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/sistema/regenerate-token', [SettingController::class, 'regenerateToken'])
        ->name('settings.regenerate_token');

    Route::get('/calls', [CallController::class, 'index'])->name('calls.index');

    // Acciones
    Route::post('/calls/call-next', [CallController::class, 'callNext'])->name('calls.call-next');
    Route::post('/calls/recall', [CallController::class, 'recall'])->name('calls.recall');
    Route::post('/calls/start', [CallController::class, 'start'])->name('calls.start');
    Route::post('/calls/finish', [CallController::class, 'finish'])->name('calls.finish');
    Route::post('/calls/abandon', [CallController::class, 'abandon'])->name('calls.abandon');
    Route::post('/calls/transfer', [CallController::class, 'transfer'])->name('calls.transfer');

    // Notificaciones
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/preferences', [NotificationController::class, 'updatePreferences'])->name('notifications.updatePreferences');

    Route::middleware('can:reportes.index')->prefix('reports')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/tickets', [ReportController::class, 'tickets'])->name('reports.tickets');
        Route::get('/tickets/export', [ReportController::class, 'exportTickets'])->name('reports.tickets.export');
        Route::get('/calls', [ReportController::class, 'calls'])->name('reports.calls');
        Route::get('/calls/export', [ReportController::class, 'exportCalls'])->name('reports.calls.export');
        Route::get('/performance', [ReportController::class, 'performance'])->name('reports.performance');
        Route::get('/performance/export', [ReportController::class, 'exportPerformance'])->name('reports.performance.export');
    });

});

Route::post('/tickets/{token}', [TicketController::class, 'store'])
    ->name('tickets.store');
Route::get('/tickets/{token}', [TicketController::class, 'create'])
    ->name('tickets.create');

Route::get('/tv', [TvController::class, 'index'])->name('tv.index');

require __DIR__.'/settings.php';

/*Route::get('/php-path', function () {
    // Esto pregunta al sistema: "¿Dónde está el comando 'php' normal?"
    $path = shell_exec('which php');

    // Esto verifica la versión de ese php específico
    $version = shell_exec('/usr/local/bin/php -v');

    return "Ruta sugerida: " . $path . "<br>Versión detectada: <pre>" . $version . "</pre>";
});*/
