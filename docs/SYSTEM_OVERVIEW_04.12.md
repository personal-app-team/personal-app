**🚀 Personal Management System - Актуальная документация**

**📅 Дата актуализации: 04.12.2025**

**🏗️ Архитектура системы (ПОЛНОСТЬЮ ОБНОВЛЕНА)**

**Основные модули с Filament Resources:**

1. **✅ WorkRequest** - Заявки на работы с проектной структурой
1. **✅ User** - Пользователи с ролями Spatie + кастомными состояниями
1. **✅ Shift** - Смены с системой расчетов и налогообложения
1. **✅ Category/Specialty** - Иерархия категорий и специальностей
1. **✅ WorkType** - Справочник видов работ (только для аналитики)
1. **✅ Contractor** - Подрядчики с персонализированными ставками
1. **✅ Assignment** - Единая система назначений (бригадиры + исполнители)
1. **✅ Compensation** - Компенсации к сменам и отчетам
1. **❌ MassPersonnelReport** - Отчеты по массовому персоналу (модель есть, ресурса нет)
1. **✅ TraineeRequest** - Система управления стажерами
1. **✅ Recruitment** - Полная система подбора персонала
1. **✅ ActivityLog** - Система логирования изменений (Spatie Laravel Activitylog)

**Подсистема подбора персонала (✅ ВСЕ РЕСУРСЫ СОЗДАНЫ):**

- **✅ Vacancy** - Вакансии компании
- **✅ RecruitmentRequest** - Заявки на подбор
- **✅ Candidate** - Кандидаты на позиции (с RelationManagers)
- **✅ CandidateDecision** - Решения заявителей (через RelationManager)
- **✅ Interview** - Собеседования
- **✅ HiringDecision** - Решения о приеме
- **✅ PositionChangeRequest** - Запросы на изменение должностей/оплат
- **✅ Department** - Организационная структура
- **✅ EmploymentHistory** - История трудоустройства

**Финансовые и справочные модули:**

- **✅ ContractType** - Типы договоров
- **✅ TaxStatus** - Налоговые статусы
- **✅ ContractorRate** - Ставки подрядчиков
- **✅ ShiftExpense** - Операционные расходы смен

**Проектная структура:**

- **✅ Project** - Проекты
- **✅ Purpose** - Цели/задачи
- **✅ PurposeTemplate** - Шаблоны целей
- **✅ Address/AddressTemplate** - Адреса и шаблоны адресов

**🔄 Актуальный Workflow системы (ПОЛНЫЙ ЦИКЛ С FILAMENT)**

**Процесс для персонализированного персонала (через Filament):**

1. **Планирование** → AssignmentResource (assignment\_type = 'brigadier\_schedule')
1. **Создание заявки** → WorkRequestResource с проектом/целью/категорией
1. **Комплектование** → AssignmentResource (assignment\_type = 'work\_request')
1. **Выполнение работ** →
   1. Исполнитель: ShiftResource → создание/закрытие смен
   1. Добавление расходов: ShiftExpenseResource
   1. Добавление компенсаций: CompensationResource
1. **Подтверждение** → ShiftResource → проверка и подтверждение смены
1. **Расчеты** → Автоматический расчет по формуле в ShiftResource
1. **Оплата** → Формирование табеля и выплата

**Процесс подбора персонала (полностью в Filament):**

1. **Создание вакансии** → VacancyResource (HR)
1. **Заявка на подбор** → RecruitmentRequestResource (Заявитель)
1. **Распределение HR** → RecruitmentRequestResource → назначение ответственного HR
1. **Поиск кандидатов** → CandidateResource через CandidatesRelationManager
1. **Решение заявителя** → CandidateResource → создание CandidateDecision
1. **Собеседование** → InterviewResource
1. **Решение о найме** → HiringDecisionResource
1. **Стажировка** → TraineeRequestResource (автоматическое создание при решении "trainee")
1. **Изменения должности** → PositionChangeRequestResource

**Процесс управления стажерами:**

1. **Запрос на стажировку** → TraineeRequestResource (Initiator/Dispatcher)
1. **Утверждение HR** → TraineeRequestResource → действие "Утвердить как HR"
1. **Утверждение Manager** → TraineeRequestResource → действие "Утвердить как Manager"
1. **Активная стажировка** → автоматическое создание временного пользователя
1. **Завершение стажировки** → решение о найме через HiringDecisionResource

