<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('tasks', 'task-list')->name('tasks');
    Route::livewire('settings/profile-form', 'settings.profile-form')->name('settings.profile-form');
    Route::livewire('admin/users', 'admin.user-table')->name('admin.users');
    Route::livewire('admin/orders', 'admin.order-management')->name('admin.orders');
    Route::livewire('admin/activity', 'admin.activity-log')->name('admin.activity');
});

require __DIR__.'/settings.php';
