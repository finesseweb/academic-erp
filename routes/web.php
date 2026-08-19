<?php

use App\Http\Controllers\AuthorizedSignatoryController;
use App\Http\Controllers\CollegeController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\UniversityController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserRoleController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'active', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::get('admin/university', [UniversityController::class, 'show'])->name('university.show');
    Route::patch('admin/university/{university}', [UniversityController::class, 'update'])->name('university.update');
    Route::redirect('super-admin/university', '/admin/university');
    Route::get('admin/colleges', [CollegeController::class, 'index'])->name('colleges.index');
    Route::get('admin/colleges/create', [CollegeController::class, 'create'])->name('colleges.create');
    Route::post('admin/colleges', [CollegeController::class, 'store'])->name('colleges.store');
    Route::get('admin/colleges/{college}/edit', [CollegeController::class, 'edit'])->name('colleges.edit');
    Route::patch('admin/colleges/{college}', [CollegeController::class, 'update'])->name('colleges.update');
    Route::patch('admin/colleges/{college}/status', [CollegeController::class, 'status'])->name('colleges.status');
    Route::redirect('super-admin/colleges', '/admin/colleges');
    Route::get('admin/university/signatories', [AuthorizedSignatoryController::class, 'index'])->name('signatories.index');
    Route::get('admin/university/signatories/create', [AuthorizedSignatoryController::class, 'create'])->name('signatories.create');
    Route::post('admin/university/signatories', [AuthorizedSignatoryController::class, 'store'])->name('signatories.store');
    Route::get('admin/university/signatories/{signatory}/edit', [AuthorizedSignatoryController::class, 'edit'])->name('signatories.edit');
    Route::patch('admin/university/signatories/{signatory}', [AuthorizedSignatoryController::class, 'update'])->name('signatories.update');
    Route::patch('admin/university/signatories/{signatory}/status', [AuthorizedSignatoryController::class, 'status'])->name('signatories.status');
    Route::get('admin/users', [UserController::class, 'index'])->name('users.index');
    Route::get('admin/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('admin/users', [UserController::class, 'store'])->name('users.store');
    Route::get('admin/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::patch('admin/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('admin/users/{user}/status', [UserController::class, 'status'])->name('users.status');
    Route::post('admin/users/{user}/password-reset', [UserController::class, 'resetPassword'])->name('users.password-reset');
    Route::redirect('super-admin/users', '/admin/users');
    Route::get('admin/users/{user}/roles', [UserRoleController::class, 'edit'])->name('users.roles.edit');
    Route::put('admin/users/{user}/roles', [UserRoleController::class, 'store'])->name('users.roles.store');
    Route::patch('admin/users/{user}/roles/{assignment}/scope', [UserRoleController::class, 'updateScope'])->name('users.roles.scope.update');
    Route::delete('admin/users/{user}/roles/{assignment}', [UserRoleController::class, 'destroy'])->name('users.roles.destroy');
    Route::get('admin/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('admin/roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::post('admin/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('admin/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::patch('admin/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::patch('admin/roles/{role}/status', [RoleController::class, 'status'])->name('roles.status');
    Route::redirect('super-admin/roles', '/admin/roles');
    Route::get('admin/roles/{role}/permissions', [RolePermissionController::class, 'edit'])->name('roles.permissions.edit');
    Route::put('admin/roles/{role}/permissions', [RolePermissionController::class, 'update'])->name('roles.permissions.update');
    Route::get('admin/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::redirect('super-admin/permissions', '/admin/permissions');
});

require __DIR__.'/settings.php';
