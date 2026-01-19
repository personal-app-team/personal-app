# 🚀 Personal Management System - Актуальная документация

**📅 Дата актуализации: 15.01.2026**

**🏗️ АРХИТЕКТУРА СИСТЕМЫ (ОБНОВЛЕНО 15.01.2026)**

## 📊 **СТАТУС СИСТЕМЫ**

| Компонент | Статус | Количество | Изменения |
|-----------|--------|------------|-----------|
| Модели | ✅ Полный набор | 41 | Без изменений |
| Filament Resources | ✅ 100% покрытие | 41 из 41 | +NotificationResource |
| Миграции | ✅ Выполнены | 96 | Без изменений |
| Система разрешений | ✅ Оптимизирована | 501 разрешение, 42 политики, 10 ролей | Исправлены конфликты Gates |
| Система уведомлений | ✅ Настроена | Полный стек: Model → Resource → Widget → Observer → Notification |
| RelationManagers | ✅ Работают | 15+ | Протестированы |

## 🔧 **ИСПРАВЛЕННЫЕ ПРОБЛЕМЫ (12-15.01.2026)**

### **1. ✅ СИСТЕМА УВЕДОМЛЕНИЙ:**
- **Было**: Ошибка "Class ListNotifications not found"
- **Стало**: Полный набор страниц NotificationResource
- **Решение**: Созданы недостающие страницы, настроен DatabaseNotification

### **2. ✅ FILAMENT v3.2 КОМПАТИБИЛЬНОСТЬ:**
- **Проблема 1**: `badgeColor()` метод не существует в NavigationItem
- **Решение**: Замена на передачу массива в `badge()`
- **Проблема 2**: `MenuItem` не поддерживает методы `badge()` и `badgeColor()`
- **Решение**: Удаление бейджей из userMenuItems

### **3. ✅ СИСТЕМА РАЗРЕШЕНИЙ:**
- **Конфликт**: Одновременное использование Policies и Gates в AuthServiceProvider
- **Решение**: Удаление всех дублирующих Gates, оставлены только для проверок без моделей
- **Ключевое исправление**: Удалена строка `Gate::guessPolicyNamesUsing(fn () => null);`

### **4. ✅ ОЧЕРЕДЬ УВЕДОМЛЕНИЙ:**
- **Проблема**: Уведомления с `ShouldQueue` накапливались в таблице `jobs`
- **Решение**: Запуск воркера очереди, исправление дублирования в Observer

## 🏛️ **ОСНОВНЫЕ МОДУЛИ С FILAMENT RESOURCES**

### **ОПЕРАТИВНАЯ ДЕЯТЕЛЬНОСТЬ (100% готово):**
1. **✅ WorkRequest** - Заявки на работы с проектной структурой
2. **✅ User** - Пользователи с полной ролевой моделью
3. **✅ Shift** - Смены с системой расчетов и налогообложения
4. **✅ Assignment** - Единая система назначений (исправлены разрешения)
5. **✅ Expense** - Операционные расходы
6. **✅ Compensation** - Компенсации к сменам
7. **✅ MassPersonnelReport** - Отчеты по массовому персоналу
8. **✅ Contractor** - Подрядчики с персонализированными ставками

### **СИСТЕМА ПОДБОРА ПЕРСОНАЛА (100% готово):**
9. **✅ Vacancy** - Вакансии компании
10. **✅ RecruitmentRequest** - Заявки на подбор
11. **✅ Candidate** - Кандидаты на позиции (с RelationManagers)
12. **✅ Interview** - Собеседования
13. **✅ HiringDecision** - Решения о приеме
14. **✅ PositionChangeRequest** - Запросы на изменение должностей/оплат
15. **✅ TraineeRequest** - Система управления стажерами

### **ОРГАНИЗАЦИОННАЯ СТРУКТУРА (100% готово):**
16. **✅ Department** - Организационная структура
17. **✅ EmploymentHistory** - История трудоустройства
18. **✅ Category/Specialty** - Иерархия категорий и специальностей
19. **✅ WorkType** - Справочник видов работ

