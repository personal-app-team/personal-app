<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Таблицы пользователей и аутентификации
        $this->addUsersComments();
        $this->addRolesComments();
        $this->addAuthComments();
        
        // 2. Основные бизнес-сущности
        $this->addWorkRequestsComments();
        $this->addBrigadierComments();
        $this->addSpecialtiesComments();
        $this->addShiftsComments();
        $this->addAssignmentsComments();
        
        // 3. Проекты и адреса
        $this->addProjectsComments();
        $this->addAddressesComments();
        
        // 4. Финансы
        $this->addRatesComments();
        $this->addFinancialComments();
        
        // 5. Дополнительные таблицы
        $this->addOtherTablesComments();
    }

    private function addUsersComments()
    {
        DB::statement("ALTER TABLE users COMMENT = 'Пользователи системы'");
        DB::statement("ALTER TABLE users 
            MODIFY COLUMN id bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID пользователя',
            MODIFY COLUMN name varchar(255) NOT NULL COMMENT 'Имя',
            MODIFY COLUMN email varchar(255) NOT NULL COMMENT 'Email',
            MODIFY COLUMN email_verified_at timestamp NULL DEFAULT NULL COMMENT 'Дата подтверждения email',
            MODIFY COLUMN password varchar(255) NOT NULL COMMENT 'Пароль',
            MODIFY COLUMN remember_token varchar(100) DEFAULT NULL COMMENT 'Токен запоминания',
            MODIFY COLUMN created_at timestamp NULL DEFAULT NULL COMMENT 'Дата создания',
            MODIFY COLUMN updated_at timestamp NULL DEFAULT NULL COMMENT 'Дата обновления'");
    }

    private function addWorkRequestsComments()
    {
        DB::statement("ALTER TABLE work_requests COMMENT = 'Заявки на работы'");
        DB::statement("ALTER TABLE work_requests 
            MODIFY COLUMN id bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID заявки',
            MODIFY COLUMN brigadier_id bigint UNSIGNED DEFAULT NULL COMMENT 'ID бригадира',
            MODIFY COLUMN specialty_id bigint UNSIGNED DEFAULT NULL COMMENT 'ID специальности',
            MODIFY COLUMN workers_count int NOT NULL COMMENT 'Количество рабочих',
            MODIFY COLUMN shift_duration int NOT NULL COMMENT 'Продолжительность смены (часы)',
            MODIFY COLUMN status varchar(255) NOT NULL COMMENT 'Статус заявки',
            MODIFY COLUMN created_at timestamp NULL DEFAULT NULL COMMENT 'Дата создания',
            MODIFY COLUMN updated_at timestamp NULL DEFAULT NULL COMMENT 'Дата обновления'");
    }

    private function addBrigadierComments()
    {
        DB::statement("ALTER TABLE brigadier_assignments COMMENT = 'Назначения бригадиров'");
        DB::statement("ALTER TABLE brigadier_assignment_dates COMMENT = 'Даты назначений бригадиров'");
        DB::statement("ALTER TABLE initiator_grants COMMENT = 'Права инициаторов'");
    }

    private function addSpecialtiesComments()
    {
        DB::statement("ALTER TABLE specialties COMMENT = 'Специальности'");
        DB::statement("ALTER TABLE user_specialties COMMENT = 'Специальности пользователей'");
        DB::statement("ALTER TABLE work_types COMMENT = 'Типы работ'");
    }

    private function addShiftsComments()
    {
        DB::statement("ALTER TABLE shifts COMMENT = 'Смены'");
        DB::statement("ALTER TABLE shift_segments COMMENT = 'Сегменты смен'");
        DB::statement("ALTER TABLE shift_photos COMMENT = 'Фотографии смен'");
    }

    private function addAssignmentsComments()
    {
        DB::statement("ALTER TABLE assignments COMMENT = 'Назначения исполнителей'");
        DB::statement("ALTER TABLE contractors COMMENT = 'Подрядчики'");
    }

    private function addProjectsComments()
    {
        DB::statement("ALTER TABLE projects COMMENT = 'Проекты'");
        DB::statement("ALTER TABLE project_assignments COMMENT = 'Назначения проектов'");
    }

    private function addAddressesComments()
    {
        DB::statement("ALTER TABLE addresses COMMENT = 'Адреса'");
        DB::statement("ALTER TABLE address_project COMMENT = 'Связь адресов с проектами'");
    }

    private function addRatesComments()
    {
        DB::statement("ALTER TABLE rates COMMENT = 'Ставки оплаты'");
    }

    private function addFinancialComments()
    {
        DB::statement("ALTER TABLE expenses COMMENT = 'Расходы'");
        DB::statement("ALTER TABLE receipts COMMENT = 'Чеки'");
    }

    private function addRolesComments()
    {
        DB::statement("ALTER TABLE roles COMMENT = 'Роли пользователей'");
        DB::statement("ALTER TABLE permissions COMMENT = 'Разрешения системы'");
        DB::statement("ALTER TABLE model_has_roles COMMENT = 'Связь моделей с ролями'");
        DB::statement("ALTER TABLE model_has_permissions COMMENT = 'Связь моделей с разрешениями'");
        DB::statement("ALTER TABLE role_has_permissions COMMENT = 'Связь ролей с разрешениями'");
    }

    private function addAuthComments()
    {
        DB::statement("ALTER TABLE password_reset_tokens COMMENT = 'Токены сброса пароля'");
        DB::statement("ALTER TABLE personal_access_tokens COMMENT = 'Токены персонального доступа'");
        DB::statement("ALTER TABLE sessions COMMENT = 'Сессии пользователей'");
    }

    private function addOtherTablesComments()
    {
        DB::statement("ALTER TABLE purposes COMMENT = 'Назначения работ'");
        DB::statement("ALTER TABLE purpose_templates COMMENT = 'Шаблоны назначений'");
        DB::statement("ALTER TABLE purpose_payer_companies COMMENT = 'Компании-плательщики'");
        DB::statement("ALTER TABLE purpose_address_rules COMMENT = 'Правила адресов назначений'");
        DB::statement("ALTER TABLE visited_locations COMMENT = 'Посещенные локации'");
        DB::statement("ALTER TABLE failed_jobs COMMENT = 'Неудачные задания очереди'");
        DB::statement("ALTER TABLE migrations COMMENT = 'Миграции базы данных'");
        DB::statement("ALTER TABLE cache COMMENT = 'Кэш системы'");
        DB::statement("ALTER TABLE cache_locks COMMENT = 'Блокировки кэша'");
        DB::statement("ALTER TABLE job_batches COMMENT = 'Пакеты заданий'");
        DB::statement("ALTER TABLE jobs COMMENT = 'Очередь заданий'");
    }

    public function down()
    {
        // При откате просто удаляем все комментарии
        $tables = [
            'users', 'work_requests', 'brigadier_assignments', 'brigadier_assignment_dates', 
            'initiator_grants', 'specialties', 'user_specialties', 'work_types',
            'shifts', 'shift_segments', 'shift_photos', 'assignments', 'contractors',
            'projects', 'project_assignments', 'addresses', 'address_project',
            'rates', 'expenses', 'receipts', 'roles', 'permissions', 'model_has_roles',
            'model_has_permissions', 'role_has_permissions', 'password_reset_tokens',
            'personal_access_tokens', 'sessions', 'purposes', 'purpose_templates',
            'purpose_payer_companies', 'purpose_address_rules', 'visited_locations',
            'failed_jobs', 'migrations', 'cache', 'cache_locks', 'job_batches', 'jobs'
        ];
        
        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} COMMENT = ''");
            // Для основных таблиц также очищаем комментарии полей
            if (in_array($table, ['users', 'work_requests', 'specialties', 'shifts'])) {
                DB::statement("ALTER TABLE {$table} MODIFY COLUMN id bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT ''");
            }
        }
    }
};
