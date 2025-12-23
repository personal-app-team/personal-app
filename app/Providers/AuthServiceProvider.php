<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\{
    Assignment, Shift, WorkRequest, User, Expense, Compensation,
    Candidate, TraineeRequest, RecruitmentRequest, Vacancy, Interview,
    HiringDecision, Department, EmploymentHistory, PositionChangeRequest,
    Category, Specialty, WorkType, Contractor, ContractorRate, ContractorWorker,
    MassPersonnelReport, Photo, VisitedLocation, WorkRequestStatus,
    Project, Purpose, PurposeTemplate, Address, AddressTemplate,
    PurposePayerCompany, PurposeAddressRule, ContractType, TaxStatus,
    InitiatorGrant, ActivityLog
};
use App\Policies\{
    AssignmentPolicy, ShiftPolicy, WorkRequestPolicy, UserPolicy,
    ExpensePolicy, CompensationPolicy, CandidatePolicy, TraineeRequestPolicy,
    RecruitmentRequestPolicy, VacancyPolicy, InterviewPolicy, HiringDecisionPolicy,
    DepartmentPolicy, EmploymentHistoryPolicy, PositionChangeRequestPolicy,
    CategoryPolicy, SpecialtyPolicy, WorkTypePolicy, ContractorPolicy, 
    ContractorRatePolicy, ContractorWorkerPolicy, MassPersonnelReportPolicy, 
    PhotoPolicy, VisitedLocationPolicy, WorkRequestStatusPolicy, ProjectPolicy, 
    PurposePolicy, PurposeTemplatePolicy, AddressPolicy, AddressTemplatePolicy,
    PurposePayerCompanyPolicy, PurposeAddressRulePolicy, ContractTypePolicy, 
    TaxStatusPolicy, InitiatorGrantPolicy, ActivityLogPolicy
};

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Assignment::class => AssignmentPolicy::class,
        Shift::class => ShiftPolicy::class,
        WorkRequest::class => WorkRequestPolicy::class,
        User::class => UserPolicy::class,
        Expense::class => ExpensePolicy::class,
        Compensation::class => CompensationPolicy::class,
        // Остальные политики добавим по мере создания
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Глобальные правила - администратор может всё
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin')) {
                return true;
            }
        });

        // Кастомные gates для специфичных действий
        Gate::define('confirm_assignment', function ($user, $assignment) {
            // Исполнитель может подтверждать только свои назначения
            return $user->hasRole('executor') && 
                   $user->id === $assignment->user_id &&
                   $assignment->status === 'pending';
        });

        Gate::define('reject_assignment', function ($user, $assignment) {
            // Исполнитель может отклонять только свои назначения
            return $user->hasRole('executor') && 
                   $user->id === $assignment->user_id &&
                   $assignment->status === 'pending';
        });

        Gate::define('create_shift', function ($user, $assignment) {
            // Исполнитель может создавать смену только для подтвержденного назначения
            return $user->hasRole('executor') && 
                   $user->id === $assignment->user_id &&
                   $assignment->status === 'confirmed';
        });

        // Gate для взятия заявки в работу
        Gate::define('take_work_request', function ($user, $workRequest) {
            return $user->hasRole('dispatcher') && 
                   $workRequest->status === 'published';
        });
    }
}
