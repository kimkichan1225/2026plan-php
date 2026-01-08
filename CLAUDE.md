# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A PHP 8 goal management system for New Year resolutions with automatic monthly plan decomposition and progress tracking. The system follows a "Plan → Execute → Reflect" workflow.

**Live Deployment**: https://new-year-plan.up.railway.app/login.php
**Test Account**: `test1@test.com` / `test1234`

## Development Commands

### Local Development
```bash
# Start PHP built-in server (default development server)
php -S localhost:8000

# Access application
# http://localhost:8000/login.php
```

### Database Setup
```bash
# Create database and schema (local MySQL)
mysql -u root -p < database/schema.sql

# For Railway deployment, use Railway CLI:
railway run mysql -h $DB_HOST -u $DB_USER -p$DB_PASS < database/schema.sql
```

### Deployment
The project is deployed on Railway with automatic GitHub deployments. Database configuration uses environment variables (see `config/database.php`).

## Architecture

### Core Business Logic Flow

**Goal Creation → Automatic Plan Generation → Progress Tracking → Reflection**

1. When a goal is created, the system **automatically generates 12 monthly plans** (one for each month)
2. Plans are grouped into quarters (Q1-Q4) with `quarter = ceil(month / 3)`
3. Progress is calculated as: `(completed_plans / 12) * 100`
4. Goal status is auto-determined: `not_started` (0%) → `in_progress` (1-99%) → `completed` (100%)

### Key Design Patterns

**Server-Side Progress Calculation**: Progress is calculated server-side in `Goal::updateProgress()` based on completed plan count, NOT by user input. This ensures data integrity.

**Database Triggers**: `tr_update_progress_after_plan_update` trigger automatically recalculates goal progress when any plan's `is_completed` status changes.

**Transaction Safety**: Goal creation with plan generation uses database transactions (`beginTransaction()` → `commit()`) to ensure atomicity.

### Model Layer

- **User.php**: Authentication with `password_hash()` / `password_verify()`, user CRUD
- **Goal.php**:
  - `create()`: Creates goal + automatically generates 12 monthly plans in transaction
  - `updateProgress()`: Recalculates progress from completed plans
  - `findWithPlans()`: Returns goal with plans grouped by quarter
- **GoalPlan.php**: Monthly plan CRUD, completion toggling, quarter-based queries
- **Reflection.php**: Monthly reflection system (can be linked to specific goals or general)

### Database Schema

**Critical Relationships**:
```
users (1) ──→ (N) goals (1) ──→ (N) goal_plans
users (1) ──→ (N) reflections ──→ (0..1) goals
```

**Important Constraints**:
- `goal_plans` has `UNIQUE KEY (goal_id, month)` to prevent duplicate monthly plans
- Always 12 plans per goal (months 1-12)
- Foreign keys use `CASCADE` for goals/plans, `SET NULL` for reflections

**Views**:
- `v_goal_progress`: Aggregated goal progress statistics
- `v_user_dashboard`: User-level summary statistics

## File Structure

```
php-project/
├── config/
│   └── database.php           # PDO connection, environment variable handling
├── models/                    # Business logic layer
│   ├── User.php
│   ├── Goal.php               # Core: auto plan generation + progress calculation
│   ├── GoalPlan.php
│   └── Reflection.php
├── includes/
│   ├── session.php            # Session management, loginUser(), requireLogin()
│   └── functions.php          # Common utilities
├── *.php                      # View files (login, dashboard, goal_list, etc.)
├── database/
│   ├── schema.sql             # Full schema with triggers, views, procedures
│   └── schema_simple.sql
└── assets/                    # CSS/JS
```

## Security Implementation

- **SQL Injection Prevention**: PDO prepared statements with bound parameters throughout
- **Password Security**: `password_hash(PASSWORD_DEFAULT)` + `password_verify()`
- **XSS Prevention**: Use `htmlspecialchars()` when outputting user data
- **Session Security**: `session_regenerate_id(true)` on login to prevent session fixation
- **Authentication**: Session-based with `requireLogin()` guard function

## Important Implementation Notes

### When Creating Goals
Always use `Goal::create()` which handles both goal insertion and automatic monthly plan generation. Never insert into `goals` table directly without creating corresponding plans.

### When Updating Plan Completion
After calling `GoalPlan::update()` to change `is_completed`, you must call `Goal::updateProgress($goalId)` to recalculate progress (unless relying on the database trigger).

### Environment Variables
`config/database.php` supports both environment variables (production) and default values (local development):
```php
DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
```

### Category Enum Values
Valid goal categories: `career`, `health`, `study`, `finance`, `hobby`, `relationship`, `other`

### Status Enum Values
Valid goal statuses: `not_started`, `in_progress`, `completed`

## Testing Notes

Test account is pre-populated with sample goals showing different progress states:
- Goal 1: 25% progress (3/12 months completed)
- Goal 2: 16.67% progress (2/12 months completed)
- Goal 3: 0% progress (not started)

Use `test1@test.com` / `test1234` for testing the live deployment.

## Common Pitfalls to Avoid

1. Don't manually calculate progress percentage - use `Goal::updateProgress()`
2. Don't create goals without monthly plans - use `Goal::create()`
3. Don't forget PDO prepared statements for user input
4. Don't expose raw PHP errors - use try-catch blocks
5. Remember to call `requireLogin()` at the top of protected pages
