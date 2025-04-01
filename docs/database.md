# Database Schema Documentation - YouCode Evaluator

This document provides detailed information about the database schema used in the YouCode Evaluator platform.

## Overview

The YouCode Evaluator platform uses PostgreSQL as its database management system. The database schema consists of several tables that store information about users, roles, evaluations, problems, submissions, and results.

## Tables

### Roles

The `roles` table stores information about user roles in the system.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | varchar(255) | Role name (candidate, instructor, administrator) |
| description | text | Role description |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Last update timestamp |

### Users

The `users` table stores information about users of the platform.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | varchar(255) | User's full name |
| email | varchar(255) | User's email address (unique) |
| email_verified_at | timestamp | Email verification timestamp |
| password | varchar(255) | Hashed password |
| role_id | bigint | Foreign key to roles table |
| total_score | integer | User's total score across all evaluations |
| remember_token | varchar(100) | Remember me token |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Last update timestamp |

### Evaluations

The `evaluations` table stores information about programming evaluations.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | varchar(255) | Evaluation name |
| description | text | Evaluation description |
| language | varchar(255) | Programming language (C, JavaScript, PHP) |
| is_active | boolean | Whether the evaluation is active |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Last update timestamp |

### Problems

The `problems` table stores information about programming problems within evaluations.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| evaluation_id | bigint | Foreign key to evaluations table |
| title | varchar(255) | Problem title |
| description | text | Problem description |
| example_input | text | Example input for the problem |
| example_output | text | Expected output for the example input |
| difficulty | varchar(255) | Problem difficulty (easy, medium, hard) |
| time_limit | integer | Time limit in milliseconds |
| memory_limit | integer | Memory limit in MB |
| test_cases | json | JSON array of test cases |
| is_active | boolean | Whether the problem is active |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Last update timestamp |

### Submissions

The `submissions` table stores information about code submissions from users.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | Foreign key to users table |
| problem_id | bigint | Foreign key to problems table |
| code | text | Submitted code |
| language | varchar(255) | Programming language of the submission |
| status | varchar(255) | Submission status (pending, running, completed, failed) |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Last update timestamp |

### Results

The `results` table stores information about the results of code submissions.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| submission_id | bigint | Foreign key to submissions table |
| success | boolean | Whether the submission passed all test cases |
| execution_time | integer | Execution time in milliseconds |
| memory_usage | integer | Memory usage in KB |
| output | text | Output of the code execution |
| error_message | text | Error message if execution failed |
| test_results | json | JSON array of test case results |
| score | integer | Score for the submission |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Last update timestamp |

## Relationships

### Users and Roles

- Each user belongs to one role
- Each role can have many users

```
users.role_id -> roles.id
```

### Evaluations and Problems

- Each evaluation can have many problems
- Each problem belongs to one evaluation

```
problems.evaluation_id -> evaluations.id
```

### Problems and Submissions

- Each problem can have many submissions
- Each submission belongs to one problem

```
submissions.problem_id -> problems.id
```

### Users and Submissions

- Each user can have many submissions
- Each submission belongs to one user

```
submissions.user_id -> users.id
```

### Submissions and Results

- Each submission can have one result
- Each result belongs to one submission

```
results.submission_id -> submissions.id
```

## Indexes

The following indexes are created to optimize query performance:

- `users_email_unique`: Unique index on `users.email`
- `users_role_id_index`: Index on `users.role_id`
- `problems_evaluation_id_index`: Index on `problems.evaluation_id`
- `submissions_user_id_index`: Index on `submissions.user_id`
- `submissions_problem_id_index`: Index on `submissions.problem_id`
- `results_submission_id_unique`: Unique index on `results.submission_id`

## Migrations

Database migrations are managed using Laravel's migration system. The migrations are executed in the following order:

1. Create roles table
2. Create users table (with foreign key to roles)
3. Create evaluations table
4. Create problems table (with foreign key to evaluations)
5. Create submissions table (with foreign keys to users and problems)
6. Create results table (with foreign key to submissions)

## Data Types

### JSON Fields

The following fields use JSON data type:

- `problems.test_cases`: Array of test cases with input and expected output
  ```json
  [
    {
      "input": "test input 1",
      "output": "expected output 1"
    },
    {
      "input": "test input 2",
      "output": "expected output 2"
    }
  ]
  ```

- `results.test_results`: Array of test case results
  ```json
  [
    {
      "test_case": 1,
      "success": true,
      "execution_time": 5,
      "memory_usage": 1024,
      "output": "actual output 1"
    },
    {
      "test_case": 2,
      "success": false,
      "execution_time": 10,
      "memory_usage": 2048,
      "output": "actual output 2",
      "error_message": "Output does not match expected output"
    }
  ]
  ```

## Database Seeding

The database is seeded with initial data for roles:

- `candidate`: Applicant to YouCode training
- `instructor`: Teaching staff for evaluation
- `administrator`: Platform management personnel

## Database Diagram

```
+-------+     +-------+     +-------------+     +---------+     +-------------+     +---------+
| roles | <-- | users | --> | submissions | <-- | results |     | evaluations | <-- | problems |
+-------+     +-------+     +-------------+     +---------+     +-------------+     +---------+
                                  ^                                                      |
                                  |                                                      |
                                  +------------------------------------------------------+
```

## Query Examples

### Get User with Role

```sql
SELECT u.*, r.name as role_name
FROM users u
JOIN roles r ON u.role_id = r.id
WHERE u.id = 1;
```

### Get Problems for an Evaluation

```sql
SELECT *
FROM problems
WHERE evaluation_id = 1
ORDER BY difficulty, id;
```

### Get Submissions for a User

```sql
SELECT s.*, p.title as problem_title
FROM submissions s
JOIN problems p ON s.problem_id = p.id
WHERE s.user_id = 1
ORDER BY s.created_at DESC;
```

### Get Results for a Submission

```sql
SELECT *
FROM results
WHERE submission_id = 1;
```

### Get Top Performers for an Evaluation

```sql
SELECT u.id, u.name, COUNT(DISTINCT s.problem_id) as problems_solved
FROM users u
JOIN submissions s ON u.id = s.user_id
JOIN problems p ON s.problem_id = p.id
JOIN results r ON s.id = r.submission_id
WHERE p.evaluation_id = 1
AND r.success = true
GROUP BY u.id, u.name
ORDER BY problems_solved DESC, u.id
LIMIT 10;
```

## Performance Considerations

- The database schema is designed to optimize common queries such as retrieving user submissions, problem details, and evaluation results.
- Indexes are created on foreign keys and frequently queried columns to improve query performance.
- JSON fields are used for flexible data structures like test cases and test results, while maintaining query capabilities.
- The database is normalized to reduce data redundancy and maintain data integrity.
