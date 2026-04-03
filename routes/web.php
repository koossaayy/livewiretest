<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('tasks', 'task-list')->name('tasks');
    Route::livewire('settings/profile-form', 'settings.profile-form')->name('settings.profile-form');
    Route::livewire('admin/users', 'admin.user-table')->name('admin.users');
});

require __DIR__.'/settings.php';
