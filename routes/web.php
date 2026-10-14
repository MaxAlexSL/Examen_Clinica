<?php

use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProductController;
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

    // Pacientes (sin eliminación: se desactivan)
    Route::get('/pacientes', [PatientController::class, 'index'])
        ->middleware('permission:pacientes.ver')->name('patients.index');
    Route::get('/pacientes/crear', [PatientController::class, 'create'])
        ->middleware('permission:pacientes.crear')->name('patients.create');
    Route::post('/pacientes', [PatientController::class, 'store'])
        ->middleware('permission:pacientes.crear')->name('patients.store');
    Route::get('/pacientes/{patient}', [PatientController::class, 'show'])
        ->middleware('permission:pacientes.ver')->name('patients.show');
    Route::get('/pacientes/{patient}/editar', [PatientController::class, 'edit'])
        ->middleware('permission:pacientes.editar')->name('patients.edit');
    Route::put('/pacientes/{patient}', [PatientController::class, 'update'])
        ->middleware('permission:pacientes.editar')->name('patients.update');
    Route::patch('/pacientes/{patient}/desactivar', [PatientController::class, 'deactivate'])
        ->middleware('permission:pacientes.desactivar')->name('patients.deactivate');
    Route::patch('/pacientes/{patient}/activar', [PatientController::class, 'activate'])
        ->middleware('permission:pacientes.desactivar')->name('patients.activate');

    // Productos (sin ruta destroy: se desactivan)
    Route::get('/productos', [ProductController::class, 'index'])
        ->middleware('permission:productos.ver')->name('products.index');
    Route::get('/productos/crear', [ProductController::class, 'create'])
        ->middleware('permission:productos.crear')->name('products.create');
    Route::post('/productos', [ProductController::class, 'store'])
        ->middleware('permission:productos.crear')->name('products.store');
    Route::get('/productos/{product}', [ProductController::class, 'show'])
        ->middleware('permission:productos.ver')->name('products.show');
    Route::get('/productos/{product}/editar', [ProductController::class, 'edit'])
        ->middleware('permission:productos.editar')->name('products.edit');
    Route::put('/productos/{product}', [ProductController::class, 'update'])
        ->middleware('permission:productos.editar')->name('products.update');
    Route::patch('/productos/{product}/desactivar', [ProductController::class, 'deactivate'])
        ->middleware('permission:productos.desactivar')->name('products.deactivate');
    Route::patch('/productos/{product}/activar', [ProductController::class, 'activate'])
        ->middleware('permission:productos.desactivar')->name('products.activate');
});

require __DIR__.'/auth.php';
