<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class NotificationService
{
    /**
     * Создать уведомление
     */
    public static function create(
        User $user,
        string $type,
        string $title,
        string $message,
        ?Model $related = null,
        ?array $data = null
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'related_type' => $related ? get_class($related) : null,
            'related_id' => $related ? $related->id : null,
        ]);
    }

    /**
     * Уведомление о новом запросе на стажировку для HR
     */
    public static function notifyHrAboutTraineeRequest($traineeRequest): void
    {
        $hrUsers = User::role('hr')->get();

        foreach ($hrUsers as $hrUser) {
            self::create(
                $hrUser,
                'trainee_request',
                'Новый запрос на стажировку',
                "Поступил новый запрос на стажировку от {$traineeRequest->user->name} для кандидата {$traineeRequest->candidate_name}",
                $traineeRequest,
                ['action_url' => '/admin/trainee-requests/' . $traineeRequest->id]
            );
        }
    }

    /**
     * Уведомление о необходимости решения по стажеру
     */
    public static function notifyAboutTraineeDecision($traineeRequest): void
    {
        $initiator = $traineeRequest->user;

        self::create(
            $initiator,
            'trainee_decision',
            'Требуется решение по стажеру',
            "Стажировка кандидата {$traineeRequest->candidate_name} заканчивается завтра. Необходимо принять решение о приеме на работу.",
            $traineeRequest,
            ['action_url' => '/admin/trainee-requests/' . $traineeRequest->id]
        );
    }

    /**
     * Уведомление о скором окончании стажировки
     */
    public static function notifyAboutExpiringTrainee($traineeRequest): void
    {
        $initiator = $traineeRequest->user;

        self::create(
            $initiator,
            'trainee_expiring',
            'Стажировка скоро завершится',
            "Стажировка кандидата {$traineeRequest->candidate_name} завершается через день. Подготовьте решение.",
            $traineeRequest,
            ['action_url' => '/admin/trainee-requests/' . $traineeRequest->id]
        );
    }

    /**
     * Уведомление о блокировке стажера
     */
    public static function notifyAboutTraineeBlocked($traineeRequest): void
    {
        $initiator = $traineeRequest->user;

        self::create(
            $initiator,
            'trainee_blocked',
            'Стажер заблокирован',
            "Стажер {$traineeRequest->candidate_name} был автоматически заблокирован из-за отсутствия решения в течение 24 часов.",
            $traineeRequest
        );
    }

    /**
     * Получить количество непрочитанных уведомлений для пользователя
     */
    public static function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Отметить все уведомления пользователя как прочитанные
     */
    public static function markAllAsRead(User $user): void
    {
        Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
