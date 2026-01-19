**🎯 Уточненный план системы подбора персонала**

**📋 Содержание**

1. Концепция системы
1. Участники процесса
1. Сущности и их поля
1. Workflow системы
1. Типы сотрудников
1. План реализации

**🎯 Концепция системы**

**Цель:** Отследить полный путь от потребности в сотрудниках до приема на работу с установлением должности и зарплаты, включая все промежуточные решения и ответственных лиц.

**Ключевые аспекты:**

- Полная трассируемость решений
- Четкое распределение ответственности
- Интеграция с существующей системой стажировок
- Разделение на временных и постоянных сотрудников

**👥 Участники процесса**

**Роли:**

- **Главный HR** - распределяет заявки, контролирует процесс
- **HR** - ведет кандидатов, организует собеседования
- **Заявитель** - создает заявки, принимает решения по кандидатам
- **Собеседующий** - проводит собеседования (может быть заявителем или назначенным сотрудником)
- **Утверждающий изменения** - утверждает изменения должностей/зарплат

**📊 Сущности и их поля**

**1. Vacancy (Вакансия) - создается HR**

php

\- id

\- title                      *// Название вакансии*

\- short\_description          *// Краткое описание*

\- employment\_type            *// temporary/permanent*

\- department\_id              *// Отдел*

\- created\_by\_id              *// HR создавший вакансию*

\- status                    *// active/closed*

\- created\_at, updated\_at

*// Связи*

\- tasks(): HasMany VacancyTask

\- requirements(): HasMany VacancyRequirement  

\- conditions(): HasMany VacancyCondition

\- recruitmentRequests(): HasMany RecruitmentRequest

**2. RecruitmentRequest (Заявка на подбор) - создается Заявителем**

php

\- id

\- vacancy\_id                *// Вакансия*

\- user\_id                   *// Заявитель*

\- department\_id             *// Отдел*

\- comment                   *// Комментарий заявителя*

\- required\_count            *// Требуемое количество*

\- employment\_type           *// temporary/permanent*

\- start\_date                *// Период с*

\- end\_date                  *// Период по (для temporary)*

\- hr\_responsible\_id         *// Ответственный HR (назначается главным HR)*

\- status                   *// new/assigned/in\_progress/completed/cancelled*

\- urgency                  *// low/medium/high*

\- deadline                 *// Крайний срок закрытия*

\- created\_at, updated\_at

*// Связи*

\- vacancy(): BelongsTo Vacancy

\- user(): BelongsTo User (заявитель)

\- hrResponsible(): BelongsTo User (HR)

\- department(): BelongsTo Department

\- candidates(): HasMany Candidate

**3. Candidate (Кандидат) - ведется HR**

php

\- id

\- full\_name

\- recruitment\_request\_id    *// Заявка на подбор*

\- phone

\- email

\- resume\_path               *// Файл резюме*

\- source                    *// hh/linkedin/recruitment/etc*

\- first\_contact\_date

\- hr\_contact\_date

\- expert\_id                 *// Заявитель (эксперт)*

\- status                   *// new/contacted/sent\_for\_approval/approved\_for\_interview/in\_reserve/rejected/etc*

\- notes

\- current\_stage            *// Текущий этап*

\- created\_by\_id            *// HR создавший запись*

\- created\_at, updated\_at

*// Связи*

\- recruitmentRequest(): BelongsTo RecruitmentRequest

\- expert(): BelongsTo User (заявитель)

\- interviews(): HasMany Interview

\- candidateDecisions(): HasMany CandidateDecision

\- candidateStatusHistory(): HasMany CandidateStatusHistory

**4. CandidateStatusHistory (История статусов)**

php

\- id

\- candidate\_id

\- status

\- comment

\- changed\_by\_id            *// Кто изменил статус*

\- previous\_status

\- created\_at

*// Связи*

\- candidate(): BelongsTo Candidate

\- changedBy(): BelongsTo User

**5. CandidateDecision (Решение заявителя)**

php

\- id

\- candidate\_id

\- user\_id                  *// Заявитель принявший решение*

\- decision                *// reject/reserve/interview/other\_vacancy*

\- comment

\- decision\_date

\- created\_at

*// Связи*

\- candidate(): BelongsTo Candidate

\- user(): BelongsTo User

**6. Interview (Собеседование)**

php

\- id

\- candidate\_id

\- scheduled\_at             *// Дата и время*

\- interview\_type          *// technical/managerial/cultural*

\- location

\- interviewer\_id          *// Кто проводит собеседование*

\- status                 *// scheduled/completed/cancelled*

\- result                *// hire/reject/reserve/other\_vacancy/trainee*

\- feedback

\- notes

\- duration\_minutes

\- created\_by\_id          *// HR создавший запись*

\- created\_at, updated\_at

*// Связи*

\- candidate(): BelongsTo Candidate

\- interviewer(): BelongsTo User

**7. HiringDecision (Решение о приеме)**

php

\- id

\- candidate\_id

\- position\_title          *// Должность*

\- specialty\_id            *// Специальность*

\- employment\_type         *// temporary/permanent*

\- payment\_type           *// rate/salary/combined*

\- payment\_value          *// Значение оплаты*

\- start\_date

\- end\_date               *// Для temporary*

\- decision\_makers        *// JSON: кто принимал решение [user\_ids]*

\- approved\_by\_id         *// Кто утвердил окончательно*

\- status                *// draft/approved/rejected*

\- trainee\_period\_days    *// Если испытательный срок*

\- created\_at, updated\_at

*// Связи*

\- candidate(): BelongsTo Candidate

\- specialty(): BelongsTo Specialty

\- approvedBy(): BelongsTo User

**8. PositionChangeRequest (Запрос на изменение)**

