## 🔧 ИСПРАВЛЕНИЕ СИСТЕМЫ РАЗРЕШЕНИЙ ДЛЯ ASSIGNMENT - 14.01.2026

**Проблема:** Кнопки "Подтвердить" и "Отклонить" не отображаются у исполнителя, несмотря на наличие разрешений.

**Причина:** 
1. **Смешанная архитектура** - одновременно используются Policies и Gates
2. **Conflict между AuthServiceProvider и Policies** - Gates переопределяют логику
3. **getEloquentQuery() фильтрует записи до проверки действий**

**Решение:**
1. ✅ **Удалить конфликтующие Gates** из `AuthServiceProvider`:
   - confirm_assignment
   - reject_assignment
   - create_shift
   - и другие дублирующие политики

2. ✅ **Упростить логику в AssignmentPolicy**:
   - Проверять только роли и состояние записи
   - Не проверять разрешения внутри политики (это делает Filament Shield)

3. ✅ **Проверить getEloquentQuery()**:
   - Убедиться, что запись доступна пользователю через этот метод

**Проверка:**
```bash
# 1. Очистить кэш
sail artisan permission:cache-reset
sail artisan cache:clear

# 2. Проверить разрешения
sail artisan tinker
>>> $executor = \App\Models\User::where('email', 'executor1@example.com')->first()
>>> $assignment = \App\Models\Assignment::where('user_id', $executor->id)->where('status', 'pending')->first()
>>> $executor->can('confirm', $assignment)  # должно вернуть true

# 3. Проверить getEloquentQuery
>>> $query = \App\Filament\Resources\AssignmentResource::getEloquentQuery()
>>> $query->where('id', $assignment->id)->exists()  # должно вернуть true
```
## 🔧 КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: AuthServiceProvider - 14.01.2026

**Проблема:** Кнопки "Подтвердить/Отклонить" не отображаются, политики не работают
**Причина:** `Gate::guessPolicyNamesUsing(fn () => null);` отключает автоматическое определение политик
**Файл:** `app/Providers/AuthServiceProvider.php`

### Исправления:

1. **✅ УДАЛЕНО:** `Gate::guessPolicyNamesUsing(fn () => null);`
2. **✅ УДАЛЕНЫ:** Все Gates, которые дублируют логику политик
3. **✅ ОСТАВЛЕНЫ:** Только Gates для проверок без моделей (доступ к панелям)

### Новый AuthServiceProvider:

```php
protected $policies = [
    // Только для встроенных моделей Laravel
    \Illuminate\Notifications\DatabaseNotification::class => \App\Policies\DatabaseNotificationPolicy::class,
];

public function boot(): void
{
    $this->registerPolicies();
    
    // Администратор может всё
    Gate::before(fn ($user) => $user->hasRole('admin'));
    
    // Gates ТОЛЬКО для проверок без моделей
    Gate::define('access_admin_panel', fn ($user) => 
        $user->hasAnyRole(['admin', 'dispatcher', 'hr', 'manager'])
    );
}
```