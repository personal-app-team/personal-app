{{-- resources/views/filament/resources/activity-log-resource/components/log-details.blade.php --}}
<div class="space-y-4 p-4">
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
                    'App\\Models\\Assignment' => '📋 Назначение',
                    'App\\Models\\User' => '👤 Пользователь',
                    'App\\Models\\Shift' => '💰 Смена',
                    'App\\Models\\WorkRequest' => '📄 Заявка',
                    default => class_basename($log->subject_type),
                } }}
            </p>
        </div>
        
        <div>
            <h3 class="text-sm font-medium text-gray-500">ID объекта</h3>
            <p class="mt-1 text-sm text-gray-900">{{ $log->subject_id }}</p>
        </div>
    </div>
    
    <!-- Дополнительная информация для финансовых изменений -->
    @if($log->subject_type === 'App\\Models\\Shift')
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <span class="font-medium">Финансовая операция:</span> 
                        @if(isset($log->properties['attributes']['base_rate']))
                            Ставка: {{ number_format($log->properties['attributes']['base_rate'], 2) }} ₽
                        @endif
                        @if(isset($log->properties['attributes']['compensation_amount']))
                            | Компенсация: {{ number_format($log->properties['attributes']['compensation_amount'], 2) }} ₽
                        @endif
                        @if(isset($log->properties['attributes']['tax_amount']))
                            | Налог: {{ number_format($log->properties['attributes']['tax_amount'], 2) }} ₽
                        @endif
                        @if(isset($log->properties['attributes']['payout_amount']))
                            | К выплате: {{ number_format($log->properties['attributes']['payout_amount'], 2) }} ₽
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endif
    
    <!-- Измененные поля -->
    @if($log->event === 'updated' && $log->properties->has('attributes') && $log->properties->has('old'))
        <div>
            <h3 class="text-sm font-medium text-gray-500 mb-2">Измененные поля:</h3>
            <div class="space-y-2">
                @foreach($log->properties['attributes'] as $key => $newValue)
                    @php
                        $oldValue = $log->properties['old'][$key] ?? null;
                        $isFinancial = in_array($key, ['base_rate', 'compensation_amount', 'expenses_total', 'tax_amount', 'payout_amount', 'hand_amount']);
                        $isStatus = in_array($key, ['status', 'is_paid']);
                    @endphp
                    
                    @if($oldValue != $newValue)
                        <div class="flex items-start p-2 rounded {{ $isFinancial ? 'bg-yellow-50' : ($isStatus ? 'bg-blue-50' : 'bg-gray-50') }}">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                {{ $isFinancial ? 'bg-yellow-100 text-yellow-800' : ($isStatus ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }} mr-2">
                                {{ $key }}
                            </span>
                            <div class="text-sm">
                                @if($isFinancial)
                                    <span class="text-red-600 line-through mr-2">
                                        {{ is_numeric($oldValue) ? number_format($oldValue, 2) . ' ₽' : (is_array($oldValue) ? json_encode($oldValue) : $oldValue) }}
                                    </span>
                                    <span class="text-green-600">→</span>
                                    <span class="text-green-600 ml-2 font-medium">
                                        {{ is_numeric($newValue) ? number_format($newValue, 2) . ' ₽' : (is_array($newValue) ? json_encode($newValue) : $newValue) }}
                                    </span>
                                @else
                                    <span class="text-red-600 line-through mr-2">
                                        {{ is_array($oldValue) ? json_encode($oldValue) : $oldValue }}
                                    </span>
                                    <span class="text-green-600">→</span>
                                    <span class="text-green-600 ml-2">
                                        {{ is_array($newValue) ? json_encode($newValue) : $newValue }}
                                    </span>
                                @endif
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