php

\- id

\- user\_id                 *// Сотрудник*

\- current\_position

\- new\_position

\- current\_payment\_type

\- new\_payment\_type

\- current\_payment\_value

\- new\_payment\_value

\- reason

\- requested\_by\_id         *// Кто запросил*

\- approved\_by\_id          *// Кто утвердил*

\- status                 *// pending/approved/rejected*

\- effective\_date

\- notification\_users     *// JSON: кого уведомить [user\_ids]*

\- created\_at, updated\_at

*// Связи*

\- user(): BelongsTo User

\- requestedBy(): BelongsTo User

\- approvedBy(): BelongsTo User

**🔄 Workflow системы**

**Этап 1: Подготовка**

text

1\. HR создает вакансии (Vacancy)

2\. Заявители создают заявки на подбор (RecruitmentRequest)

3\. Главный HR распределяет заявки среди HR (назначает hr\_responsible\_id)

**Этап 2: Поиск и первичный отбор**

text

4\. Ответственный HR ведет таблицу кандидатов (Candidate):

`   `- Добавляет кандидатов в заявку

`   `- Указывает заявителя (expert\_id)

`   `- Статус: "new" → "contacted"

5\. HR отправляет карточки кандидатов заявителям:

`   `- Статус кандидата меняется на "sent\_for\_approval"

`   `- Создается запись в CandidateStatusHistory

**Этап 3: Решение заявителя**

text

6\. Заявитель принимает решение (CandidateDecision):

`   `- "reject" → статус "rejected\_by\_expert"

`   `- "reserve" → статус "in\_reserve" 

`   `- "interview" → статус "approved\_for\_interview"

`   `- "other\_vacancy" → статус "suggested\_other\_vacancy"

7\. Результат фиксируется в Candidate и CandidateStatusHistory

**Этап 4: Организация собеседования**

text

8\. HR на основании решения "interview":

`   `- Создает Interview

`   `- Согласовывает дату/время с заявителем

`   `- Назначает собеседующего (interviewer\_id)

9\. Проводится собеседование:

`   `- Собеседующий вносит результат

`   `- Статус Interview: "completed"

**Этап 5: Решение по результатам собеседования**

text

10\. По результатам Interview фиксируется решение:

`    `- "hire" → создается HiringDecision

`    `- "reject" → статус кандидата "rejected\_after\_interview"

`    `- "reserve" → статус "in\_reserve\_after\_interview"

`    `- "other\_vacancy" → статус "suggested\_other\_vacancy\_after\_interview"

`    `- "trainee" → создается TraineeRequest (интеграция с существующей системой)

**Этап 6: Оформление трудоустройства**

text

11\. Для прямого найма:

`    `- Утверждается HiringDecision

`    `- Создается пользователь в системе

`    `- Назначаются должность и оплата

12\. Для стажировки:

`    `- Проходит стандартный workflow TraineeRequest

`    `- После стажировки - решение о приеме через HiringDecision

**Этап 7: Изменения после трудоустройства**

text

13\. Любые изменения должности/оплаты:

`    `- Создается PositionChangeRequest

`    `- Утверждается назначенным пользователем

`    `- Уведомляются указанные пользователи

**👥 Типы сотрудников**

**Временные сотрудники (temporary)**

- Имеют дату окончания работы (end\_date)
- Могут быть переведены в постоянные через PositionChangeRequest
- Учитываются в отчетах отдельно

**Постоянные сотрудники (permanent)**

- Не имеют даты окончания работы
- Основной штат компании
- Изменения только через PositionChangeRequest

**🚀 План реализации**

**Этап 1: Базовая структура (Неделя 1)**

text

\- [ ] Vacancy + вспомогательные таблицы (Tasks, Requirements, Conditions)

\- [ ] RecruitmentRequest с распределением HR

\- [ ] Candidate с привязкой к заявителю

\- [ ] CandidateStatusHistory для полного аудита

**Этап 2: Workflow решений (Неделя 2)**

text

\- [ ] CandidateDecision для решений заявителей

\- [ ] Interview с назначением собеседующих

\- [ ] HiringDecision для фиксации условий трудоустройства

\- [ ] Интеграция с существующей TraineeRequest

**Этап 3: Управление изменениями (Неделя 3)**

text

\- [ ] PositionChangeRequest для изменений должностей/оплат

\- [ ] Система уведомлений для изменений

\- [ ] Разделение temporary/permanent

**Этап 4: Filament Resources (Неделя 4)**

text

\- [ ] RecruitmentRequestResource с workflow

\- [ ] CandidateResource с историей решений

\- [ ] InterviewResource с назначением собеседующих

\- [ ] PositionChangeRequestResource

**Этап 5: Интеграция и тестирование (Неделя 5)**

text

\- [ ] Полная интеграция с существующей системой

\- [ ] Тестирование полного workflow

\- [ ] Настройка прав доступа для всех ролей

**🎯 Ключевые особенности реализации**

**1. Полная трассируемость**

- Каждое изменение статуса фиксируется в CandidateStatusHistory
- Указывается кто и когда принял решение
- Сохраняется предыдущее состояние

**2. Четкое распределение ответственности**

- HR отвечают за процесс подбора
- Заявители принимают решения по кандидатам
- Собеседующие проводят оценки
- Утверждающие фиксируют условия трудоустройства

**3. Интеграция с существующей системой**

- TraineeRequest создается автоматически при решении "trainee"
- Пользователи создаются из утвержденных HiringDecision
- Назначения (Assignment) учитывают тип сотрудника

**4. Гибкость workflow**

- Возможность возврата на предыдущие этапы
- Резервирование кандидатов для будущих вакансий
- Предложение альтернативных вакансий