### **ФИНАНСОВЫЕ И СПРАВОЧНЫЕ МОДУЛИ (100% готово):**
20. **✅ ContractType** - Типы договоров
21. **✅ TaxStatus** - Налоговые статусы
22. **✅ ContractorRate** - Ставки подрядчиков

### **ПРОЕКТНАЯ СТРУКТУРА (100% готово):**
23. **✅ Project** - Проекты
24. **✅ Purpose** - Цели/задачи
25. **✅ PurposeTemplate** - Шаблоны целей
26. **✅ Address/AddressTemplate** - Адреса и шаблоны адресов
27. **✅ PurposeAddressRule, PurposePayerCompany** - Дополнительные справочники

### **ВСПОМОГАТЕЛЬНЫЕ МОДУЛИ (100% готово):**
28. **✅ ActivityLog** - Система логирования изменений (Spatie Laravel Activitylog)
29. **✅ VisitedLocation** - Посещенные локации
30. **✅ Photo** - Фотографии (унифицированная система)
31. **✅ WorkRequestStatus** - Статусы заявок
32. **✅ InitiatorGrant** - Гранты инициаторов
33. **✅ ContractorWorker** - Работники подрядчиков

### **НОВЫЕ МОДУЛИ (добавлено в январе 2026):**
34. **✅ Notification** - Полноценная система уведомлений с очередями
35. **✅ NotificationResource** - Управление уведомлениями в админке
36. **✅ NotificationsWidget** - Виджет уведомлений на дашборде

### **СИСТЕМА РАЗРЕШЕНИЙ:**
37. **✅ PermissionResource** - Управление разрешениями (Filament Shield)
38. **✅ RoleResource** - Управление ролями (Filament Shield)

## 🛡️ **СИСТЕМА РАЗРЕШЕНИЙ (ОПТИМИЗИРОВАНА 15.01.2026)**

### **✅ ОПТИМИЗИРОВАННАЯ КОНФИГУРАЦИЯ:**
- **Удалены конфликтующие Gates** из AuthServiceProvider
- **Упрощены политики** - проверяют только роль admin
- **Filament Shield** автоматически управляет разрешениями
- **Автоматическое определение политик** восстановлено (удален guessPolicyNamesUsing)

### **👥 РОЛЕВАЯ МОДЕЛЬ:**

**Основные роли системы (Spatie Permissions + Filament Shield):**

**Административные роли:**
- **admin** - Полный доступ ко всем ресурсам (501 разрешение)
- **initiator** - Создание WorkRequest, TraineeRequest, RecruitmentRequest
- **dispatcher** - Управление Assignment, Shift, комплектование заявок
- **executor** - Личный кабинет: смены, расходы, локации

**Роли подрядчиков (отдельная иерархия):**
- **contractor_admin** - Полный доступ к данным своего подрядчика
- **contractor_dispatcher** - Управление назначениями подрядчика
- **contractor_executor** - Исполнитель в рамках подрядчика

**Роли подбора персонала:**
- **hr** - Доступ к модулям подбора персонала (Vacancy, Candidate, Interview)
- **manager** - Утверждение HiringDecision, PositionChangeRequest, TraineeRequest
- **trainee** - Ограниченный доступ к системе (стажер)

## 🔔 **СИСТЕМА УВЕДОМЛЕНИЙ (ПОЛНОСТЬЮ НАСТРОЕНА)**

### **✅ КОМПОНЕНТЫ:**
1. **Модель**: `Illuminate\Notifications\DatabaseNotification` (встроенная Laravel)
2. **Ресурс**: `NotificationResource` (просмотр уведомлений)
3. **Виджет**: `NotificationsWidget` (показывает 5 последних на дашборде)
4. **Observer**: `AssignmentObserver` (автоматически создает уведомления)
5. **Notification**: `NewAssignmentNotification` (класс уведомления с очередями)