**👥 Ролевая модель (ПОЛНОСТЬЮ ОБНОВЛЕНА)**

**Базовые роли (Spatie Permissions + RoleResource):**

- **admin** - Полный доступ ко всем ресурсам
- **initiator** - Создание WorkRequest, TraineeRequest, RecruitmentRequest
- **dispatcher** - Управление Assignment, Shift, комплектование заявок
- **executor** - Личный кабинет: просмотр своих Shifts, создание ShiftExpense

**Новые роли системы подбора:**

- **hr** - Доступ к: VacancyResource, RecruitmentRequestResource, CandidateResource, InterviewResource
- **manager** - Утверждение: HiringDecisionResource, PositionChangeRequestResource, TraineeRequestResource
- **trainee** - Ограниченный доступ к системе

**Типы назначений в Assignment:**

- **brigadier\_schedule** - Плановые назначения бригадиров
- **work\_request** - Назначения исполнителей на заявки
- **mass\_personnel** - Назначения массового персонала

**📊 Ключевые сущности (ПОЛНОСТЬЮ ОБНОВЛЕНЫ)**

**AssignmentResource (Единая система назначений):**

- assignment\_type: brigadier\_schedule | work\_request | mass\_personnel
- user\_id → User (исполнитель/бригадир) с выбором из UserResource
- work\_request\_id → WorkRequest (NULL для бригадиров) с выбором из WorkRequestResource
- status: pending | confirmed | rejected | completed с цветовой индикацией

**ShiftResource (Обновлена с расчетами):**

- Автоматический расчет: (base\_rate × worked\_minutes / 60) + compensation\_amount + expenses\_total - tax\_amount = payout\_amount
- Визуализация статусов: scheduled → active → pending\_approval → completed → paid
- Связи: UserResource, WorkRequestResource, Assignment (через assignment\_number)

**CandidateResource (с RelationManagers):**

- **CandidateDecisionsRelationManager** - история решений заявителей
- **CandidateStatusHistoryRelationManager** - полный аудит изменений статусов
- **InterviewsRelationManager** - список собеседований кандидата
- Интеграция с: RecruitmentRequestResource, UserResource (expert), HiringDecisionResource

**TraineeRequestResource (Workflow действия):**

- Действие "Утвердить как HR" - доступно для роли hr
- Действие "Утвердить как Manager" - доступно для роли manager
- Автоматическое создание пользователя при активации стажировки
- Интеграция с HiringDecisionResource при завершении стажировки

**ActivityLogResource (Просмотр логов):**

- Фильтрация по: пользователям, моделям, событиям, датам
- Детальный просмотр изменений полей
- Автоматическая очистка записей старше 365 дней

**🎯 Текущие приоритеты разработки**

**Высокий приоритет (НЕМЕДЛЕННО):**

1. **❌ Создание отсутствующих Filament Resources:**
   1. MassPersonnelReportResource (модель есть, ресурса нет)
   1. Проверить и создать ресурсы для: VisitedLocation, ShiftPhoto, WorkRequestStatus
1. **🔧 Настройка политик доступа для всех Resources:**
   1. Интеграция Spatie Permissions с Filament
   1. Настройка granular permissions для ролей hr, manager, trainee
   1. Тестирование сценариев доступа
1. **🔔 Завершение системы уведомлений:**
   1. Создание модели Notification (если еще не создана)
   1. NotificationResource для просмотра уведомлений
   1. Интеграция NotificationService в workflow

**Средний приоритет:**

4. **📊 Создание дашбордов и виджетов:**
   1. ActivityStatsWidget уже создан, нужны другие виджеты
   1. Дашборд рекрутинга (статистика по вакансиям, кандидатам)
   1. Дашборд оперативной деятельности (Shifts, Assignments)
4. **🔗 Тестирование полных workflow:**
   1. Тест: Вакансия → Заявка → Кандидат → Собеседование → Найм → Назначение → Смена → Оплата
   1. Тест: Запрос на стажировку → Утверждение → Стажировка → Найм
   1. Тест: Изменение должности/зарплаты через PositionChangeRequest

**Низкий приоритет:**

6. **📱 PWA и мобильная адаптация:**
   1. Оптимизация Filament под мобильные устройства
   1. Настройка Progressive Web App
   1. Оффлайн-возможности для исполнителей

