### ✅ **Исправление структуры после анализа (16.12.2025)**

**Обнаружено:**
1. В таблице `work_requests` нет поля `specialty_id` - это правильно
2. Заявки на работы определяют только категорию, конкретная специальность определяется на уровне смены
3. Связь `Specialty->workRequests()` некорректна и должна быть удалена

**Выполнено:**
- [x] Удалена связь `workRequests()` из модели `Specialty`
- [x] Убран столбец `work_requests_count` из `SpecialtyResource`
- [ ] Обновлен метод `determineBaseRate()` в `Shift` (убрать устаревшую логику для подрядчиков без `contractor_rate_id`)

**Требуется проверить:**
1. Как создаются смены для наших исполнителей - откуда берется `specialty_id`?
2. Все ли смены для подрядчиков имеют `contractor_rate_id`?
3. Корректно ли работает создание `MassPersonnelReport` для массового персонала?

**Обновленный workflow:**

1. **Заявка на работы:**
   - Выбирается `category_id` (категория работ)
   - Выбирается `work_type_id` (тип работ)
   - Указывается `address_id`, `project_id`, `purpose_id`

2. **Создание смены (наши исполнители):**
   - Берется `specialty_id` из назначенного исполнителя (user_specialties)
   - Ставка определяется через `user_specialties.pivot.base_hourly_rate`

3. **Создание смены (подрядчики):**
   - Берется `contractor_rate_id` (ставка подрядчика для категории)
   - Ставка определяется через `contractor_rates.hourly_rate`

4. **Создание отчета (массовый персонал):**
   - Берется `contractor_rate_id` с `rate_type='mass'`
   - Ставка определяется через `contractor_rates.hourly_rate`

### ✅ **Исправления завершены (16.12.2025)**

**Выполнено:**
- [x] Исправлен метод `determineBaseRate()` в модели `Shift`:
  - Для наших исполнителей: через `user_specialties`
  - Для подрядчиков: через `contractor_rate_id` (только персонализированные)
  - Убрана устаревшая логика поиска ставок по `specialty_id`
  
- [x] Обновлен метод `calculateTotalAmount()` в `MassPersonnelReport`:
  - Автоматически определяет `base_hourly_rate` через `determineBaseRate()`
  - Корректно рассчитывает итоговую сумму
  
- [x] Удалено избыточное поле `is_anonymous` из системы:
  - Создана миграция для удаления поля из таблицы `contractor_rates`
  - Обновлена модель `ContractorRate` (убрано из `$fillable` и `$casts`)
  - Обновлен `ContractorRateResource` (убрано из формы, таблицы и фильтров)
  
- [x] Обновлена документация `WORKREQUEST.md` с новой структурой

**Тестовые сценарии:**
1. ✅ Создание массовой ставки (`rate_type='mass'`) → используется в `MassPersonnelReport`
2. ✅ Создание персонализированной ставки (`rate_type='personalized'`) → используется в `Shift`
3. ✅ Создание смены для нашего исполнителя → ставка из `user_specialties`
4. ✅ Создание смены для подрядчика → ставка из `contractor_rates` (персонализированная)
5. ✅ Создание отчета по массовому персоналу → ставка из `contractor_rates` (массовая)

**Проверка целостности:**
- [ ] Все смены (`Shift`) должны иметь либо `specialty_id` (наши исполнители), либо `contractor_rate_id` (подрядчики)
- [ ] Все отчеты (`MassPersonnelReport`) должны иметь `contractor_rate_id` с `rate_type='mass'`
- [ ] В таблице `work_requests` нет `specialty_id` - это правильно
- [ ] Связь `Specialty->workRequests()` удалена - это правильно