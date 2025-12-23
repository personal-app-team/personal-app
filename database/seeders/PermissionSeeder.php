<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PermissionSeeder extends Seeder
{
    /**
     * Группы разрешений для нормализации
     * Приводим все к единому формату snake_case
     */
    private array $groupMapping = [
        // Русские названия -> английские snake_case
        'Activity' => 'activity',
        'Address' => 'address',
        'Assignment' => 'assignment',
        'Candidate' => 'candidate',
        'Category' => 'category',
        'Compensation' => 'compensation',
        'Contractor' => 'contractor',
        'ContractType' => 'contract_type',
        'Department' => 'department',
        'EmploymentHistory' => 'employment_history',
        'Expense' => 'expense',
        'filament' => 'system', // объединяем с system
        'HiringDecision' => 'hiring_decision',
        'InitiatorGrant' => 'initiator_grant',
        'Interview' => 'interview',
        'MassPersonnelReport' => 'mass_personnel_report',
        'panel' => 'system', // объединяем с system
        'Permission' => 'permission',
        'Photo' => 'photo',
        'PositionChangeRequest' => 'position_change_request',
        'Project' => 'project',
        'Purpose' => 'purpose',
        'RecruitmentRequest' => 'recruitment_request',
        'reports' => 'report',
        'Role' => 'role',
        'settings' => 'system',
        'Shift' => 'shift',
        'Specialty' => 'specialty',
        'statistics' => 'report',
        'TaxStatus' => 'tax_status',
        'TraineeRequest' => 'trainee_request',
        'User' => 'user',
        'Vacancy' => 'vacancy',
        'VisitedLocation' => 'visited_location',
        'WorkRequest' => 'work_request',
        'WorkType' => 'work_type',
        'database' => 'system',
        'data' => 'system',
        'payments' => 'financial',
        'hiringdecision' => 'hiring_decision',
        
        // Дополнительные группы из Excel
        'AddressProject' => 'address',
        'AddressTemplate' => 'address',
        'CandidateDecision' => 'candidate',
        'CandidateStatusHistory' => 'candidate',
        'ContractorRate' => 'contractor',
        'ContractorWorker' => 'contractor',
        'PurposeAddressRule' => 'purpose',
        'PurposePayerCompany' => 'purpose',
        'PurposeTemplate' => 'purpose',
        'VacancyCondition' => 'vacancy',
        'VacancyRequirement' => 'vacancy',
        'VacancyTask' => 'vacancy',
        'WorkRequestStatus' => 'work_request',
    ];
    
    /**
     * Вставьте сюда сгенерированный массив из generated_permissions.php
     */
    private array $excelPermissions = [
        'view_any_activity_log' => ['group' => 'Activity', 'description' => 'Просмотр списка логов активности'],
        'view_activity_log' => ['group' => 'Activity', 'description' => 'Просмотр записи лога активности'],
        'view_any_address' => ['group' => 'Address', 'description' => 'Просмотр списка адресов'],
        'view_address' => ['group' => 'Address', 'description' => 'Просмотр адреса'],
        'create_address' => ['group' => 'Address', 'description' => 'Создание адреса'],
        'update_address' => ['group' => 'Address', 'description' => 'Редактирование адреса'],
        'delete_address' => ['group' => 'Address', 'description' => 'Удаление адреса'],
        'restore_address' => ['group' => 'Address', 'description' => 'Восстановление адреса'],
        'force_delete_address' => ['group' => 'Address', 'description' => 'Принудительное удаление адреса'],
        'delete_any_address' => ['group' => 'Address', 'description' => 'Массовое удаление адресов'],
        'restore_any_address' => ['group' => 'Address', 'description' => 'Массовое восстановление адресов'],
        'force_delete_any_address' => ['group' => 'Address', 'description' => 'Массовое принудительное удаление адресов'],
        'replicate_address' => ['group' => 'Address', 'description' => 'Копирование адреса'],
        'view_any_address_project' => ['group' => 'AddressProject', 'description' => 'Просмотр списка связей адресов с проектами'],
        'view_address_project' => ['group' => 'AddressProject', 'description' => 'Просмотр связи адреса с проектом'],
        'create_address_project' => ['group' => 'AddressProject', 'description' => 'Создание связи адреса с проектом'],
        'update_address_project' => ['group' => 'AddressProject', 'description' => 'Редактирование связи адреса с проектом'],
        'delete_address_project' => ['group' => 'AddressProject', 'description' => 'Удаление связи адреса с проектом'],
        'delete_any_address_project' => ['group' => 'AddressProject', 'description' => 'Массовое удаление связей адресов с проектами'],
        'restore_address_project' => ['group' => 'AddressProject', 'description' => 'Восстановление связи адреса с проектом'],
        'restore_any_address_project' => ['group' => 'AddressProject', 'description' => 'Массовое восстановление связей адресов с проектами'],
        'force_delete_address_project' => ['group' => 'AddressProject', 'description' => 'Принудительное удаление связи адреса с проектом'],
        'force_delete_any_address_project' => ['group' => 'AddressProject', 'description' => 'Массовое принудительное удаление связей адресов с проектами'],
        'replicate_address_project' => ['group' => 'AddressProject', 'description' => 'Копирование связи адреса с проектом'],
        'view_any_address_template' => ['group' => 'AddressTemplate', 'description' => 'Просмотр списка шаблонов адресов'],
        'view_address_template' => ['group' => 'AddressTemplate', 'description' => 'Просмотр шаблона адреса'],
        'create_address_template' => ['group' => 'AddressTemplate', 'description' => 'Создание шаблона адреса'],
        'update_address_template' => ['group' => 'AddressTemplate', 'description' => 'Редактирование шаблона адреса'],
        'delete_address_template' => ['group' => 'AddressTemplate', 'description' => 'Удаление шаблона адреса'],
        'restore_address_template' => ['group' => 'AddressTemplate', 'description' => 'Восстановление шаблона адреса'],
        'force_delete_address_template' => ['group' => 'AddressTemplate', 'description' => 'Принудительное удаление шаблона адреса'],
        'delete_any_address_template' => ['group' => 'AddressTemplate', 'description' => 'Массовое удаление шаблонов адресов'],
        'restore_any_address_template' => ['group' => 'AddressTemplate', 'description' => 'Массовое восстановление шаблонов адресов'],
        'force_delete_any_address_template' => ['group' => 'AddressTemplate', 'description' => 'Массовое принудительное удаление шаблонов адресов'],
        'replicate_address_template' => ['group' => 'AddressTemplate', 'description' => 'Копирование шаблона адреса'],
        'view_any_assignment' => ['group' => 'Assignment', 'description' => 'Просмотр списка назначений'],
        'view_assignment' => ['group' => 'Assignment', 'description' => 'Просмотр назначения'],
        'create_assignment' => ['group' => 'Assignment', 'description' => 'Создание назначения'],
        'update_assignment' => ['group' => 'Assignment', 'description' => 'Редактирование назначения'],
        'delete_assignment' => ['group' => 'Assignment', 'description' => 'Удаление назначения'],
        'approve_contractor_assignments' => ['group' => 'Assignment', 'description' => 'Утверждение назначений подрядчикам'],
        'view_own_company_assignments' => ['group' => 'Assignment', 'description' => 'Просмотр назначений своей компании'],
        'restore_assignment' => ['group' => 'Assignment', 'description' => 'Восстановление назначения'],
        'force_delete_assignment' => ['group' => 'Assignment', 'description' => 'Принудительное удаление назначения'],
        'delete_any_assignment' => ['group' => 'Assignment', 'description' => 'Массовое удаление назначений'],
        'restore_any_assignment' => ['group' => 'Assignment', 'description' => 'Массовое восстановление назначений'],
        'force_delete_any_assignment' => ['group' => 'Assignment', 'description' => 'Массовое принудительное удаление назначений'],
        'replicate_assignment' => ['group' => 'Assignment', 'description' => 'Копирование назначения'],
        'view_any_candidate' => ['group' => 'Candidate', 'description' => 'Просмотр списка кандидатов'],
        'view_candidate' => ['group' => 'Candidate', 'description' => 'Просмотр кандидата'],
        'create_candidate' => ['group' => 'Candidate', 'description' => 'Создание кандидата'],
        'update_candidate' => ['group' => 'Candidate', 'description' => 'Редактирование кандидата'],
        'delete_candidate' => ['group' => 'Candidate', 'description' => 'Удаление кандидата'],
        'restore_candidate' => ['group' => 'Candidate', 'description' => 'Восстановление кандидата'],
        'force_delete_candidate' => ['group' => 'Candidate', 'description' => 'Принудительное удаление кандидата'],
        'delete_any_candidate' => ['group' => 'Candidate', 'description' => 'Массовое удаление кандидатов'],
        'restore_any_candidate' => ['group' => 'Candidate', 'description' => 'Массовое восстановление кандидатов'],
        'force_delete_any_candidate' => ['group' => 'Candidate', 'description' => 'Массовое принудительное удаление кандидатов'],
        'replicate_candidate' => ['group' => 'Candidate', 'description' => 'Копирование кандидата'],
        'view_any_candidate_decision' => ['group' => 'CandidateDecision', 'description' => 'Просмотр списка решений по кандидатам'],
        'view_candidate_decision' => ['group' => 'CandidateDecision', 'description' => 'Просмотр решения по кандидату'],
        'create_candidate_decision' => ['group' => 'CandidateDecision', 'description' => 'Создание решения по кандидату'],
        'update_candidate_decision' => ['group' => 'CandidateDecision', 'description' => 'Редактирование решения по кандидату'],
        'delete_candidate_decision' => ['group' => 'CandidateDecision', 'description' => 'Удаление решения по кандидату'],
        'restore_candidate_decision' => ['group' => 'CandidateDecision', 'description' => 'Восстановление решения по кандидату'],
        'force_delete_candidate_decision' => ['group' => 'CandidateDecision', 'description' => 'Принудительное удаление решения по кандидату'],
        'delete_any_candidate_decision' => ['group' => 'CandidateDecision', 'description' => 'Массовое удаление решений по кандидатам'],
        'restore_any_candidate_decision' => ['group' => 'CandidateDecision', 'description' => 'Массовое восстановление решений по кандидатам'],
        'force_delete_any_candidate_decision' => ['group' => 'CandidateDecision', 'description' => 'Массовое принудительное удаление решений по кандидатам'],
        'replicate_candidate_decision' => ['group' => 'CandidateDecision', 'description' => 'Копирование решения по кандидату'],
        'view_any_candidate_status_history' => ['group' => 'CandidateStatusHistory', 'description' => 'Просмотр списка историй статусов кандидатов'],
        'view_candidate_status_history' => ['group' => 'CandidateStatusHistory', 'description' => 'Просмотр истории статусов кандидата'],
        'create_candidate_status_history' => ['group' => 'CandidateStatusHistory', 'description' => 'Создание истории статусов кандидата'],
        'update_candidate_status_history' => ['group' => 'CandidateStatusHistory', 'description' => 'Редактирование истории статусов кандидата'],
        'delete_candidate_status_history' => ['group' => 'CandidateStatusHistory', 'description' => 'Удаление истории статусов кандидата'],
        'restore_candidate_status_history' => ['group' => 'CandidateStatusHistory', 'description' => 'Восстановление истории статусов кандидата'],
        'force_delete_candidate_status_history' => ['group' => 'CandidateStatusHistory', 'description' => 'Принудительное удаление истории статусов кандидата'],
        'delete_any_candidate_status_history' => ['group' => 'CandidateStatusHistory', 'description' => 'Массовое удаление историй статусов кандидатов'],
        'restore_any_candidate_status_history' => ['group' => 'CandidateStatusHistory', 'description' => 'Массовое восстановление историй статусов кандидатов'],
        'force_delete_any_candidate_status_history' => ['group' => 'CandidateStatusHistory', 'description' => 'Массовое принудительное удаление историй статусов кандидатов'],
        'replicate_candidate_status_history' => ['group' => 'CandidateStatusHistory', 'description' => 'Копирование истории статусов кандидата'],
        'view_any_category' => ['group' => 'Category', 'description' => 'Просмотр списка категорий'],
        'view_category' => ['group' => 'Category', 'description' => 'Просмотр категории'],
        'create_category' => ['group' => 'Category', 'description' => 'Создание категории'],
        'update_category' => ['group' => 'Category', 'description' => 'Редактирование категории'],
        'delete_category' => ['group' => 'Category', 'description' => 'Удаление категории'],
        'restore_category' => ['group' => 'Category', 'description' => 'Восстановление категории'],
        'force_delete_category' => ['group' => 'Category', 'description' => 'Принудительное удаление категории'],
        'delete_any_category' => ['group' => 'Category', 'description' => 'Массовое удаление категорий'],
        'restore_any_category' => ['group' => 'Category', 'description' => 'Массовое восстановление категорий'],
        'force_delete_any_category' => ['group' => 'Category', 'description' => 'Массовое принудительное удаление категорий'],
        'replicate_category' => ['group' => 'Category', 'description' => 'Копирование категории'],
        'view_any_compensation' => ['group' => 'Compensation', 'description' => 'Просмотр списка компенсаций'],
        'view_compensation' => ['group' => 'Compensation', 'description' => 'Просмотр компенсации'],
        'create_compensation' => ['group' => 'Compensation', 'description' => 'Создание компенсации'],
        'update_compensation' => ['group' => 'Compensation', 'description' => 'Редактирование компенсации'],
        'delete_compensation' => ['group' => 'Compensation', 'description' => 'Удаление компенсации'],
        'restore_compensation' => ['group' => 'Compensation', 'description' => 'Восстановление компенсации'],
        'force_delete_compensation' => ['group' => 'Compensation', 'description' => 'Принудительное удаление компенсации'],
        'delete_any_compensation' => ['group' => 'Compensation', 'description' => 'Массовое удаление компенсаций'],
        'restore_any_compensation' => ['group' => 'Compensation', 'description' => 'Массовое восстановление компенсаций'],
        'force_delete_any_compensation' => ['group' => 'Compensation', 'description' => 'Массовое принудительное удаление компенсаций'],
        'replicate_compensation' => ['group' => 'Compensation', 'description' => 'Копирование компенсации'],
        'view_any_contractor' => ['group' => 'Contractor', 'description' => 'Просмотр списка подрядчиков'],
        'view_contractor' => ['group' => 'Contractor', 'description' => 'Просмотр подрядчика'],
        'create_contractor' => ['group' => 'Contractor', 'description' => 'Создание подрядчика'],
        'update_contractor' => ['group' => 'Contractor', 'description' => 'Редактирование подрядчика'],
        'delete_contractor' => ['group' => 'Contractor', 'description' => 'Удаление подрядчика'],
        'restore_contractor' => ['group' => 'Contractor', 'description' => 'Восстановление подрядчика'],
        'force_delete_contractor' => ['group' => 'Contractor', 'description' => 'Принудительное удаление подрядчика'],
        'delete_any_contractor' => ['group' => 'Contractor', 'description' => 'Массовое удаление подрядчиков'],
        'restore_any_contractor' => ['group' => 'Contractor', 'description' => 'Массовое восстановление подрядчиков'],
        'force_delete_any_contractor' => ['group' => 'Contractor', 'description' => 'Массовое принудительное удаление подрядчиков'],
        'replicate_contractor' => ['group' => 'Contractor', 'description' => 'Копирование подрядчика'],
        'view_any_contractor_rate' => ['group' => 'ContractorRate', 'description' => 'Просмотр списка ставок подрядчиков'],
        'view_contractor_rate' => ['group' => 'ContractorRate', 'description' => 'Просмотр ставки подрядчика'],
        'create_contractor_rate' => ['group' => 'ContractorRate', 'description' => 'Создание ставки подрядчика'],
        'update_contractor_rate' => ['group' => 'ContractorRate', 'description' => 'Редактирование ставки подрядчика'],
        'delete_contractor_rate' => ['group' => 'ContractorRate', 'description' => 'Удаление ставки подрядчика'],
        'restore_contractor_rate' => ['group' => 'ContractorRate', 'description' => 'Восстановление ставки подрядчика'],
        'force_delete_contractor_rate' => ['group' => 'ContractorRate', 'description' => 'Принудительное удаление ставки подрядчика'],
        'delete_any_contractor_rate' => ['group' => 'ContractorRate', 'description' => 'Массовое удаление ставок подрядчиков'],
        'restore_any_contractor_rate' => ['group' => 'ContractorRate', 'description' => 'Массовое восстановление ставок подрядчиков'],
        'force_delete_any_contractor_rate' => ['group' => 'ContractorRate', 'description' => 'Массовое принудительное удаление ставок подрядчиков'],
        'replicate_contractor_rate' => ['group' => 'ContractorRate', 'description' => 'Копирование ставки подрядчика'],
        'view_any_contractor_worker' => ['group' => 'ContractorWorker', 'description' => 'Просмотр списка работников подрядчиков'],
        'view_contractor_worker' => ['group' => 'ContractorWorker', 'description' => 'Просмотр работника подрядчика'],
        'create_contractor_worker' => ['group' => 'ContractorWorker', 'description' => 'Создание работника подрядчика'],
        'update_contractor_worker' => ['group' => 'ContractorWorker', 'description' => 'Редактирование работника подрядчика'],
        'delete_contractor_worker' => ['group' => 'ContractorWorker', 'description' => 'Удаление работника подрядчика'],
        'restore_contractor_worker' => ['group' => 'ContractorWorker', 'description' => 'Восстановление работника подрядчика'],
        'force_delete_contractor_worker' => ['group' => 'ContractorWorker', 'description' => 'Принудительное удаление работника подрядчика'],
        'delete_any_contractor_worker' => ['group' => 'ContractorWorker', 'description' => 'Массовое удаление работников подрядчиков'],
        'restore_any_contractor_worker' => ['group' => 'ContractorWorker', 'description' => 'Массовое восстановление работников подрядчиков'],
        'force_delete_any_contractor_worker' => ['group' => 'ContractorWorker', 'description' => 'Массовое принудительное удаление работников подрядчиков'],
        'replicate_contractor_worker' => ['group' => 'ContractorWorker', 'description' => 'Копирование работника подрядчика'],
        'view_any_contract_type' => ['group' => 'ContractType', 'description' => 'Просмотр списка типов договоров'],
        'view_contract_type' => ['group' => 'ContractType', 'description' => 'Просмотр типа договора'],
        'create_contract_type' => ['group' => 'ContractType', 'description' => 'Создание типа договора'],
        'update_contract_type' => ['group' => 'ContractType', 'description' => 'Редактирование типа договора'],
        'delete_contract_type' => ['group' => 'ContractType', 'description' => 'Удаление типа договора'],
        'restore_contract_type' => ['group' => 'ContractType', 'description' => 'Восстановление типа договора'],
        'force_delete_contract_type' => ['group' => 'ContractType', 'description' => 'Принудительное удаление типа договора'],
        'delete_any_contract_type' => ['group' => 'ContractType', 'description' => 'Массовое удаление типов договоров'],
        'restore_any_contract_type' => ['group' => 'ContractType', 'description' => 'Массовое восстановление типов договоров'],
        'force_delete_any_contract_type' => ['group' => 'ContractType', 'description' => 'Массовое принудительное удаление типов договоров'],
        'replicate_contract_type' => ['group' => 'ContractType', 'description' => 'Копирование типа договора'],
        'view_own_contractor_data' => ['group' => 'data', 'description' => 'Просмотр данных своего подрядчика'],
        'export_data' => ['group' => 'data', 'description' => 'Экспорт данных'],
        'import_data' => ['group' => 'data', 'description' => 'Импорт данных'],
        'edit_database' => ['group' => 'database', 'description' => 'Редактирование базы данных'],
        'view_any_department' => ['group' => 'Department', 'description' => 'Просмотр списка отделов'],
        'view_department' => ['group' => 'Department', 'description' => 'Просмотр отдела'],
        'create_department' => ['group' => 'Department', 'description' => 'Создание отдела'],
        'update_department' => ['group' => 'Department', 'description' => 'Редактирование отдела'],
        'delete_department' => ['group' => 'Department', 'description' => 'Удаление отдела'],
        'restore_department' => ['group' => 'Department', 'description' => 'Восстановление отдела'],
        'force_delete_department' => ['group' => 'Department', 'description' => 'Принудительное удаление отдела'],
        'delete_any_department' => ['group' => 'Department', 'description' => 'Массовое удаление отделов'],
        'restore_any_department' => ['group' => 'Department', 'description' => 'Массовое восстановление отделов'],
        'force_delete_any_department' => ['group' => 'Department', 'description' => 'Массовое принудительное удаление отделов'],
        'replicate_department' => ['group' => 'Department', 'description' => 'Копирование отдела'],
        'view_any_employment_history' => ['group' => 'EmploymentHistory', 'description' => 'Просмотр списка историй трудоустройства'],
        'view_employment_history' => ['group' => 'EmploymentHistory', 'description' => 'Просмотр истории трудоустройства'],
        'create_employment_history' => ['group' => 'EmploymentHistory', 'description' => 'Создание истории трудоустройства'],
        'update_employment_history' => ['group' => 'EmploymentHistory', 'description' => 'Редактирование истории трудоустройства'],
        'delete_employment_history' => ['group' => 'EmploymentHistory', 'description' => 'Удаление истории трудоустройства'],
        'restore_employment_history' => ['group' => 'EmploymentHistory', 'description' => 'Восстановление истории трудоустройства'],
        'force_delete_employment_history' => ['group' => 'EmploymentHistory', 'description' => 'Принудительное удаление истории трудоустройства'],
        'delete_any_employment_history' => ['group' => 'EmploymentHistory', 'description' => 'Массовое удаление историй трудоустройства'],
        'restore_any_employment_history' => ['group' => 'EmploymentHistory', 'description' => 'Массовое восстановление историй трудоустройства'],
        'force_delete_any_employment_history' => ['group' => 'EmploymentHistory', 'description' => 'Массовое принудительное удаление историй трудоустройства'],
        'replicate_employment_history' => ['group' => 'EmploymentHistory', 'description' => 'Копирование истории трудоустройства'],
        'view_any_expense' => ['group' => 'Expense', 'description' => 'Просмотр списка расходов'],
        'view_expense' => ['group' => 'Expense', 'description' => 'Просмотр расхода'],
        'create_expense' => ['group' => 'Expense', 'description' => 'Создание расхода'],
        'update_expense' => ['group' => 'Expense', 'description' => 'Редактирование расхода'],
        'delete_expense' => ['group' => 'Expense', 'description' => 'Удаление расхода'],
        'view_own_company_expenses' => ['group' => 'Expense', 'description' => 'Просмотр расходов своей компании'],
        'restore_expense' => ['group' => 'Expense', 'description' => 'Восстановление расхода'],
        'force_delete_expense' => ['group' => 'Expense', 'description' => 'Принудительное удаление расхода'],
        'approve_expenses' => ['group' => 'Expense', 'description' => 'Утверждение расходов'],
        'delete_any_expense' => ['group' => 'Expense', 'description' => 'Массовое удаление расходов'],
        'restore_any_expense' => ['group' => 'Expense', 'description' => 'Массовое восстановление расходов'],
        'force_delete_any_expense' => ['group' => 'Expense', 'description' => 'Массовое принудительное удаление расходов'],
        'replicate_expense' => ['group' => 'Expense', 'description' => 'Копирование расхода'],
        'access_filament' => ['group' => 'filament', 'description' => 'Доступ к панели Filament'],
        'view_any_hiring_decision' => ['group' => 'HiringDecision', 'description' => 'Просмотр списка решений о найме'],
        'view_hiring_decision' => ['group' => 'HiringDecision', 'description' => 'Просмотр решения о найме'],
        'create_hiring_decision' => ['group' => 'HiringDecision', 'description' => 'Создание решения о найме'],
        'update_hiring_decision' => ['group' => 'HiringDecision', 'description' => 'Редактирование решения о найме'],
        'delete_hiring_decision' => ['group' => 'HiringDecision', 'description' => 'Удаление решения о найме'],
        'restore_hiringdecision' => ['group' => 'hiringdecision', 'description' => 'Восстановление решения о найме'],
        'force_delete_hiringdecision' => ['group' => 'hiringdecision', 'description' => 'Принудительное удаление решения о найме'],
        'delete_any_hiringdecision' => ['group' => 'hiringdecision', 'description' => 'Массовое удаление решений о найме'],
        'restore_any_hiringdecision' => ['group' => 'hiringdecision', 'description' => 'Массовое восстановление решений о найме'],
        'force_delete_any_hiringdecision' => ['group' => 'hiringdecision', 'description' => 'Массовое принудительное удаление решений о найме'],
        'replicate_hiringdecision' => ['group' => 'hiringdecision', 'description' => 'Копирование решения о найме'],
        'view_any_initiator_grant' => ['group' => 'InitiatorGrant', 'description' => 'Просмотр списка предоставлений прав инициатора'],
        'view_initiator_grant' => ['group' => 'InitiatorGrant', 'description' => 'Просмотр предоставления прав инициатора'],
        'create_initiator_grant' => ['group' => 'InitiatorGrant', 'description' => 'Создание предоставления прав инициатора'],
        'update_initiator_grant' => ['group' => 'InitiatorGrant', 'description' => 'Редактирование предоставления прав инициатора'],
        'delete_initiator_grant' => ['group' => 'InitiatorGrant', 'description' => 'Удаление предоставления прав инициатора'],
        'restore_initiator_grant' => ['group' => 'InitiatorGrant', 'description' => 'Восстановление предоставления прав инициатора'],
        'force_delete_initiator_grant' => ['group' => 'InitiatorGrant', 'description' => 'Принудительное удаление предоставления прав инициатора'],
        'delete_any_initiator_grant' => ['group' => 'InitiatorGrant', 'description' => 'Массовое удаление предоставлений прав инициатора'],
        'restore_any_initiator_grant' => ['group' => 'InitiatorGrant', 'description' => 'Массовое восстановление предоставлений прав инициатора'],
        'force_delete_any_initiator_grant' => ['group' => 'InitiatorGrant', 'description' => 'Массовое принудительное удаление предоставлений прав инициатора'],
        'replicate_initiator_grant' => ['group' => 'InitiatorGrant', 'description' => 'Копирование предоставления прав инициатора'],
        'view_any_interview' => ['group' => 'Interview', 'description' => 'Просмотр списка собеседований'],
        'view_interview' => ['group' => 'Interview', 'description' => 'Просмотр собеседования'],
        'create_interview' => ['group' => 'Interview', 'description' => 'Создание собеседования'],
        'update_interview' => ['group' => 'Interview', 'description' => 'Редактирование собеседования'],
        'delete_interview' => ['group' => 'Interview', 'description' => 'Удаление собеседования'],
        'restore_interview' => ['group' => 'Interview', 'description' => 'Восстановление собеседования'],
        'force_delete_interview' => ['group' => 'Interview', 'description' => 'Принудительное удаление собеседования'],
        'delete_any_interview' => ['group' => 'Interview', 'description' => 'Массовое удаление собеседований'],
        'restore_any_interview' => ['group' => 'Interview', 'description' => 'Массовое восстановление собеседований'],
        'force_delete_any_interview' => ['group' => 'Interview', 'description' => 'Массовое принудительное удаление собеседований'],
        'replicate_interview' => ['group' => 'Interview', 'description' => 'Копирование собеседования'],
        'view_any_mass_personnel_report' => ['group' => 'MassPersonnelReport', 'description' => 'Просмотр списка отчетов по массовому персоналу'],
        'view_mass_personnel_report' => ['group' => 'MassPersonnelReport', 'description' => 'Просмотр отчета по массовому персоналу'],
        'create_mass_personnel_report' => ['group' => 'MassPersonnelReport', 'description' => 'Создание отчета по массовому персоналу'],
        'update_mass_personnel_report' => ['group' => 'MassPersonnelReport', 'description' => 'Редактирование отчета по массовому персоналу'],
        'delete_mass_personnel_report' => ['group' => 'MassPersonnelReport', 'description' => 'Удаление отчета по массовому персоналу'],
        'restore_mass_personnel_report' => ['group' => 'MassPersonnelReport', 'description' => 'Восстановление отчета по массовому персоналу'],
        'force_delete_mass_personnel_report' => ['group' => 'MassPersonnelReport', 'description' => 'Принудительное удаление отчета по массовому'],
        'delete_any_mass_personnel_report' => ['group' => 'MassPersonnelReport', 'description' => 'Массовое удаление отчетов по массовому персоналу'],
        'restore_any_mass_personnel_report' => ['group' => 'MassPersonnelReport', 'description' => 'Массовое восстановление отчетов по массовому персоналу'],
        'force_delete_any_mass_personnel_report' => ['group' => 'MassPersonnelReport', 'description' => 'Массовое принудительное удаление отчетов по массовому персоналу'],
        'replicate_mass_personnel_report' => ['group' => 'MassPersonnelReport', 'description' => 'Копирование отчета по массовому персоналу'],
        'access_panel' => ['group' => 'panel', 'description' => 'Доступ к панели управления'],
        'manage_payments' => ['group' => 'payments', 'description' => 'Управление выплатами'],
        'view_any_permission' => ['group' => 'Permission', 'description' => 'Просмотр списка разрешений'],
        'view_permission' => ['group' => 'Permission', 'description' => 'Просмотр разрешения'],
        'create_permission' => ['group' => 'Permission', 'description' => 'Создание разрешения'],
        'update_permission' => ['group' => 'Permission', 'description' => 'Редактирование разрешения'],
        'delete_permission' => ['group' => 'Permission', 'description' => 'Удаление разрешения'],
        'manage_permissions' => ['group' => 'Permission', 'description' => 'Управление разрешениями'],
        'view_any_photo' => ['group' => 'Photo', 'description' => 'Просмотр списка фотографий'],
        'view_photo' => ['group' => 'Photo', 'description' => 'Просмотр фотографии'],
        'create_photo' => ['group' => 'Photo', 'description' => 'Создание фотографии'],
        'update_photo' => ['group' => 'Photo', 'description' => 'Редактирование фотографии'],
        'delete_photo' => ['group' => 'Photo', 'description' => 'Удаление фотографии'],
        'restore_photo' => ['group' => 'Photo', 'description' => 'Восстановление фотографии'],
        'force_delete_photo' => ['group' => 'Photo', 'description' => 'Принудительное удаление фотографии'],
        'delete_any_photo' => ['group' => 'Photo', 'description' => 'Массовое удаление фотографий'],
        'restore_any_photo' => ['group' => 'Photo', 'description' => 'Массовое восстановление фотографий'],
        'force_delete_any_photo' => ['group' => 'Photo', 'description' => 'Массовое принудительное удаление фотографий'],
        'replicate_photo' => ['group' => 'Photo', 'description' => 'Копирование фотографии'],
        'view_any_position_change_request' => ['group' => 'PositionChangeRequest', 'description' => 'Просмотр списка запросов на изменение должности'],
        'view_position_change_request' => ['group' => 'PositionChangeRequest', 'description' => 'Просмотр запроса на изменение должности'],
        'create_position_change_request' => ['group' => 'PositionChangeRequest', 'description' => 'Создание запроса на изменение должности'],
        'update_position_change_request' => ['group' => 'PositionChangeRequest', 'description' => 'Редактирование запроса на изменение должности'],
        'delete_position_change_request' => ['group' => 'PositionChangeRequest', 'description' => 'Удаление запроса на изменение должности'],
        'restore_position_change_request' => ['group' => 'PositionChangeRequest', 'description' => 'Восстановление запроса на изменение должности'],
        'force_delete_position_change_request' => ['group' => 'PositionChangeRequest', 'description' => 'Принудительное удаление запроса на изменение должности'],
        'delete_any_position_change_request' => ['group' => 'PositionChangeRequest', 'description' => 'Массовое удаление запросов на изменение должности'],
        'restore_any_position_change_request' => ['group' => 'PositionChangeRequest', 'description' => 'Массовое восстановление запросов на изменение должности'],
        'force_delete_any_position_change_request' => ['group' => 'PositionChangeRequest', 'description' => 'Массовое принудительное удаление запросов на изменение должности'],
        'replicate_position_change_request' => ['group' => 'PositionChangeRequest', 'description' => 'Копирование запроса на изменение должности'],
        'view_any_project' => ['group' => 'Project', 'description' => 'Просмотр списка проектов'],
        'view_project' => ['group' => 'Project', 'description' => 'Просмотр проекта'],
        'create_project' => ['group' => 'Project', 'description' => 'Создание проекта'],
        'update_project' => ['group' => 'Project', 'description' => 'Редактирование проекта'],
        'delete_project' => ['group' => 'Project', 'description' => 'Удаление проекта'],
        'restore_project' => ['group' => 'Project', 'description' => 'Восстановление проекта'],
        'force_delete_project' => ['group' => 'Project', 'description' => 'Принудительное удаление проекта'],
        'delete_any_project' => ['group' => 'Project', 'description' => 'Массовое удаление проектов'],
        'restore_any_project' => ['group' => 'Project', 'description' => 'Массовое восстановление проектов'],
        'force_delete_any_project' => ['group' => 'Project', 'description' => 'Массовое принудительное удаление проектов'],
        'replicate_project' => ['group' => 'Project', 'description' => 'Копирование проекта'],
        'view_any_purpose' => ['group' => 'Purpose', 'description' => 'Просмотр списка целей/назначений'],
        'view_purpose' => ['group' => 'Purpose', 'description' => 'Просмотр цели/назначения'],
        'create_purpose' => ['group' => 'Purpose', 'description' => 'Создание цели/назначения'],
        'update_purpose' => ['group' => 'Purpose', 'description' => 'Редактирование цели/назначения'],
        'delete_purpose' => ['group' => 'Purpose', 'description' => 'Удаление цели/назначения'],
        'restore_purpose' => ['group' => 'Purpose', 'description' => 'Восстановление цели/назначения'],
        'force_delete_purpose' => ['group' => 'Purpose', 'description' => 'Принудительное удаление цели/назначения'],
        'delete_any_purpose' => ['group' => 'Purpose', 'description' => 'Массовое удаление целей/назначений'],
        'restore_any_purpose' => ['group' => 'Purpose', 'description' => 'Массовое восстановление целей/назначений'],
        'force_delete_any_purpose' => ['group' => 'Purpose', 'description' => 'Массовое принудительное удаление целей/назначений'],
        'replicate_purpose' => ['group' => 'Purpose', 'description' => 'Копирование цели/назначения'],
        'view_any_purpose_address_rule' => ['group' => 'PurposeAddressRule', 'description' => 'Просмотр списка правил адресов назначения'],
        'view_purpose_address_rule' => ['group' => 'PurposeAddressRule', 'description' => 'Просмотр правила адреса назначения'],
        'create_purpose_address_rule' => ['group' => 'PurposeAddressRule', 'description' => 'Создание правила адреса назначения'],
        'update_purpose_address_rule' => ['group' => 'PurposeAddressRule', 'description' => 'Редактирование правила адреса назначения'],
        'delete_purpose_address_rule' => ['group' => 'PurposeAddressRule', 'description' => 'Удаление правила адреса назначения'],
        'restore_purpose_address_rule' => ['group' => 'PurposeAddressRule', 'description' => 'Восстановление правила адреса назначения'],
        'force_delete_purpose_address_rule' => ['group' => 'PurposeAddressRule', 'description' => 'Принудительное удаление правила адреса назначения'],
        'delete_any_purpose_address_rule' => ['group' => 'PurposeAddressRule', 'description' => 'Массовое удаление правил адресов назначения'],
        'restore_any_purpose_address_rule' => ['group' => 'PurposeAddressRule', 'description' => 'Массовое восстановление правил адресов назначения'],
        'force_delete_any_purpose_address_rule' => ['group' => 'PurposeAddressRule', 'description' => 'Массовое принудительное удаление правил адресов назначения'],
        'replicate_purpose_address_rule' => ['group' => 'PurposeAddressRule', 'description' => 'Копирование правила адреса назначения'],
        'view_any_purpose_payer_company' => ['group' => 'PurposePayerCompany', 'description' => 'Просмотр списка компаний-плательщиков назначения'],
        'view_purpose_payer_company' => ['group' => 'PurposePayerCompany', 'description' => 'Просмотр компании-плательщика назначения'],
        'create_purpose_payer_company' => ['group' => 'PurposePayerCompany', 'description' => 'Создание компании-плательщика назначения'],
        'update_purpose_payer_company' => ['group' => 'PurposePayerCompany', 'description' => 'Редактирование компании-плательщика назначения'],
        'delete_purpose_payer_company' => ['group' => 'PurposePayerCompany', 'description' => 'Удаление компании-плательщика назначения'],
        'restore_purpose_payer_company' => ['group' => 'PurposePayerCompany', 'description' => 'Восстановление компании-плательщика назначения'],
        'force_delete_purpose_payer_company' => ['group' => 'PurposePayerCompany', 'description' => 'Принудительное удаление компании-плательщика назначения'],
        'delete_any_purpose_payer_company' => ['group' => 'PurposePayerCompany', 'description' => 'Массовое удаление компаний-плательщиков назначения'],
        'restore_any_purpose_payer_company' => ['group' => 'PurposePayerCompany', 'description' => 'Массовое восстановление компаний-плательщиков назначения'],
        'force_delete_any_purpose_payer_company' => ['group' => 'PurposePayerCompany', 'description' => 'Массовое принудительное удаление компаний-плательщиков назначения'],
        'replicate_purpose_payer_company' => ['group' => 'PurposePayerCompany', 'description' => 'Копирование компании-плательщика назначения'],
        'view_any_purpose_template' => ['group' => 'PurposeTemplate', 'description' => 'Просмотр списка шаблонов назначений'],
        'view_purpose_template' => ['group' => 'PurposeTemplate', 'description' => 'Просмотр шаблона назначения'],
        'create_purpose_template' => ['group' => 'PurposeTemplate', 'description' => 'Создание шаблона назначения'],
        'update_purpose_template' => ['group' => 'PurposeTemplate', 'description' => 'Редактирование шаблона назначения'],
        'delete_purpose_template' => ['group' => 'PurposeTemplate', 'description' => 'Удаление шаблона назначения'],
        'restore_purpose_template' => ['group' => 'PurposeTemplate', 'description' => 'Восстановление шаблона назначения'],
        'force_delete_purpose_template' => ['group' => 'PurposeTemplate', 'description' => 'Принудительное удаление шаблона назначения'],
        'delete_any_purpose_template' => ['group' => 'PurposeTemplate', 'description' => 'Массовое удаление шаблонов назначений'],
        'restore_any_purpose_template' => ['group' => 'PurposeTemplate', 'description' => 'Массовое восстановление шаблонов назначений'],
        'force_delete_any_purpose_template' => ['group' => 'PurposeTemplate', 'description' => 'Массовое принудительное удаление шаблонов назначений'],
        'replicate_purpose_template' => ['group' => 'PurposeTemplate', 'description' => 'Копирование шаблона назначения'],
        'view_any_recruitment_request' => ['group' => 'RecruitmentRequest', 'description' => 'Просмотр списка заявок на подбор'],
        'view_recruitment_request' => ['group' => 'RecruitmentRequest', 'description' => 'Просмотр заявки на подбор'],
        'create_recruitment_request' => ['group' => 'RecruitmentRequest', 'description' => 'Создание заявки на подбор'],
        'update_recruitment_request' => ['group' => 'RecruitmentRequest', 'description' => 'Редактирование заявки на подбор'],
        'delete_recruitment_request' => ['group' => 'RecruitmentRequest', 'description' => 'Удаление заявки на подбор'],
        'restore_recruitment_request' => ['group' => 'RecruitmentRequest', 'description' => 'Восстановление заявки на подбор'],
        'force_delete_recruitment_request' => ['group' => 'RecruitmentRequest', 'description' => 'Принудительное удаление заявки на подбор'],
        'delete_any_recruitment_request' => ['group' => 'RecruitmentRequest', 'description' => 'Массовое удаление заявок на подбор'],
        'restore_any_recruitment_request' => ['group' => 'RecruitmentRequest', 'description' => 'Массовое восстановление заявок на подбор'],
        'force_delete_any_recruitment_request' => ['group' => 'RecruitmentRequest', 'description' => 'Массовое принудительное удаление заявок на подбор'],
        'replicate_recruitment_request' => ['group' => 'RecruitmentRequest', 'description' => 'Копирование заявки на подбор'],
        'view_reports' => ['group' => 'reports', 'description' => 'Просмотр отчетов'],
        'view_any_role' => ['group' => 'Role', 'description' => 'Просмотр списка ролей'],
        'view_role' => ['group' => 'Role', 'description' => 'Просмотр роли'],
        'create_role' => ['group' => 'Role', 'description' => 'Создание роли'],
        'update_role' => ['group' => 'Role', 'description' => 'Редактирование роли'],
        'delete_role' => ['group' => 'Role', 'description' => 'Удаление роли'],
        'restore_role' => ['group' => 'Role', 'description' => 'Восстановление роли'],
        'force_delete_role' => ['group' => 'Role', 'description' => 'Принудительное удаление роли'],
        'assign_roles' => ['group' => 'Role', 'description' => 'Назначение ролей пользователям'],
        'manage_settings' => ['group' => 'settings', 'description' => 'Управление настройками системы'],
        'view_any_shift' => ['group' => 'Shift', 'description' => 'Просмотр списка смен'],
        'view_shift' => ['group' => 'Shift', 'description' => 'Просмотр смены'],
        'create_shift' => ['group' => 'Shift', 'description' => 'Создание смены'],
        'update_shift' => ['group' => 'Shift', 'description' => 'Редактирование смены'],
        'delete_shift' => ['group' => 'Shift', 'description' => 'Удаление смены'],
        'view_own_company_shifts' => ['group' => 'Shift', 'description' => 'Просмотр смен своей компании'],
        'restore_shift' => ['group' => 'Shift', 'description' => 'Восстановление смены'],
        'force_delete_shift' => ['group' => 'Shift', 'description' => 'Принудительное удаление смены'],
        'approve_shifts' => ['group' => 'Shift', 'description' => 'Утверждение смен'],
        'delete_any_shift' => ['group' => 'Shift', 'description' => 'Массовое удаление смен'],
        'restore_any_shift' => ['group' => 'Shift', 'description' => 'Массовое восстановление смен'],
        'force_delete_any_shift' => ['group' => 'Shift', 'description' => 'Массовое принудительное удаление смен'],
        'replicate_shift' => ['group' => 'Shift', 'description' => 'Копирование смены'],
        'view_any_specialty' => ['group' => 'Specialty', 'description' => 'Просмотр списка специальностей'],
        'view_specialty' => ['group' => 'Specialty', 'description' => 'Просмотр специальности'],
        'create_specialty' => ['group' => 'Specialty', 'description' => 'Создание специальности'],
        'update_specialty' => ['group' => 'Specialty', 'description' => 'Редактирование специальности'],
        'delete_specialty' => ['group' => 'Specialty', 'description' => 'Удаление специальности'],
        'restore_specialty' => ['group' => 'Specialty', 'description' => 'Восстановление специальности'],
        'force_delete_specialty' => ['group' => 'Specialty', 'description' => 'Принудительное удаление специальности'],
        'delete_any_specialty' => ['group' => 'Specialty', 'description' => 'Массовое удаление специальностей'],
        'restore_any_specialty' => ['group' => 'Specialty', 'description' => 'Массовое восстановление специальностей'],
        'force_delete_any_specialty' => ['group' => 'Specialty', 'description' => 'Массовое принудительное удаление специальностей'],
        'replicate_specialty' => ['group' => 'Specialty', 'description' => 'Копирование специальности'],
        'view_contractor_statistics' => ['group' => 'statistics', 'description' => 'Просмотр статистики подрядчика'],
        'view_any_tax_status' => ['group' => 'TaxStatus', 'description' => 'Просмотр списка налоговых статусов'],
        'view_tax_status' => ['group' => 'TaxStatus', 'description' => 'Просмотр налогового статуса'],
        'create_tax_status' => ['group' => 'TaxStatus', 'description' => 'Создание налогового статуса'],
        'update_tax_status' => ['group' => 'TaxStatus', 'description' => 'Редактирование налогового статуса'],
        'delete_tax_status' => ['group' => 'TaxStatus', 'description' => 'Удаление налогового статуса'],
        'restore_tax_status' => ['group' => 'TaxStatus', 'description' => 'Восстановление налогового статуса'],
        'force_delete_tax_status' => ['group' => 'TaxStatus', 'description' => 'Принудительное удаление налогового статуса'],
        'delete_any_tax_status' => ['group' => 'TaxStatus', 'description' => 'Массовое удаление налоговых статусов'],
        'restore_any_tax_status' => ['group' => 'TaxStatus', 'description' => 'Массовое восстановление налоговых статусов'],
        'force_delete_any_tax_status' => ['group' => 'TaxStatus', 'description' => 'Массовое принудительное удаление налоговых статусов'],
        'replicate_tax_status' => ['group' => 'TaxStatus', 'description' => 'Копирование налогового статуса'],
        'view_any_trainee_request' => ['group' => 'TraineeRequest', 'description' => 'Просмотр списка заявок на стажировку'],
        'view_trainee_request' => ['group' => 'TraineeRequest', 'description' => 'Просмотр заявки на стажировку'],
        'create_trainee_request' => ['group' => 'TraineeRequest', 'description' => 'Создание заявки на стажировку'],
        'update_trainee_request' => ['group' => 'TraineeRequest', 'description' => 'Редактирование заявки на стажировку'],
        'delete_trainee_request' => ['group' => 'TraineeRequest', 'description' => 'Удаление заявки на стажировку'],
        'restore_trainee_request' => ['group' => 'TraineeRequest', 'description' => 'Восстановление заявки на стажировку'],
        'force_delete_trainee_request' => ['group' => 'TraineeRequest', 'description' => 'Принудительное удаление заявки на стажировку'],
        'delete_any_trainee_request' => ['group' => 'TraineeRequest', 'description' => 'Массовое удаление заявок на стажировку'],
        'restore_any_trainee_request' => ['group' => 'TraineeRequest', 'description' => 'Массовое восстановление заявок на стажировку'],
        'force_delete_any_trainee_request' => ['group' => 'TraineeRequest', 'description' => 'Массовое принудительное удаление заявок на стажировку'],
        'replicate_trainee_request' => ['group' => 'TraineeRequest', 'description' => 'Копирование заявки на стажировку'],
        'view_any_user' => ['group' => 'User', 'description' => 'Просмотр списка пользователей'],
        'view_user' => ['group' => 'User', 'description' => 'Просмотр пользователя'],
        'create_user' => ['group' => 'User', 'description' => 'Создание пользователя'],
        'update_user' => ['group' => 'User', 'description' => 'Редактирование пользователя'],
        'delete_user' => ['group' => 'User', 'description' => 'Удаление пользователя'],
        'manage_contractor_users' => ['group' => 'User', 'description' => 'Управление пользователями подрядчика'],
        'view_own_company_users' => ['group' => 'User', 'description' => 'Просмотр пользователей своей компании'],
        'restore_user' => ['group' => 'User', 'description' => 'Восстановление пользователя'],
        'force_delete_user' => ['group' => 'User', 'description' => 'Принудительное удаление пользователя'],
        'delete_any_user' => ['group' => 'User', 'description' => 'Массовое удаление пользователей'],
        'restore_any_user' => ['group' => 'User', 'description' => 'Массовое восстановление пользователей'],
        'force_delete_any_user' => ['group' => 'User', 'description' => 'Массовое принудительное удаление пользователей'],
        'replicate_user' => ['group' => 'User', 'description' => 'Копирование пользователя'],
        'view_any_vacancy' => ['group' => 'Vacancy', 'description' => 'Просмотр списка вакансий'],
        'view_vacancy' => ['group' => 'Vacancy', 'description' => 'Просмотр вакансии'],
        'create_vacancy' => ['group' => 'Vacancy', 'description' => 'Создание вакансии'],
        'update_vacancy' => ['group' => 'Vacancy', 'description' => 'Редактирование вакансии'],
        'delete_vacancy' => ['group' => 'Vacancy', 'description' => 'Удаление вакансии'],
        'restore_vacancy' => ['group' => 'Vacancy', 'description' => 'Восстановление вакансии'],
        'force_delete_vacancy' => ['group' => 'Vacancy', 'description' => 'Принудительное удаление вакансии'],
        'delete_any_vacancy' => ['group' => 'Vacancy', 'description' => 'Массовое удаление вакансий'],
        'restore_any_vacancy' => ['group' => 'Vacancy', 'description' => 'Массовое восстановление вакансий'],
        'force_delete_any_vacancy' => ['group' => 'Vacancy', 'description' => 'Массовое принудительное удаление вакансий'],
        'replicate_vacancy' => ['group' => 'Vacancy', 'description' => 'Копирование вакансии'],
        'view_any_vacancy_condition' => ['group' => 'VacancyCondition', 'description' => 'Просмотр списка условий вакансий'],
        'view_vacancy_condition' => ['group' => 'VacancyCondition', 'description' => 'Просмотр условия вакансии'],
        'create_vacancy_condition' => ['group' => 'VacancyCondition', 'description' => 'Создание условия вакансии'],
        'update_vacancy_condition' => ['group' => 'VacancyCondition', 'description' => 'Редактирование условия вакансии'],
        'delete_vacancy_condition' => ['group' => 'VacancyCondition', 'description' => 'Удаление условия вакансии'],
        'restore_vacancy_condition' => ['group' => 'VacancyCondition', 'description' => 'Восстановление условия вакансии'],
        'force_delete_vacancy_condition' => ['group' => 'VacancyCondition', 'description' => 'Принудительное удаление условия вакансии'],
        'delete_any_vacancy_condition' => ['group' => 'VacancyCondition', 'description' => 'Массовое удаление условий вакансий'],
        'restore_any_vacancy_condition' => ['group' => 'VacancyCondition', 'description' => 'Массовое восстановление условий вакансий'],
        'force_delete_any_vacancy_condition' => ['group' => 'VacancyCondition', 'description' => 'Массовое принудительное удаление условий вакансий'],
        'replicate_vacancy_condition' => ['group' => 'VacancyCondition', 'description' => 'Копирование условия вакансии'],
        'view_any_vacancy_requirement' => ['group' => 'VacancyRequirement', 'description' => 'Просмотр списка требований вакансий'],
        'view_vacancy_requirement' => ['group' => 'VacancyRequirement', 'description' => 'Просмотр требования вакансии'],
        'create_vacancy_requirement' => ['group' => 'VacancyRequirement', 'description' => 'Создание требования вакансии'],
        'update_vacancy_requirement' => ['group' => 'VacancyRequirement', 'description' => 'Редактирование требования вакансии'],
        'delete_vacancy_requirement' => ['group' => 'VacancyRequirement', 'description' => 'Удаление требования вакансии'],
        'restore_vacancy_requirement' => ['group' => 'VacancyRequirement', 'description' => 'Восстановление требования вакансии'],
        'force_delete_vacancy_requirement' => ['group' => 'VacancyRequirement', 'description' => 'Принудительное удаление требования вакансии'],
        'delete_any_vacancy_requirement' => ['group' => 'VacancyRequirement', 'description' => 'Массовое удаление требований вакансий'],
        'restore_any_vacancy_requirement' => ['group' => 'VacancyRequirement', 'description' => 'Массовое восстановление требований вакансий'],
        'force_delete_any_vacancy_requirement' => ['group' => 'VacancyRequirement', 'description' => 'Массовое принудительное удаление требований вакансий'],
        'replicate_vacancy_requirement' => ['group' => 'VacancyRequirement', 'description' => 'Копирование требования вакансии'],
        'view_any_vacancy_task' => ['group' => 'VacancyTask', 'description' => 'Просмотр списка задач вакансий'],
        'view_vacancy_task' => ['group' => 'VacancyTask', 'description' => 'Просмотр задачи вакансии'],
        'create_vacancy_task' => ['group' => 'VacancyTask', 'description' => 'Создание задачи вакансии'],
        'update_vacancy_task' => ['group' => 'VacancyTask', 'description' => 'Редактирование задачи вакансии'],
        'delete_vacancy_task' => ['group' => 'VacancyTask', 'description' => 'Удаление задачи вакансии'],
        'restore_vacancy_task' => ['group' => 'VacancyTask', 'description' => 'Восстановление задачи вакансии'],
        'force_delete_vacancy_task' => ['group' => 'VacancyTask', 'description' => 'Принудительное удаление задачи вакансии'],
        'delete_any_vacancy_task' => ['group' => 'VacancyTask', 'description' => 'Массовое удаление задач вакансий'],
        'restore_any_vacancy_task' => ['group' => 'VacancyTask', 'description' => 'Массовое восстановление задач вакансий'],
        'force_delete_any_vacancy_task' => ['group' => 'VacancyTask', 'description' => 'Массовое принудительное удаление задач вакансий'],
        'replicate_vacancy_task' => ['group' => 'VacancyTask', 'description' => 'Копирование задачи вакансии'],
        'view_any_visited_location' => ['group' => 'VisitedLocation', 'description' => 'Просмотр списка посещенных локаций'],
        'view_visited_location' => ['group' => 'VisitedLocation', 'description' => 'Просмотр посещенной локации'],
        'create_visited_location' => ['group' => 'VisitedLocation', 'description' => 'Создание посещенной локации'],
        'update_visited_location' => ['group' => 'VisitedLocation', 'description' => 'Редактирование посещенной локации'],
        'delete_visited_location' => ['group' => 'VisitedLocation', 'description' => 'Удаление посещенной локации'],
        'restore_visited_location' => ['group' => 'VisitedLocation', 'description' => 'Восстановление посещенной локации'],
        'force_delete_visited_location' => ['group' => 'VisitedLocation', 'description' => 'Принудительное удаление посещенной локации'],
        'delete_any_visited_location' => ['group' => 'VisitedLocation', 'description' => 'Массовое удаление посещенных локаций'],
        'restore_any_visited_location' => ['group' => 'VisitedLocation', 'description' => 'Массовое восстановление посещенных локаций'],
        'force_delete_any_visited_location' => ['group' => 'VisitedLocation', 'description' => 'Массовое принудительное удаление посещенных локаций'],
        'replicate_visited_location' => ['group' => 'VisitedLocation', 'description' => 'Копирование посещенной локации'],
        'view_any_work_request' => ['group' => 'WorkRequest', 'description' => 'Просмотр списка заявок на работы'],
        'view_work_request' => ['group' => 'WorkRequest', 'description' => 'Просмотр заявки на работы'],
        'create_work_request' => ['group' => 'WorkRequest', 'description' => 'Создание заявки на работы'],
        'update_work_request' => ['group' => 'WorkRequest', 'description' => 'Редактирование заявки на работы'],
        'delete_work_request' => ['group' => 'WorkRequest', 'description' => 'Удаление заявки на работы'],
        'create_contractor_work_request' => ['group' => 'WorkRequest', 'description' => 'Создание заявки на работы для подрядчика'],
        'restore_work_request' => ['group' => 'WorkRequest', 'description' => 'Восстановление заявки на работы'],
        'force_delete_work_request' => ['group' => 'WorkRequest', 'description' => 'Принудительное удаление заявки на работы'],
        'delete_any_work_request' => ['group' => 'WorkRequest', 'description' => 'Массовое удаление заявок на работы'],
        'restore_any_work_request' => ['group' => 'WorkRequest', 'description' => 'Массовое восстановление заявок на работы'],
        'force_delete_any_work_request' => ['group' => 'WorkRequest', 'description' => 'Массовое принудительное удаление заявок на работы'],
        'replicate_work_request' => ['group' => 'WorkRequest', 'description' => 'Копирование заявки на работы'],
        'view_any_work_request_status' => ['group' => 'WorkRequestStatus', 'description' => 'Просмотр списка статусов заявок на работы'],
        'view_work_request_status' => ['group' => 'WorkRequestStatus', 'description' => 'Просмотр статуса заявки на работы'],
        'create_work_request_status' => ['group' => 'WorkRequestStatus', 'description' => 'Создание статуса заявки на работы'],
        'update_work_request_status' => ['group' => 'WorkRequestStatus', 'description' => 'Редактирование статуса заявки на работы'],
        'delete_work_request_status' => ['group' => 'WorkRequestStatus', 'description' => 'Удаление статуса заявки на работы'],
        'restore_work_request_status' => ['group' => 'WorkRequestStatus', 'description' => 'Восстановление статуса заявки на работы'],
        'force_delete_work_request_status' => ['group' => 'WorkRequestStatus', 'description' => 'Принудительное удаление статуса заявки на работы'],
        'delete_any_work_request_status' => ['group' => 'WorkRequestStatus', 'description' => 'Массовое удаление статусов заявок на работы'],
        'restore_any_work_request_status' => ['group' => 'WorkRequestStatus', 'description' => 'Массовое восстановление статусов заявок на работы'],
        'force_delete_any_work_request_status' => ['group' => 'WorkRequestStatus', 'description' => 'Массовое принудительное удаление статусов заявок на работы'],
        'replicate_work_request_status' => ['group' => 'WorkRequestStatus', 'description' => 'Копирование статуса заявки на работы'],
        'view_any_worktype' => ['group' => 'WorkType', 'description' => 'Просмотр списка типов работ'],
        'view_worktype' => ['group' => 'WorkType', 'description' => 'Просмотр типа работ'],
        'create_worktype' => ['group' => 'WorkType', 'description' => 'Создание типа работ'],
        'update_worktype' => ['group' => 'WorkType', 'description' => 'Редактирование типа работ'],
        'delete_worktype' => ['group' => 'WorkType', 'description' => 'Удаление типа работ'],
        'restore_worktype' => ['group' => 'WorkType', 'description' => 'Восстановление типа работ'],
        'force_delete_worktype' => ['group' => 'WorkType', 'description' => 'Принудительное удаление типа работ'],
        'delete_any_worktype' => ['group' => 'WorkType', 'description' => 'Массовое удаление типов работ'],
        'restore_any_worktype' => ['group' => 'WorkType', 'description' => 'Массовое восстановление типов работ'],
        'force_delete_any_worktype' => ['group' => 'WorkType', 'description' => 'Массовое принудительное удаление типов работ'],
        'replicate_worktype' => ['group' => 'WorkType', 'description' => 'Копирование типа работ'],
    ];

    public function run(): void
    {
        $this->command->info('🚀 Запуск PermissionSeeder...');
        
        // Проверяем структуру таблицы permissions
        if (!Schema::hasColumn('permissions', 'group')) {
            $this->command->error('❌ В таблице permissions отсутствует колонка "group"');
            $this->command->info('Запустите миграцию: sail artisan migrate');
            return;
        }
        
        // Очищаем кэш разрешений
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        DB::transaction(function () {
            $stats = [
                'updated' => 0,
                'created' => 0,
                'skipped' => 0,
            ];
            
            // Если массив пустой, показываем предупреждение
            if (empty($this->excelPermissions)) {
                $this->command->warn('⚠️  Массив excelPermissions пустой!');
                $this->command->info('Запустите скрипт convert_excel_to_php.php для генерации массива');
                return;
            }
            
            $this->command->info("📊 Загружено " . count($this->excelPermissions) . " разрешений из Excel");
            
            // Обновляем существующие разрешения и создаем недостающие
            foreach ($this->excelPermissions as $name => $excelData) {
                $normalizedGroup = $this->normalizeGroup($excelData['group']);
                
                // Ищем существующее разрешение
                $permission = Permission::where('name', $name)->first();
                
                if ($permission) {
                    // Проверяем, нужно ли обновить
                    $needsUpdate = false;
                    
                    if ($permission->group !== $normalizedGroup) {
                        $permission->group = $normalizedGroup;
                        $needsUpdate = true;
                    }
                    
                    if ($permission->description !== $excelData['description']) {
                        $permission->description = $excelData['description'];
                        $needsUpdate = true;
                    }
                    
                    if ($needsUpdate) {
                        $permission->save();
                        $stats['updated']++;
                    } else {
                        $stats['skipped']++;
                    }
                } else {
                    // Создаем новое разрешение
                    Permission::create([
                        'name' => $name,
                        'group' => $normalizedGroup,
                        'description' => $excelData['description'],
                        'guard_name' => 'web',
                    ]);
                    $stats['created']++;
                }
            }
            
            // Статистика по группам
            $groupStats = Permission::select('group', DB::raw('count(*) as count'))
                ->whereNotNull('group')
                ->groupBy('group')
                ->orderBy('count', 'desc')
                ->pluck('count', 'group')
                ->toArray();
            
            $this->command->info("📊 Результат:");
            $this->command->info("  ✅ Создано: {$stats['created']}");
            $this->command->info("  🔄 Обновлено: {$stats['updated']}");
            $this->command->info("  ⏭️  Пропущено: {$stats['skipped']}");
            $this->command->info("  📈 Всего в базе: " . Permission::count());
            
            if (!empty($groupStats)) {
                $this->command->info("\n📊 Группы разрешений:");
                foreach ($groupStats as $group => $count) {
                    $this->command->info("  - {$group}: {$count}");
                }
            }
        });
        
        $this->command->info('✅ PermissionSeeder завершен!');
    }
    
    /**
     * Нормализуем название группы
     */
    private function normalizeGroup(string $group): string
    {
        $group = trim($group);
        
        // Если группа есть в маппинге, используем нормализованное значение
        if (isset($this->groupMapping[$group])) {
            return $this->groupMapping[$group];
        }
        
        // Приводим к snake_case
        $normalized = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $group));
        $normalized = trim($normalized, '_');
        
        return $normalized;
    }
    
    /**
     * Метод для получения статистики (можно использовать для отладки)
     */
    public function getGroupStatistics(): array
    {
        $groups = [];
        foreach ($this->excelPermissions as $data) {
            $originalGroup = $data['group'];
            $normalizedGroup = $this->normalizeGroup($originalGroup);
            $groups[$originalGroup] = $normalizedGroup;
        }
        
        return array_unique($groups);
    }
}
