# 📋 Руководство по настройке поведения форм в Filament

## 🎯 Цель
После создания сущности:
1. Форма автоматически закрывается
2. Происходит перенаправление на страницу списка
3. Отображается уведомление об успешном создании

## 📁 Структура файлов для WorkRequestResource

app/Filament/Resources/WorkRequestResource/
├── WorkRequestResource.php
└── Pages/
├── ListWorkRequests.php
├── CreateWorkRequest.php ← будем редактировать
└── EditWorkRequest.php ← будем редактировать


## 🔧 Шаг 1: Редактируем CreateWorkRequest.php

### **Было:**
```php
<?php

namespace App\Filament\Resources\WorkRequestResource\Pages;

use App\Filament\Resources\WorkRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkRequest extends CreateRecord
{
    protected static string $resource = WorkRequestResource::class;
}
```

### **Стало:**
```php
<?php

namespace App\Filament\Resources\WorkRequestResource\Pages;

use App\Filament\Resources\WorkRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateWorkRequest extends CreateRecord
{
    protected static string $resource = WorkRequestResource::class;

    // ✅ 1. Перенаправление на список после создания
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // ✅ 2. Кастомное сообщение об успехе
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Заявка успешно создана';
    }

    // ✅ 3. Опционально: добавить действие "Создать еще"
    protected function getCreatedNotificationActions(): array
    {
        return [
            Actions\Action::make('createAnother')
                ->label('Создать еще')
                ->url(static::getResource()::getUrl('create'))
                ->color('gray'),
        ];
    }

    // ✅ 4. Дополнительная логика после создания
    protected function afterCreate(): void
    {
        // Можно добавить кастомную логику
        // Например: логирование, отправка уведомлений и т.д.
    }
}
```

## 🔧 Шаг 2: Редактируем EditWorkRequest.php

```php
<?php

namespace App\Filament\Resources\WorkRequestResource\Pages;

use App\Filament\Resources\WorkRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditWorkRequest extends EditRecord
{
    protected static string $resource = WorkRequestResource::class;

    // ✅ Перенаправление на список после сохранения
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // ✅ Кастомное сообщение об успешном обновлении
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Заявка успешно обновлена';
    }

    // ✅ Кнопка закрытия в заголовке
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('close')
                ->label('Закрыть')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->url($this->getResource()::getUrl('index'))
                ->extraAttributes(['class' => 'ml-auto']),
        ];
    }
}
```

## 🔧 Шаг 3: Для RelationManagers (если нужно)

### В файле RelationManager добавьте в метод headerActions():

```php
public function table(Table $table): Table
{
    return $table
        ->headerActions([
            Tables\Actions\CreateAction::make()
                ->label('Добавить')
                ->modalHeading('Создание')
                ->closeModalByClickingAway(false)
                ->modalSubmitActionLabel('Создать')
                ->modalCancelActionLabel('Отмена')
                ->successNotificationTitle('Запись успешно создана')
                ->after(function () {
                    // Закрытие модального окна после создания
                    $this->dispatch('close-modal', id: 'create');
                }),
        ]);
}
```

## 🔧 Шаг 4: Для всех ресурсов (шаблон)

### **Универсальный шаблон для CreateRecord:**

```php
<?php

namespace App\Filament\Resources\YourResource\Pages;

use App\Filament\Resources\YourResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateYourResource extends CreateRecord
{
    protected static string $resource = YourResource::class;

    // 1️⃣ Вставьте этот метод для перенаправления
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // 2️⃣ Вставьте этот метод для кастомного сообщения
    protected function getCreatedNotificationTitle(): ?string
    {
        // Автоматически определяем название сущности
        $modelLabel = $this->getResource()::getModelLabel();
        
        return match($modelLabel) {
            'подрядчик' => 'Подрядчик успешно создан',
            'заявку' => 'Заявка успешно создана',
            'ставку' => 'Ставка успешно создана',
            'пользователь' => 'Пользователь успешно создан',
            'кандидат' => 'Кандидат успешно создан',
            default => ucfirst($modelLabel) . ' успешно создан(а)',
        };
    }

    // 3️⃣ Опционально: кнопка "Создать еще"
    protected function getCreatedNotificationActions(): array
    {
        return [
            Actions\Action::make('createAnother')
                ->label('Создать еще')
                ->url(static::getResource()::getUrl('create'))
                ->color('gray'),
        ];
    }
}
```

### **Универсальный шаблон для EditRecord:**

```php
<?php

namespace App\Filament\Resources\YourResource\Pages;

use App\Filament\Resources\YourResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditYourResource extends EditRecord
{
    protected static string $resource = YourResource::class;

    // 1️⃣ Перенаправление на список после сохранения
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // 2️⃣ Кастомное сообщение об обновлении
    protected function getSavedNotificationTitle(): ?string
    {
        $modelLabel = $this->getResource()::getModelLabel();
        
        return match($modelLabel) {
            'подрядчик' => 'Подрядчик успешно обновлен',
            'заявку' => 'Заявка успешно обновлена',
            'ставку' => 'Ставка успешно обновлена',
            'пользователь' => 'Пользователь успешно обновлен',
            'кандидат' => 'Кандидат успешно обновлен',
            default => ucfirst($modelLabel) . ' успешно обновлен(а)',
        };
    }

    // 3️⃣ Кнопка закрытия
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('close')
                ->label('Закрыть')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->url($this->getResource()::getUrl('index'))
                ->extraAttributes(['class' => 'ml-auto']),
        ];
    }
}
```

## **🧪 Тестирование**

1. Перейдите на страницу создания заявки: /admin/work-requests/create

2. Заполните форму и нажмите "Создать"

3. **Ожидаемый результат:**

    - Форма закрывается
    - Происходит перенаправление на /admin/work-requests
    - Появляется уведомление "Заявка успешно создана"

## **📝 Примечания**

1. Для модальных окон (RelationManagers): Используйте after() метод в CreateAction

2. Для кастомных сообщений: Измените текст в getCreatedNotificationTitle()

3. Если нужно другое поведение: Переопределите метод afterCreate()

4. Очистка кэша: После изменений выполните sail artisan optimize:clear
