<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LicenseSuspendedController;
use App\Http\Controllers\LotController;
use App\Http\Controllers\LotOwnershipController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\ResidenceSelectionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Sélection de la résidence active
    Route::post('residences/{residence}/select', [ResidenceSelectionController::class, 'select'])->name('residences.select');

    // Page dédiée licence suspendue
    Route::get('residences/suspended', LicenseSuspendedController::class)->name('residences.suspended');

    // Coquille et modules métier protégés par les verrous de résidence
    Route::middleware(['residence.attachment', 'residence.license'])->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        // Lots (pas de suppression)
        Route::get('lots', [LotController::class, 'index'])->name('lots.index');
        Route::post('lots', [LotController::class, 'store'])->name('lots.store');
        Route::get('lots/{lot}', [LotController::class, 'show'])->name('lots.show');
        Route::put('lots/{lot}', [LotController::class, 'update'])->name('lots.update');

        // Détentions & Mutations
        Route::post('lots/{lot}/ownerships', [LotOwnershipController::class, 'store'])->name('lots.ownerships.store');
        Route::post('lots/{lot}/mutate', [LotOwnershipController::class, 'mutate'])->name('lots.mutate');

        // Copropriétaires (pas de suppression)
        Route::get('owners', [OwnerController::class, 'index'])->name('owners.index');
        Route::post('owners', [OwnerController::class, 'store'])->name('owners.store');
        Route::get('owners/{owner}', [OwnerController::class, 'show'])->name('owners.show');
        Route::put('owners/{owner}', [OwnerController::class, 'update'])->name('owners.update');
    });
});

require __DIR__.'/settings.php';
