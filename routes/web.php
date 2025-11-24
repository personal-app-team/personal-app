<?php

use Illuminate\Support\Facades\Route;

// Главная страница - перенаправляем в Filament админку
Route::redirect('/', '/admin');

// Filament Admin Panel
Route::get('/admin/{any?}', function () {
    return view('welcome');
})->where('any', '.*');

// Выход (оставляем для совместимости)
Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/admin');
})->name('logout');

// Тестовый маршрут (можно удалить, если не нужен)
Route::get('/test-storage', function() {
    return [
        'disk' => config('filesystems.default'),
        'public_url' => Storage::disk('public')->url('test.jpg'),
        'public_path' => Storage::disk('public')->path('test.jpg'),
    ];
});
