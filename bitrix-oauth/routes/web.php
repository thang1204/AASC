<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BitrixController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/bitrix/oauth/callback', [BitrixController::class, 'handleInstall']);
Route::get('/bitrix/test-api', [BitrixController::class, 'testApi']);

Route::prefix('bitrix/contacts')->group(function () {
    Route::get('/', [BitrixController::class, 'index'])->name('bitrix.contacts.index');
    Route::get('/create', [BitrixController::class, 'create'])->name('bitrix.contacts.create');
    Route::post('/store', [BitrixController::class, 'store'])->name('bitrix.contacts.store');
    Route::get('/{id}/edit', [BitrixController::class, 'edit'])->name('bitrix.contacts.edit');
    Route::put('/{id}', [BitrixController::class, 'update'])->name('bitrix.contacts.update');
    Route::delete('/{id}', [BitrixController::class, 'destroy'])->name('bitrix.contacts.destroy');

});