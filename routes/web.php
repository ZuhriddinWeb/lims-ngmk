<?php

use App\Http\Controllers\Admin\LabController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Api\IngestController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LabPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Публичная часть
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/labs/{lab}', [LabPageController::class, 'show'])->whereNumber('lab')->name('labs.show');

/*
| Zavod LIMS'laridan jonli ma'lumot (imzo bilan; CSRF talab qilinmaydi — bootstrap/app.php)
*/
Route::post('/api/ingest', IngestController::class)
    ->middleware('throttle:120,1')
    ->name('ingest');

/*
|--------------------------------------------------------------------------
| Аутентификация (вход в админку)
|--------------------------------------------------------------------------
*/
Route::get('/admin/login', [LoginController::class, 'create'])->name('login');
Route::post('/admin/login', [LoginController::class, 'store']);

Route::post('/admin/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Админ-панель (только для авторизованных)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::redirect('/', '/admin/labs');
        Route::resource('labs', LabController::class)->except(['show']);
        Route::post('labs/{lab}/key', [LabController::class, 'issueKey'])->name('labs.key');
        Route::delete('labs/{lab}/key', [LabController::class, 'revokeKey'])->name('labs.key.revoke');
    });
