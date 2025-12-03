<?php

return [
    // Основные настройки
    'delete_records_older_than_days' => 365, // Храним 1 год
    
    // Очереди для производительности
    'queue_logs' => true,
    'queue_connection' => env('ACTIVITY_LOG_QUEUE_CONNECTION', 'redis'),
    'queue_name' => env('ACTIVITY_LOG_QUEUE_NAME', 'default'),
    'queue_timeout' => 10,
    
    // Ограничения для защиты
    'max_logs_per_minute' => 5000,
    
    // Что логировать
    'log_events' => [
        'created',
        'updated',
        'deleted',
        'restored',
    ],
    
    // Не логировать автоматически все модели
    'log_all_events' => false,
    
    // Дополнительная информация
    'log_ip_address' => true,
    'log_user_agent' => false, // Экономим место
    'log_url' => true,
];
