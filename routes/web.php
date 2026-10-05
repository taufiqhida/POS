<?php

use App\Http\Controllers\PosAuthController;
use App\Http\Controllers\ReceiptController;
use App\Livewire\Pos;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/kasir');

Route::get('/kasir/login', [PosAuthController::class, 'show'])->name('pos.login');
Route::post('/kasir/login', [PosAuthController::class, 'login'])->name('pos.login.submit');
Route::post('/kasir/logout', [PosAuthController::class, 'logout'])->name('pos.logout');

Route::middleware('pos.auth')->group(function () {
    Route::get('/kasir', Pos::class)->name('pos');
    Route::get('/kasir/struk/{transaction}', [ReceiptController::class, 'show'])->name('receipt');
});

// Struk digital untuk pembeli (link bertanda tangan, tanpa login).
Route::get('/struk/{transaction}', [ReceiptController::class, 'show'])->middleware('signed')->name('receipt.public');
