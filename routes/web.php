<?php

use App\Http\Controllers\Admin\LabController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Публичная часть
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

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
    });
