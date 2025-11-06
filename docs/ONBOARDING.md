# 👋 Добро пожаловать в проект!

## 🚀 Быстрый старт

### 1. Настройка окружения
```bash
# Клонируем репозиторий
git clone git@github.com:personal-app-team/personal-app.git
cd personal-app

# Настраиваем окружение (Docker)
sail up -d
sail composer install
sail npm install
sail artisan key:generate
sail artisan migrate
```
### 2. Первый запуск
```bash
# Запускаем сервер разработки
sail artisan serve
# Или через Docker
sail up
```

### 3. Начало работы над задачей
```bash
# Обновляем основную ветку
git checkout main
git pull origin main

# Создаем feature ветку
git checkout -b feature/краткое-описание

# Примеры именования веток:
# feature/user-authentication
# fix/login-bug
# docs/update-readme
```

## 📋 Процесс разработки

### Рабочий процесс:

1. **Создайте ветку** от актуального main

2. Разрабатывайте и коммитьте изменения

3. Создайте Pull Request когда задача готова

4. Ждите code review от @Nick-Major

5. Исправьте замечания если есть

6. После approval - PR будет смержен

### Правила коммитов:
```bash
# Хорошие примеры:
git commit -m "feat: add user authentication"
git commit -m "fix: resolve login page issue"
git commit -m "docs: update installation guide"

# Плохие примеры:
git commit -m "changes"
git commit -m "update"
git commit -m "fix bug"
```

## 🛡️ Важные правила

### Безопасность:

- ❌ **НЕЛЬЗЯ** пушить напрямую в main

* ❌ НЕЛЬЗЯ мержить свои PR без approval

* ✅ МОЖНО создавать ветки и PR

* ✅ МОЖНО пушить в свои ветки

### Code Review:

- Все PR требуют минимум 1 approval

* Комментарии в PR должны быть разрешены перед мержем

* Новые коммиты сбрасывают существующие approvals

## 🆘 Если что-то пошло не так

### Частые проблемы и решения:

**Конфликты с main веткой:**
```bash
git checkout your-feature-branch
git fetch origin
git merge origin/main
# Решите конфликты, затем
git push origin your-feature-branch
```
**Забыли что-то в коммите:**
```bash
git add forgotten-file.php
git commit --amend
git push --force-with-lease origin your-feature-branch
```

## 📞 Контакты и помощь

- **Технические вопросы**: @Nick-Major

* Экстренные случаи: Telegram/Email

- **Документация**: 
  - [Collaboration Guide](./COLLABORATION_GUIDE.md)
  - [Backup & Recovery](./BACKUP_HISTORY.md)

## 🔗 Полезные ссылки

- **Основной репозиторий**: https://github.com/personal-app-team/personal-app

* Laravel Documentation: https://laravel.com/docs

* Filament Documentation: https://filamentphp.com/docs