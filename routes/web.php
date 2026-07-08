<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('contact.thanks');
});

Route::middleware('auth')->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    Route::get('/admin/contacts/{contact}', [AdminController::class, 'show'])->name('admin.contacts.show');
    Route::post('/admin/tags', [AdminController::class, 'storeTag'])->name('admin.tags.store');
    Route::get('/admin/tags/{tag}/edit', [AdminController::class, 'editTag'])->name('admin.tags.edit');
    Route::put('/admin/tags/{tag}', [AdminController::class, 'updateTag'])->name('admin.tags.update');
    Route::delete('/admin/tags/{tag}', [AdminController::class, 'destroyTag'])->name('admin.tags.destroy');
});
