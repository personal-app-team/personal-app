# 💰 Shift - Логика расчетов оплаты

## 🎯 Формула расчета смены

СУММА_НА_РУКИ = (Базовая_ставка × Часы) + Компенсация + Операционные_расходы

НАЛОГ = СУММА_НА_РУКИ × Ставка_налога

К_ВЫПЛАТЕ = СУММА_НА_РУКИ + НАЛОГ

## 🔧 Методы расчета в Shift модели

### Определение базовой ставки (`determineBaseRate()`)
```php
public function determineBaseRate()
{
    // 1. Наш персонал - из user_specialties pivot
    if ($this->user_id && $this->specialty_id) {
        $userSpecialty = $this->user->specialties()
            ->where('specialty_id', $this->specialty_id)
            ->first();
        return $userSpecialty->pivot->base_hourly_rate 
            ?? $userSpecialty->base_hourly_rate 
            ?? 0;
    }
    
    // 2. Персонализированный персонал подрядчика
    if ($this->user_id && $this->user->contractor_id && $this->specialty_id) {
        $contractorRate = ContractorRate::where('contractor_id', $this->user->contractor_id)
            ->where('specialty_id', $this->specialty_id)
            ->where('is_anonymous', false)
            ->where('is_active', true)
            ->first();
        return $contractorRate?->hourly_rate ?? 0;
    }
    
    return 0;
}
```

## Расчет суммы на руки (calculateHandAmount())
```php
public function calculateHandAmount()
{
    $hours = $this->worked_minutes / 60;
    $baseRate = $this->base_rate ?: $this->determineBaseRate();
    $baseAmount = $baseRate * $hours;
    $compensation = $this->compensation_amount ?? 0;
    $expenses = $this->shiftExpenses->sum('amount');
    
    return $baseAmount + $compensation + $expenses;
}
```

## Расчет налога (calculateTaxAmount())
```php
public function calculateTaxAmount()
{
    $handAmount = $this->hand_amount ?: $this->calculateHandAmount();
    $taxRate = $this->taxStatus?->tax_rate ?? 0;
    return $handAmount * $taxRate;
}
```

## Расчет к выплате (calculatePayoutAmount())
```php
public function calculatePayoutAmount()
{
    $handAmount = $this->hand_amount ?: $this->calculateHandAmount();
    $taxAmount = $this->calculateTaxAmount();
    return $handAmount + $taxAmount;
}
```

## 📊 Поля для расчетов

Основные поля:

* base_rate - базовая ставка (руб/час)

* worked_minutes - отработанное время (минуты)

* compensation_amount - компенсация без чека

* shiftExpenses - операционные расходы (чеки)

## Налоговая система:

* tax_status_id → TaxStatus (налоговый статус)

* taxStatus.tax_rate - ставка налога (например 0.13 для 13%)

## Результаты расчетов:

* hand_amount - сумма на руки (до налога)

* tax_amount - сумма налога

* payout_amount - сумма к выплате (с налогом)

## 🔄 Workflow расчета

1. Создание смены → заполняются specialty_id, user_id, contractor_id

2. Определение ставки → determineBaseRate() находит актуальную ставку

3. Заполнение времени → worked_minutes (авто или ручной ввод)

4. Добавление расходов → компенсации и операционные расходы

5. Авторасчет → updateCalculations() обновляет все суммы

6. Подтверждение → статус меняется на completed

## 🎯 Приоритеты ставок

1. Индивидуальная ставка пользователя (user_specialties.base_hourly_rate)

2. Базовая ставка специальности (specialties.base_hourly_rate)

3. Ставка подрядчика (contractor_rates.hourly_rate)
