<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MovieController;
use App\Http\Controllers\Admin\ShowtimeController;
use App\Http\Controllers\Admin\BookingController;

/*
|--------------------------------------------------------------------------
| Routes Luồng D: Quản Trị Hệ Thống (Cinego)
| Đã được bọc sẵn: prefix('/admin'), name('admin.'), middleware('auth', 'admin')
|--------------------------------------------------------------------------
*/

// 1. Dashboard Tổng quan & Báo cáo doanh thu
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// 2. Quản lý Phim
Route::resource('movies', MovieController::class);
Route::get('movies/check-name', [MovieController::class, 'checkName'])->name('movies.checkName');
Route::post('movies/store-ajax', [MovieController::class, 'storeAjax'])->name('movies.storeAjax');
Route::post('movies/quick-status', [MovieController::class, 'quickStatus'])->name('movies.quickStatus');

// 3. Quản lý Suất chiếu
Route::resource('showtimes', ShowtimeController::class);

// 4. Quản lý Đơn vé
Route::resource('bookings', BookingController::class);
Route::post('bookings/quick-status', [BookingController::class, 'quickStatus'])->name('bookings.quickStatus');
Route::get('bookings/get-seats/{showtime}', [BookingController::class, 'getShowtimeSeats'])->name('bookings.getSeats');