<div class="p-4 bg-white rounded-lg shadow">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-2">
            <x-filament::icon
                :icon="$this->unreadCount > 0 ? 'heroicon-o-bell-alert' : 'heroicon-o-bell'"
                :class="[
                    'h-5 w-5',
                    'text-gray-400' => $this->unreadCount == 0,
                    'text-danger-500 animate-pulse' => $this->unreadCount > 0,
                ]"
            />
            <h3 class="text-lg font-semibold text-gray-900">
                Уведомления
                @if($this->unreadCount > 0)
                    <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-danger-100 text-danger-800">
                        {{ $this->unreadCount }} новых
                    </span>
                @endif
            </h3>
        </div>
        
        @if($this->unreadCount > 0)
            <button 
                wire:click="markAllAsRead"
                class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-primary-600 hover:text-primary-800"
            >
                <x-filament::icon icon="heroicon-o-check-circle" class="w-4 h-4 mr-1" />
                Прочитать все
            </button>
        @endif
    </div>
    
    @if(count($this->recentNotifications) > 0)
        <div class="space-y-3">
            @foreach($this->recentNotifications as $notification)
                <div class="border rounded-lg p-3 {{ $notification['is_unread'] ? 'border-primary-200 bg-primary-50' : 'border-gray-200 bg-gray-50' }}">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-start">
                                @php
                                    $icon = $notification['data']['icon'] ?? 'heroicon-o-information-circle';
                                    $color = $notification['data']['color'] ?? 'primary';
                                @endphp
                                <x-filament::icon
                                    :icon="$icon"
                                    :class="[
                                        'h-5 w-5 mr-2 mt-0.5',
                                        'text-primary-500' => $color == 'primary',
                                        'text-success-500' => $color == 'success',
                                        'text-warning-500' => $color == 'warning',
                                        'text-danger-500' => $color == 'danger',
                                    ]"
                                />
                                <div>
                                    <h4 class="font-medium text-gray-900">
                                        {{ $notification['data']['title'] ?? 'Уведомление' }}
                                    </h4>
                                    <p class="text-sm text-gray-600 mt-1">
                                        {{ $notification['data']['message'] ?? 'Новое уведомление' }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-2">
                                        {{ $notification['created_at'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        @if($notification['is_unread'])
                            <button 
                                wire:click="markAsRead('{{ $notification['id'] }}')"
                                class="ml-2 inline-flex items-center px-2 py-1 text-xs font-medium text-primary-600 hover:text-primary-800"
                                title="Отметить как прочитанное"
                            >
                                <x-filament::icon icon="heroicon-o-check" class="w-4 h-4" />
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="mt-4 text-center">
            <a 
                href="{{ route('filament.admin.resources.notifications.index') }}"
                class="inline-flex items-center text-sm font-medium text-primary-600 hover:text-primary-800"
            >
                Все уведомления
                <x-filament::icon icon="heroicon-o-arrow-right" class="w-4 h-4 ml-1" />
            </a>
        </div>
    @else
        <div class="text-center py-8">
            <x-filament::icon
                icon="heroicon-o-bell"
                class="mx-auto h-12 w-12 text-gray-400"
            />
            <h3 class="mt-2 text-sm font-semibold text-gray-900">
                Нет уведомлений
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Здесь появятся ваши уведомления
            </p>
        </div>
    @endif
</div>
