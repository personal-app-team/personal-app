<?php

use Illuminate\Support\Facades\Route;
use Spatie\Activitylog\Models\Activity;

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

Route::get('/debug-activity-logs', function () {
    $logs = Activity::orderBy('created_at', 'desc')->limit(10)->get();
    
    echo "<h1>Отладка Activity Logs</h1>";
    echo "<p>Всего записей: " . Activity::count() . "</p>";
    
    foreach ($logs as $log) {
        echo "<hr>";
        echo "<p><strong>ID:</strong> {$log->id}</p>";
        echo "<p><strong>Действие:</strong> {$log->description}</p>";
        echo "<p><strong>Тип объекта:</strong> {$log->subject_type}</p>";
        echo "<p><strong>Дата:</strong> {$log->created_at->format('d.m.Y H:i:s')}</p>";
    }
    
    // Проверим Filament Resource
    echo "<hr><h2>Проверка Filament Resource</h2>";
    
    try {
        $resource = new \App\Filament\Resources\ActivityLogResource();
        echo "<p>Ресурс загружен успешно</p>";
    } catch (\Exception $e) {
        echo "<p style='color: red;'>Ошибка: " . $e->getMessage() . "</p>";
    }
});

Route::get('/test-activity-filament', function () {
    try {
        // Тест 1: Проверка модели
        $model = new \Spatie\Activitylog\Models\Activity();
        echo "<h2>Тест модели:</h2>";
        echo "<p>Модель: " . get_class($model) . "</p>";
        echo "<p>Всего записей: " . $model->count() . "</p>";
        
        // Тест 2: Проверка запроса из ресурса
        echo "<h2>Тест запроса из ресурса:</h2>";
        $query = \App\Filament\Resources\ActivityLogResource::getEloquentQuery();
        echo "<p>Запрос SQL: " . $query->toSql() . "</p>";
        echo "<p>Количество записей: " . $query->count() . "</p>";
        
        // Тест 3: Пробуем получить данные
        echo "<h2>Первые 5 записей:</h2>";
        $logs = $query->limit(5)->get();
        foreach ($logs as $log) {
            echo "<p>ID: {$log->id}, Действие: {$log->description}, Дата: {$log->created_at->format('d.m.Y H:i:s')}</p>";
        }
        
        // Тест 4: Проверка прав
        echo "<h2>Проверка прав доступа:</h2>";
        $user = \App\Models\User::find(1);
        echo "<p>Пользователь: " . $user->email . "</p>";
        echo "<p>Может view_activity_logs: " . ($user->can('view_activity_logs') ? 'ДА' : 'НЕТ') . "</p>";
        
    } catch (\Exception $e) {
        echo "<h2 style='color: red;'>ОШИБКА:</h2>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
});

// Route::get('/test-users-for-filter', function () {
//     try {
//         $users = User::whereNotNull('name')
//             ->select('id', 'name', 'surname', 'patronymic', 'email')
//             ->get();
            
//         echo "<h1>Пользователи для фильтра</h1>";
//         echo "<p>Всего пользователей: " . $users->count() . "</p>";
        
//         foreach ($users as $user) {
//             echo "<hr>";
//             echo "<p><strong>ID:</strong> {$user->id}</p>";
//             echo "<p><strong>Имя:</strong> " . ($user->name ?? 'NULL') . "</p>";
//             echo "<p><strong>Фамилия:</strong> " . ($user->surname ?? 'NULL') . "</p>";
//             echo "<p><strong>Отчество:</strong> " . ($user->patronymic ?? 'NULL') . "</p>";
//             echo "<p><strong>Email:</strong> " . ($user->email ?? 'NULL') . "</p>";
//             echo "<p><strong>Full Name (calculated):</strong> " . $user->full_name . "</p>";
//         }
//     } catch (\Exception $e) {
//         echo "<h2 style='color: red;'>ОШИБКА:</h2>";
//         echo "<p>" . $e->getMessage() . "</p>";
//         echo "<pre>" . $e->getTraceAsString() . "</pre>";
//     }
// });
