#!/bin/bash

echo "🔍 Анализ соответствия моделей и Filament Resources..."
echo ""

# Список моделей
MODELS_PATH="app/Models"
RESOURCES_PATH="app/Filament/Resources"

echo "📊 Моделей в системе:"
ls -1 $MODELS_PATH/*.php | wc -l

echo ""
echo "📊 Filament Resources:"
ls -1 $RESOURCES_PATH/*Resource.php | wc -l

echo ""
echo "📋 Соответствие моделей и ресурсов:"

# Идем по всем моделям
for model_file in $MODELS_PATH/*.php; do
    model_name=$(basename $model_file .php)
    resource_file="$RESOURCES_PATH/${model_name}Resource.php"
    
    if [[ -f "$resource_file" ]]; then
        echo "✅ $model_name -> ${model_name}Resource"
    else
        echo "❌ $model_name -> НЕТ РЕСУРСА"
    fi
done

echo ""
echo "📋 Ресурсы без моделей:"
for resource_file in $RESOURCES_PATH/*Resource.php; do
    resource_name=$(basename $resource_file Resource.php)
    model_file="$MODELS_PATH/${resource_name}.php"
    
    if [[ ! -f "$model_file" ]]; then
        echo "⚠️  $resource_file -> НЕТ МОДЕЛИ"
    fi
done
