<?php

use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\TransferLogController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;



require_once __DIR__.'/admin.php';

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/profile', [HomeController::class, 'profile'])->name('profile');

// auth routes
Route::get('/login', [LoginController::class, 'showLogin'])->name('showLogin');
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::post('logout', [LoginController::class, 'logout'])->name('logout');
Route::get('get-change-password', [LoginController::class, 'changePassword'])->name('getChangePassword');
Route::post('update-password/{user}', [LoginController::class, 'updatePassword'])->name('updatePassword');





