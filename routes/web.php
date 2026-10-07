<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('dashboard', 'pages.dashboard.index')->name('dashboard');
});

Route::name('web.')->middleware(['auth', 'verified'])->group(function () {
    Volt::route('areas', 'pages.areas.index')->name('areas.index');
    Volt::route('catalogos', 'pages.catalogs.index')->name('catalogs.index');
    Volt::route('dispositivos', 'pages.devices.index')->name('devices.index');
    Volt::route('horarios', 'pages.schedules.index')->name('schedules.index');
    Volt::route('anomalias', 'pages.anomalies.index')->name('anomalies.index');
    Volt::route('estadisticas', 'pages.statistics.index')->name('statistics.index');
    Volt::route('usuarios', 'pages.users.index')->name('users.index');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
