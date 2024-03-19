<?php

use Illuminate\Support\Facades\Route;
use Modules\TomatoSaaS\App\Http\Controllers\TomatoSaaSController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === config('tenancy.central_domains.0')) {
    Route::middleware(['web','auth', 'splade', 'verified'])->name('admin.')->group(function () {
        Route::get('admin/saas', [\Modules\TomatoSaaS\App\Http\Controllers\SyncController::class, 'index'])->name('syncs.index');
        Route::get('admin/saas/api', [\Modules\TomatoSaaS\App\Http\Controllers\SyncController::class, 'api'])->name('syncs.api');
        Route::get('admin/saas/create', [\Modules\TomatoSaaS\App\Http\Controllers\SyncController::class, 'create'])->name('syncs.create');
        Route::post('admin/saas', [\Modules\TomatoSaaS\App\Http\Controllers\SyncController::class, 'store'])->name('syncs.store');
        Route::get('admin/saas/{model}', [\Modules\TomatoSaaS\App\Http\Controllers\SyncController::class, 'show'])->name('syncs.show');
        Route::get('admin/saas/{model}/edit', [\Modules\TomatoSaaS\App\Http\Controllers\SyncController::class, 'edit'])->name('syncs.edit');
        Route::get('admin/saas/{model}/impersonate', [\Modules\TomatoSaaS\App\Http\Controllers\SyncController::class, 'impersonate'])->name('syncs.impersonate');
        Route::post('admin/saas/{model}', [\Modules\TomatoSaaS\App\Http\Controllers\SyncController::class, 'update'])->name('syncs.update');
        Route::delete('admin/saas/{model}', [\Modules\TomatoSaaS\App\Http\Controllers\SyncController::class, 'destroy'])->name('syncs.destroy');
    });
}

Route::get('admin/login/url', [\Modules\TomatoSaaS\App\Http\Controllers\SyncController::class, 'url'])->name('login.url')->middleware(['web','splade']);