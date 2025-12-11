#!/bin/bash

echo "🔍 Проверка проблемных ресурсов..."

resources=(
  "AssignmentResource"
  "ContractorWorkerResource" 
  "ExpenseResource"
  "MassPersonnelReportResource"
  "PhotoResource"
  "TraineeRequestResource"
  "UserResource"
  "WorkRequestStatusResource"
)

for resource in "${resources[@]}"; do
  echo -e "\n=== $resource ==="
  
  # Проверяем наличие файла
  if [ -f "app/Filament/Resources/$resource/$resource.php" ]; then
    echo "✅ Файл существует"
    
    # Проверяем навигационные свойства
    echo "📋 Навигационные свойства:"
    grep -E "navigationIcon|navigationGroup|navigationLabel|navigationSort" "app/Filament/Resources/$resource/$resource.php" | head -5
    
    # Проверяем canAccess
    echo "🔐 Метод canAccess:"
    grep -A10 "canAccess" "app/Filament/Resources/$resource/$resource.php" | head -15
    
    # Проверяем shouldRegisterNavigation
    echo "📍 Метод shouldRegisterNavigation:"
    grep -A5 "shouldRegisterNavigation" "app/Filament/Resources/$resource/$resource.php"
    
  else
    echo "❌ Файл не найден!"
  fi
done