**✅ Завершенные задачи (последние)**

**Filament Resources (✅ СОЗДАНЫ ДЛЯ ОСНОВНЫХ МОДУЛЕЙ):**

- ✅ **Система подбора персонала**: VacancyResource, RecruitmentRequestResource, CandidateResource с RelationManagers, InterviewResource, HiringDecisionResource, PositionChangeRequestResource
- ✅ **Система стажеров**: TraineeRequestResource с workflow действиями
- ✅ **Основные модули**: AssignmentResource, ShiftResource, WorkRequestResource, UserResource
- ✅ **Финансовые модули**: CompensationResource, ShiftExpenseResource, ContractorRateResource
- ✅ **Справочники**: CategoryResource, SpecialtyResource, WorkTypeResource, ContractTypeResource, TaxStatusResource
- ✅ **Проектная структура**: ProjectResource, PurposeResource, AddressResource
- ✅ **Логирование**: ActivityLogResource

**Система логирования:**

- ✅ Внедрен Spatie Laravel Activitylog
- ✅ ActivityLogResource создан с фильтрацией и просмотром
- ✅ Настроено логирование для Assignment и Shift
- ✅ Автоматическая очистка логов старше 365 дней

**Интеграция:**

- ✅ Связь CandidateResource с TraineeRequestResource для стажеров
- ✅ Связь HiringDecisionResource с UserResource для создания пользователей
- ✅ RelationManagers для сложных связей (Candidate, User)

**📈 Статистика системы**

**Количество моделей: 40**

**Количество Filament Resources: 34**

**Количество выполненных миграций: 55**

**RelationManagers создано: 15**

**Основные модули с ресурсами: 28 из 32 (87%)**

**🔄 Интеграционные точки в Filament**

**Ключевые RelationManagers:**

1. **UserResource**:
   1. AssignmentsRelationManager - назначения пользователя
   1. EmploymentHistoryRelationManager - история трудоустройства
   1. PositionChangeRequestsRelationManager - запросы на изменения
   1. ShiftsRelationManager - смены пользователя
   1. SpecialtiesRelationManager - специальности пользователя
1. **CandidateResource**:
   1. CandidateDecisionsRelationManager - решения по кандидату
   1. CandidateStatusHistoryRelationManager - история статусов
   1. InterviewsRelationManager - собеседования кандидата
1. **RecruitmentRequestResource**:
   1. CandidatesRelationManager - кандидаты по заявке

**Автоматические действия:**

- Создание смены при подтверждении Assignment (assignment\_type = 'brigadier\_schedule')
- Создание TraineeRequest при решении Interview.result = 'trainee'
- Создание пользователя при утверждении HiringDecision
- Автоматический расчет сумм в ShiftResource

**🚀 Следующие шаги разработки**

1. **ПРОВЕРИТЬ И СОЗДАТЬ ОТСУТСТВУЮЩИЕ РЕСУРСЫ:**

   bash

   *# Проверить какие модели без ресурсов*

   ls app/Models/ | while read model; do

   `  `resource="app/Filament/Resources/${model%.php}Resource.php"

   `  `if [ ! -f "$resource" ]; then

   `    `echo "❌ Нет ресурса для: $model"

   `  `fi

   done

1. **НАСТРОИТЬ ПОЛИТИКИ ДОСТУПА:**
   1. Создать политики для всех ресурсов
   1. Настроить Spatie Permissions в Filament
   1. Протестировать доступ для разных ролей
1. **СОЗДАТЬ ТЕСТОВЫЕ ДАННЫЕ И ПРОТЕСТИРОВАТЬ WORKFLOW:**
   1. Сценарий 1: Полный цикл подбора персонала
   1. Сценарий 2: Стажировка и прием на работу
   1. Сценарий 3: Назначение, работа, расчет, оплата
1. **НАСТРОИТЬ СИСТЕМУ УВЕДОМЛЕНИЙ:**
   1. Создать модель Notification и ресурс
   1. Интегрировать NotificationService в основные workflow
   1. Добавить уведомления в реальном времени
-----
**📝 Примечание**: Система Filament Resources охватывает 87% основных моделей. Осталось создать ресурсы для нескольких вспомогательных моделей и настроить политики доступа. Основной функционал системы уже доступен через админку Filament.

