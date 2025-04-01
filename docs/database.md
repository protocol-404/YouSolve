# Database Schema Documentation for YouCode Evaluator

This document provides detailed information about the database schema used in the YouCode Evaluator platform.

## Overview

The YouCode Evaluator database consists of several interconnected tables that store information about users, roles, evaluations, problems, submissions, and results. The database is designed to support the evaluation of programming skills in C, JavaScript, and PHP.

## Entity Relationship Diagram

```
+----------------+       +----------------+       +----------------+
|     roles      |       |     users      |       |  evaluations   |
+----------------+       +----------------+       +----------------+
| id             |<----->| id             |       | id             |
| name           |       | name           |       | name           |
| description    |       | email          |       | description    |
| timestamps     |       | password       |       | language       |
+----------------+       | role_id        |       | is_active      |
                         | timestamps     |       | timestamps     |
                         +----------------+       +----------------+
                                |                        |
                                |                        |
                                v                        v
                         +----------------+       +----------------+
                         |  submissions   |       |    problems    |
                         +----------------+       +----------------+
                         | id             |<----->| id             |
                         | user_id        |       | evaluation_id  |
                         | problem_id     |       | title          |
                         | code           |       | description    |
                         | language       |       | example_input  |
                         | status         |       | example_output |
                         | timestamps     |       | constraints    |
                         +----------------+       | difficulty     |
                                |                 | time_limit     |
                                |                 | memory_limit   |
                                v                 | test_cases     |
                         +----------------+       | is_active      |
                         |    results     |       | timestamps     |
                         +----------------+       +----------------+
                         | id             |
                         | submission_id  |
                         | success        |
                         | execution_time |
                         | memory_usage   |
                         | output         |
                         | error_message  |
                         | test_results   |
                         | timestamps     |
                         +----------------+
```

## Tables

### roles

Stores the different user roles in the system.

| Column      | Type         | Description                           |
|-------------|--------------|---------------------------------------|
| id          | bigint       | Primary key                           |
| name        | varchar(255) | Role name (unique)                    |
| description | varchar(255) | Role description                      |
| created_at  | timestamp    | Creation timestamp                    |
| updated_at  | timestamp    | Last update timestamp                 |

### users

Stores user information and authentication details.

| Column           | Type         | Description                           |
|------------------|--------------|---------------------------------------|
| id               | bigint       | Primary key                           |
| name             | varchar(255) | User's full name                      |
| email            | varchar(255) | User's email address (unique)         |
| email_verified_at| timestamp    | When email was verified (nullable)    |
| password         | varchar(255) | Hashed password                       |
| role_id          | bigint       | Foreign key to roles table            |
| remember_token   | varchar(100) | Token for "remember me" functionality |
| created_at       | timestamp    | Creation timestamp                    |
| updated_at       | timestamp    | Last update timestamp                 |

### evaluations

Stores information about programming evaluations.

| Column      | Type         | Description                           |
|-------------|--------------|---------------------------------------|
| id          | bigint       | Primary key                           |
| name        | varchar(255) | Evaluation name                       |
| description | text         | Evaluation description (nullable)     |
| language    | enum         | Programming language (C, JavaScript, PHP) |
| is_active   | boolean      | Whether evaluation is active          |
| created_at  | timestamp    | Creation timestamp                    |
| updated_at  | timestamp    | Last update timestamp                 |

### problems

Stores programming problems for evaluations.

| Column        | Type         | Description                           |
|---------------|--------------|---------------------------------------|
| id            | bigint       | Primary key                           |
| evaluation_id | bigint       | Foreign key to evaluations table      |
| title         | varchar(255) | Problem title                         |
| description   | text         | Problem description                   |
| example_input | text         | Example input (nullable)              |
| example_output| text         | Example output (nullable)             |
| constraints   | text         | Problem constraints (nullable)        |
| difficulty    | enum         | Problem difficulty (easy, medium, hard) |
| time_limit    | integer      | Time limit in milliseconds (default: 1000) |
| memory_limit  | integer      | Memory limit in MB (default: 128)     |
| test_cases    | text         | JSON encoded test cases (nullable)    |
| is_active     | boolean      | Whether problem is active             |
| created_at    | timestamp    | Creation timestamp                    |
| updated_at    | timestamp    | Last update timestamp                 |

### submissions

Stores user code submissions for problems.

| Column      | Type         | Description                           |
|-------------|--------------|---------------------------------------|
| id          | bigint       | Primary key                           |
| user_id     | bigint       | Foreign key to users table            |
| problem_id  | bigint       | Foreign key to problems table         |
| code        | text         | Submitted code                        |
| language    | enum         | Programming language (C, JavaScript, PHP) |
| status      | enum         | Submission status (pending, running, completed, failed) |
| created_at  | timestamp    | Creation timestamp                    |
| updated_at  | timestamp    | Last update timestamp                 |

### results

Stores evaluation results for submissions.

| Column         | Type         | Description                           |
|----------------|--------------|---------------------------------------|
| id             | bigint       | Primary key                           |
| submission_id  | bigint       | Foreign key to submissions table      |
| success        | boolean      | Whether submission passed all tests   |
| execution_time | integer      | Execution time in milliseconds (nullable) |
| memory_usage   | integer      | Memory usage in KB (nullable)         |
| output         | text         | Program output (nullable)             |
| error_message  | text         | Error message if any (nullable)       |
| test_results   | json         | JSON encoded test results (nullable)  |
| created_at     | timestamp    | Creation timestamp                    |
| updated_at     | timestamp    | Last update timestamp                 |

## Relationships

### One-to-Many Relationships

- **Role to Users**: One role can be assigned to many users.
- **Evaluation to Problems**: One evaluation can have many problems.
- **User to Submissions**: One user can make many submissions.
- **Problem to Submissions**: One problem can have many submissions.
- **Submission to Result**: One submission has one result.

### Foreign Key Constraints

- `users.role_id` references `roles.id` (on delete: restrict)
- `problems.evaluation_id` references `evaluations.id` (on delete: cascade)
- `submissions.user_id` references `users.id` (on delete: cascade)
- `submissions.problem_id` references `problems.id` (on delete: cascade)
- `results.submission_id` references `submissions.id` (on delete: cascade)

## Indexes

- `roles.name` (unique)
- `users.email` (unique)
- `users.role_id` (index)
- `problems.evaluation_id` (index)
- `submissions.user_id` (index)
- `submissions.problem_id` (index)
- `results.submission_id` (index)

## Migrations

The database schema is created and maintained using Laravel migrations. The migration files are located in the `database/migrations` directory and are executed in the following order:

1. `2025_04_01_210843_create_roles_table.php`
2. `2025_04_01_210843_create_evaluations_table.php`
3. `2025_04_01_210843_create_problems_table.php`
4. `2025_04_01_210845_create_users_table.php`
5. `2025_04_01_210846_create_submissions_table.php`
6. `2025_04_01_210847_create_results_table.php`

## Data Seeding

Initial data for the roles table is provided through the `RoleSeeder` class, which creates the following roles:

1. `candidate`: Applicants to YouCode training
2. `instructor`: Teaching staff for evaluation
3. `administrator`: Platform management personnel
