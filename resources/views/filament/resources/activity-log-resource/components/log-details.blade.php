<div class="space-y-4">
    <!-- Основная информация -->
    <div class="grid grid-cols-2 gap-4">
        <div>
            <h3 class="text-sm font-medium text-gray-500">Пользователь</h3>
            <p class="mt-1 text-sm text-gray-900">
                {{ $log->causer?->full_name ?? 'Система' }}
            </p>
        </div>
        
        <div>
            <h3 class="text-sm font-medium text-gray-500">Время</h3>
            <p class="mt-1 text-sm text-gray-900">
                {{ $log->created_at->format('d.m.Y H:i:s') }}
            </p>
        </div>
        
        <div>
            <h3 class="text-sm font-medium text-gray-500">Тип объекта</h3>
            <p class="mt-1 text-sm text-gray-900">
                {{ match($log->subject_type) {
                    'App\\Models\\Assignment' => 'Назначение',
                    'App\\Models\\User' => 'Пользователь',
                    default => class_basename($log->subject_type),
                } }}
            </p>
        </div>
        
        <div>
            <h3 class="text-sm font-medium text-gray-500">ID объекта</h3>
            <p class="mt-1 text-sm text-gray-900">{{ $log->subject_id }}</p>
        </div>
    </div>
    
    <!-- Измененные поля -->
    @if($log->event === 'updated' && $log->properties->has('attributes') && $log->properties->has('old'))
        <div>
            <h3 class="text-sm font-medium text-gray-500 mb-2">Измененные поля:</h3>
            <div class="space-y-2">
                @foreach($log->properties['attributes'] as $key => $newValue)
                    @if(isset($log->properties['old'][$key]) && $log->properties['old'][$key] != $newValue)
                        <div class="flex items-start">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2">
                                {{ $key }}
                            </span>
                            <div class="text-sm">
                                <span class="text-red-600 line-through mr-2">
                                    {{ is_array($log->properties['old'][$key]) ? json_encode($log->properties['old'][$key]) : $log->properties['old'][$key] }}
                                </span>
                                <span class="text-green-600">→</span>
                                <span class="text-green-600 ml-2">
                                    {{ is_array($newValue) ? json_encode($newValue) : $newValue }}
                                </span>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
    
    <!-- Полные свойства -->
    <div>
        <h3 class="text-sm font-medium text-gray-500 mb-2">Все свойства:</h3>
        <pre class="bg-gray-50 p-4 rounded text-xs overflow-auto max-h-96">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
</div>