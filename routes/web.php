<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\StatementController;
use App\Http\Controllers\TransactionController;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('statements/{statement}/export', [StatementController::class, 'export'])->name('statements.export');
    Route::resource('statements', StatementController::class);
    
    // Manual Transactions
    Route::get('transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
});