### **✅ ОСОБЕННОСТИ:**
- **Очередь заданий**: Уведомления используют `ShouldQueue` для асинхронной обработки
- **Автоматическое создание**: При создании назначения → уведомление исполнителю
- **Фильтрация по пользователю**: Каждый видит только свои уведомления (кроме админа)
- **Виджет на дашборде**: Показывает количество непрочитанных уведомлений

### **✅ КОМАНДЫ ДЛЯ УПРАВЛЕНИЯ:**
```bash
# Обработка очереди уведомлений
sail artisan queue:work --stop-when-empty

# Проверка состояния очереди
sail artisan queue:failed

# Перезапуск воркера
sail artisan queue:restart
```

## 🔄 **РАБОЧИЕ ПРОЦЕССЫ (ПРОТЕСТИРОВАНЫ)**

### ✅ **ПРОЦЕСС НАЗНАЧЕНИЙ И СМЕН:**

1. Создание назначения → AssignmentResource (dispatcher)

2. Авто-уведомление → AssignmentObserver отправляет NewAssignmentNotification

3. Подтверждение исполнителем → Кнопки "Подтвердить/Отклонить" в AssignmentResource

4. Создание смены → Автоматически при подтверждении назначения

5. Выполнение работ → ShiftResource (старт/завершение смены)

6. Расчеты → Автоматический расчет по формуле в ShiftResource


### ✅ **ПРОЦЕСС ПОДБОРА ПЕРСОНАЛА:**

1. Создание вакансии → VacancyResource (HR)

2. Заявка на подбор → RecruitmentRequestResource (Заявитель)

3. Поиск кандидатов → CandidateResource через RelationManagers

4. Решение заявителя → CandidateDecision через RelationManager

5. Собеседование → InterviewResource

6. Решение о найме → HiringDecisionResource

7. Стажировка → TraineeRequestResource (автоматически при решении "trainee")

## 📈 **СТАТИСТИКА СИСТЕМЫ**


### ОБЩАЯ СТАТИСТИКА:

* Модели: 41

* Filament Resources: 41 (100% покрытие)

* Миграции: 96 выполненных

* RelationManagers: 15+

* API endpoints: 50+ (для мобильного приложения)


### СИСТЕМА РАЗРЕШЕНИЙ:

* Разрешения: 501

* Политики: 42

* Роли: 10

* Разрешений у admin: 501 (100%)


### СИСТЕМА УВЕДОМЛЕНИЙ:

* Компоненты: 5 (Model, Resource, Widget, Observer, Notification)

* Очередь заданий: Database driver

* Автоматические триггеры: 1 (создание назначения)


## 🎯 ТЕКУЩИЕ ПРИОРИТЕТЫ РАЗРАБОТКИ

**ВЫСОКИЙ ПРИОРИТЕТ:**

1. 🔍 Тестирование полных workflow:

    * Тест: Вакансия → Заявка → Кандидат → Собеседование → Найм → Назначение → Смена → Оплата

    * Тест: Запрос на стажировку → Утверждение → Стажировка → Найм

    * Тест: Изменение должности/зарплаты через PositionChangeRequest

2. 📊 Создание дашбордов и виджетов:

    * Дашборд рекрутинга (статистика по вакансиям, кандидатам)

    * Дашборд оперативной деятельности (Shifts, Assignments, Expenses)

    * Финансовый дашборд (расчеты, выплаты, налоги)

    * Расширение существующего ActivityStatsWidget


**СРЕДНИЙ ПРИОРИТЕТ:**

3. 📱 PWA и мобильная адаптация:

    * Оптимизация Filament под мобильные устройства

    * Настройка Progressive Web App

    * Оффлайн-возможности для исполнителей

4. 🔗 Улучшение API для мобильного приложения:

 * Документация API endpoints

 * Тестирование мобильных сценариев

 * Оптимизация производительности

**НИЗКИЙ ПРИОРИТЕТ:**

