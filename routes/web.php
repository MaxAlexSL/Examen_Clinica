<?php

use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

// Cada ruta exige su permiso (nunca un nombre de rol). Sin permiso → 403.
Route::middleware('auth')->group(function () {
    // Usuarios (sin eliminación: se desactivan)
    Route::get('/usuarios', [UserController::class, 'index'])
        ->middleware('permission:usuarios.ver')->name('users.index');
    Route::get('/usuarios/crear', [UserController::class, 'create'])
        ->middleware('permission:usuarios.crear')->name('users.create');
    Route::post('/usuarios', [UserController::class, 'store'])
        ->middleware('permission:usuarios.crear')->name('users.store');
    Route::get('/usuarios/{user}/editar', [UserController::class, 'edit'])
        ->middleware('permission:usuarios.editar')->name('users.edit');
    Route::put('/usuarios/{user}', [UserController::class, 'update'])
        ->middleware('permission:usuarios.editar')->name('users.update');

    // Roles
    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('permission:roles.ver')->name('roles.index');
    Route::get('/roles/crear', [RoleController::class, 'create'])
        ->middleware('permission:roles.crear')->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])
        ->middleware('permission:roles.crear')->name('roles.store');
    Route::get('/roles/{role}/editar', [RoleController::class, 'edit'])
        ->middleware('permission:roles.editar')->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])
        ->middleware('permission:roles.editar')->name('roles.update');
});

require __DIR__.'/auth.php';
