<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\MovieController;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // 1. Xem danh sách phim
    Route::get('/movies', [MovieController::class, 'index'])->name('movies.index');
    
    // 2.kiểm tra tên phim khi blur 
    Route::get('/movies/kiem-tra-ten', [MovieController::class, 'kiemTraTenPhim'])->name('movies.checkName');
    
    // 3.thêm phim mới
    Route::post('/movies/them-phim', [MovieController::class, 'themPhimAjax'])->name('movies.storeAjax');
    
    // 4. Xóa phim
    Route::get('/movies/xoa/{id}', [MovieController::class, 'xoaPhim'])->name('movies.destroy');
});