5. ⚙️ Дополнительные функции:

    * Экспорт отчетов в Excel/PDF

    * Интеграция с календарями (Google Calendar, Outlook)

    * Система чатов между участниками workflow

## ✅ ЗАВЕРШЕННЫЕ ЗАДАЧИ (ЯНВАРЬ 2026)

### ИСПРАВЛЕНИЯ ФИЛАТМЕНТ:

* ✅ Исправлена ошибка badgeColor() для Filament v3.2

* ✅ Исправлен конфликт MenuItem vs NavigationItem

* ✅ Настроена совместимость с Filament v3.2.51

### СИСТЕМА РАЗРЕШЕНИЙ:

* ✅ Удалены конфликтующие Gates из AuthServiceProvider

* ✅ Восстановлено автоматическое определение политик

* ✅ Упрощена логика политик (проверка только на роль admin)

### СИСТЕМА УВЕДОМЛЕНИЙ:

* ✅ Создан полный стек уведомлений (Resource, Widget, Observer, Notification)

* ✅ Настроена очередь заданий для асинхронной отправки

* ✅ Исправлено дублирование уведомлений в Observer

### ДОКУМЕНТАЦИЯ:

* ✅ Обновлена документация разрешений (TODO_FIXES_29.12.md)

* ✅ Добавлены отчеты по исправлениям (TODO_FIXES_12-15.01.md)

* ✅ Актуализирован общий обзор системы

## 🔗 ИНТЕГРАЦИОННЫЕ ТОЧКИ

**КЛЮЧЕВЫЕ RELATIONMANAGERS:**

1. UserResource:

    * AssignmentsRelationManager - назначения пользователя

    * EmploymentHistoryRelationManager - история трудоустройства

    * ShiftsRelationManager - смены пользователя

    * SpecialtiesRelationManager - специальности пользователя

2. CandidateResource:

    * CandidateDecisionsRelationManager - решения по кандидату

    * CandidateStatusHistoryRelationManager - история статусов

    * InterviewsRelationManager - собеседования кандидата

3. WorkRequestResource:

    * AssignmentsRelationManager - назначения на заявку

    * ShiftsRelationManager - смены по заявку

    * ExpensesRelationManager - расходы по заявку

## АВТОМАТИЧЕСКИЕ ДЕЙСТВИЯ:

* ✅ Создание смены при подтверждении Assignment

* ✅ Создание TraineeRequest при решении Interview.result = 'trainee'

* ✅ Создание пользователя при утверждении HiringDecision

* ✅ Автоматический расчет сумм в ShiftResource и MassPersonnelReportResource

* ✅ Автоматическое уведомление при создании назначения

## 🚀 СЛЕДУЮЩИЕ ШАГИ РАЗРАБОТКИ

1. **ТЕСТИРОВАНИЕ ПОЛНЫХ WORKFLOW:**
```bash
# Проверить доступ для разных ролей
sail artisan tinker
>>> $user = User::role('executor')->first();
>>> $assignment = Assignment::where('user_id', $user->id)->first();
>>> $user->can('confirm', $assignment);  # Должно вернуть true
```
2. **СОЗДАНИЕ ДАШБОРДОВ:**
```bash
# Создать виджеты для дашбордов
sail artisan make:filament-widget RecruitmentStatsWidget
sail artisan make:filament-widget OperationalDashboardWidget
sail artisan make:filament-widget FinancialOverviewWidget
```
3. **ОПТИМИЗАЦИЯ ПРОИЗВОДИТЕЛЬНОСТИ:**
```bash
# Проверить индексы базы данных
sail artisan db:show --type=indexes

# Проверить медленные запросы
sail artisan debugbar:enable
```
4. **ТЕСТИРОВАНИЕ API ДЛЯ МОБИЛЬНОГО ПРИЛОЖЕНИЯ:**
```bash
# Протестировать ключевые endpoints
curl -X GET http://localhost/api/my/assignments \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
  ```
