# API Documentation for YouCode Evaluator

This document provides detailed information about the API endpoints available in the YouCode Evaluator platform.

## Base URL

All API endpoints are relative to the base URL of your deployment:

```
https://youcode-evaluator-api.example.com/api
```

## Authentication

The API uses token-based authentication with Laravel Sanctum. To access protected endpoints, you need to include the authentication token in the request header:

```
Authorization: Bearer {your_token}
```

### Authentication Endpoints

#### Register a new user

```
POST /register
```

**Request Body:**
```json
{
  "name": "User Name",
  "email": "user@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role_id": 1
}
```

**Response:**
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "User Name",
    "email": "user@example.com",
    "role_id": 1
  },
  "token": "your_auth_token"
}
```

#### Login

```
POST /login
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "User Name",
    "email": "user@example.com",
    "role_id": 1
  },
  "token": "your_auth_token"
}
```

#### Logout

```
POST /logout
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Response:**
```json
{
  "message": "Successfully logged out"
}
```

#### Forgot Password

```
POST /forgot-password
```

**Request Body:**
```json
{
  "email": "user@example.com"
}
```

**Response:**
```json
{
  "message": "Password reset link sent to your email"
}
```

#### Reset Password

```
POST /reset-password
```

**Request Body:**
```json
{
  "token": "reset_token_from_email",
  "email": "user@example.com",
  "password": "new_password",
  "password_confirmation": "new_password"
}
```

**Response:**
```json
{
  "message": "Password has been reset"
}
```

## Authorization and Permissions

### Get Current User

```
GET /user
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Response:**
```json
{
  "id": 1,
  "name": "User Name",
  "email": "user@example.com",
  "role_id": 1
}
```

### Get Available Roles

```
GET /roles
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Response:**
```json
{
  "roles": [
    {
      "id": 1,
      "name": "candidate",
      "description": "Applicant to YouCode training"
    },
    {
      "id": 2,
      "name": "instructor",
      "description": "Teaching staff for evaluation"
    },
    {
      "id": 3,
      "name": "administrator",
      "description": "Platform management personnel"
    }
  ]
}
```

### Check User Role

```
GET /check-role/{role}
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Response:**
```json
{
  "has_role": true
}
```

### Get User Permissions

```
GET /permissions
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Response:**
```json
{
  "role": "administrator",
  "permissions": {
    "users": ["view", "create", "edit", "delete"],
    "roles": ["view", "create", "edit", "delete"],
    "evaluations": ["view", "create", "edit", "delete"],
    "problems": ["view", "create", "edit", "delete"],
    "submissions": ["view", "create", "edit", "delete"],
    "results": ["view", "create", "edit", "delete"]
  }
}
```

## User Management

### List Users (Admin/Instructor Only)

```
GET /users
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Query Parameters:**
- `role_id` (optional): Filter users by role ID

**Response:**
```json
{
  "users": [
    {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com",
      "role_id": 3,
      "role": {
        "id": 3,
        "name": "administrator",
        "description": "Platform management personnel"
      }
    },
    {
      "id": 2,
      "name": "Instructor User",
      "email": "instructor@example.com",
      "role_id": 2,
      "role": {
        "id": 2,
        "name": "instructor",
        "description": "Teaching staff for evaluation"
      }
    }
  ]
}
```

### Create User (Admin Only)

```
POST /users
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Request Body:**
```json
{
  "name": "New User",
  "email": "newuser@example.com",
  "password": "password123",
  "role_id": 1
}
```

**Response:**
```json
{
  "message": "User created successfully",
  "user": {
    "id": 3,
    "name": "New User",
    "email": "newuser@example.com",
    "role_id": 1
  }
}
```

### Get User Details

```
GET /users/{user_id}
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Response:**
```json
{
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "role_id": 3,
    "role": {
      "id": 3,
      "name": "administrator",
      "description": "Platform management personnel"
    }
  }
}
```

### Update User

```
PUT /users/{user_id}
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Request Body:**
```json
{
  "name": "Updated User Name",
  "email": "updated@example.com",
  "password": "newpassword123",
  "role_id": 2
}
```

**Response:**
```json
{
  "message": "User updated successfully",
  "user": {
    "id": 1,
    "name": "Updated User Name",
    "email": "updated@example.com",
    "role_id": 2
  }
}
```

### Delete User (Admin Only)

```
DELETE /users/{user_id}
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Response:**
```json
{
  "message": "User deleted successfully"
}
```

### Get User Progress

```
GET /users/{user_id}/progress
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Response:**
```json
{
  "user_id": 1,
  "total_submissions": 10,
  "successful_submissions": 8,
  "success_rate": 80,
  "submissions_by_language": {
    "C": 3,
    "JavaScript": 4,
    "PHP": 3
  },
  "submissions_by_difficulty": {
    "easy": 5,
    "medium": 3,
    "hard": 2
  },
  "recent_submissions": [
    {
      "id": 10,
      "problem_id": 5,
      "status": "completed",
      "created_at": "2025-04-01T12:00:00Z"
    }
  ]
}
```

### Get Current User Progress

```
GET /my-progress
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Response:** Same as `/users/{user_id}/progress`

## Evaluations

### List Evaluations

```
GET /evaluations
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Response:**
```json
{
  "evaluations": [
    {
      "id": 1,
      "name": "C Programming Basics",
      "description": "Basic C programming concepts and syntax",
      "language": "C",
      "is_active": true,
      "created_at": "2025-04-01T12:00:00Z",
      "updated_at": "2025-04-01T12:00:00Z"
    },
    {
      "id": 2,
      "name": "JavaScript Fundamentals",
      "description": "Core JavaScript concepts and DOM manipulation",
      "language": "JavaScript",
      "is_active": true,
      "created_at": "2025-04-01T12:00:00Z",
      "updated_at": "2025-04-01T12:00:00Z"
    }
  ]
}
```

### Get Evaluation Details

```
GET /evaluations/{evaluation_id}
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Response:**
```json
{
  "evaluation": {
    "id": 1,
    "name": "C Programming Basics",
    "description": "Basic C programming concepts and syntax",
    "language": "C",
    "is_active": true,
    "created_at": "2025-04-01T12:00:00Z",
    "updated_at": "2025-04-01T12:00:00Z"
  }
}
```

### Create Evaluation (Admin Only)

```
POST /evaluations
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Request Body:**
```json
{
  "name": "PHP Basics",
  "description": "Introduction to PHP programming",
  "language": "PHP",
  "is_active": true
}
```

**Response:**
```json
{
  "message": "Evaluation created successfully",
  "evaluation": {
    "id": 3,
    "name": "PHP Basics",
    "description": "Introduction to PHP programming",
    "language": "PHP",
    "is_active": true,
    "created_at": "2025-04-01T12:00:00Z",
    "updated_at": "2025-04-01T12:00:00Z"
  }
}
```

### Update Evaluation (Admin Only)

```
PUT /evaluations/{evaluation_id}
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Request Body:**
```json
{
  "name": "Updated PHP Basics",
  "description": "Updated introduction to PHP programming",
  "is_active": false
}
```

**Response:**
```json
{
  "message": "Evaluation updated successfully",
  "evaluation": {
    "id": 3,
    "name": "Updated PHP Basics",
    "description": "Updated introduction to PHP programming",
    "language": "PHP",
    "is_active": false,
    "created_at": "2025-04-01T12:00:00Z",
    "updated_at": "2025-04-01T12:30:00Z"
  }
}
```

### Delete Evaluation (Admin Only)

```
DELETE /evaluations/{evaluation_id}
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Response:**
```json
{
  "message": "Evaluation deleted successfully"
}
```

## Problems

### List Problems

```
GET /problems
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Query Parameters:**
- `evaluation_id` (optional): Filter problems by evaluation ID
- `difficulty` (optional): Filter problems by difficulty (easy, medium, hard)
- `is_active` (optional): Filter problems by active status

**Response:**
```json
{
  "problems": [
    {
      "id": 1,
      "evaluation_id": 1,
      "title": "Hello World",
      "description": "Write a function that returns 'Hello, World!'",
      "example_input": "None",
      "example_output": "Hello, World!",
      "difficulty": "easy",
      "time_limit": 1000,
      "memory_limit": 128,
      "is_active": true,
      "created_at": "2025-04-01T12:00:00Z",
      "updated_at": "2025-04-01T12:00:00Z"
    },
    {
      "id": 2,
      "evaluation_id": 1,
      "title": "Sum Two Numbers",
      "description": "Write a function that returns the sum of two numbers",
      "example_input": "2, 3",
      "example_output": "5",
      "difficulty": "easy",
      "time_limit": 1000,
      "memory_limit": 128,
      "is_active": true,
      "created_at": "2025-04-01T12:00:00Z",
      "updated_at": "2025-04-01T12:00:00Z"
    }
  ]
}
```

### Get Problem Details

```
GET /problems/{problem_id}
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Response:**
```json
{
  "problem": {
    "id": 1,
    "evaluation_id": 1,
    "title": "Hello World",
    "description": "Write a function that returns 'Hello, World!'",
    "example_input": "None",
    "example_output": "Hello, World!",
    "difficulty": "easy",
    "time_limit": 1000,
    "memory_limit": 128,
    "is_active": true,
    "created_at": "2025-04-01T12:00:00Z",
    "updated_at": "2025-04-01T12:00:00Z"
  }
}
```

### Create Problem (Admin Only)

```
POST /problems
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Request Body:**
```json
{
  "evaluation_id": 1,
  "title": "Factorial",
  "description": "Write a function that calculates the factorial of a number",
  "example_input": "5",
  "example_output": "120",
  "constraints": "0 <= n <= 12",
  "difficulty": "medium",
  "time_limit": 1000,
  "memory_limit": 128,
  "test_cases": "[{\"input\":\"0\",\"output\":\"1\"},{\"input\":\"5\",\"output\":\"120\"}]",
  "is_active": true
}
```

**Response:**
```json
{
  "message": "Problem created successfully",
  "problem": {
    "id": 3,
    "evaluation_id": 1,
    "title": "Factorial",
    "description": "Write a function that calculates the factorial of a number",
    "example_input": "5",
    "example_output": "120",
    "constraints": "0 <= n <= 12",
    "difficulty": "medium",
    "time_limit": 1000,
    "memory_limit": 128,
    "test_cases": "[{\"input\":\"0\",\"output\":\"1\"},{\"input\":\"5\",\"output\":\"120\"}]",
    "is_active": true,
    "created_at": "2025-04-01T12:00:00Z",
    "updated_at": "2025-04-01T12:00:00Z"
  }
}
```

### Update Problem (Admin Only)

```
PUT /problems/{problem_id}
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Request Body:**
```json
{
  "title": "Updated Factorial",
  "description": "Updated description for factorial problem",
  "difficulty": "hard",
  "is_active": false
}
```

**Response:**
```json
{
  "message": "Problem updated successfully",
  "problem": {
    "id": 3,
    "evaluation_id": 1,
    "title": "Updated Factorial",
    "description": "Updated description for factorial problem",
    "example_input": "5",
    "example_output": "120",
    "constraints": "0 <= n <= 12",
    "difficulty": "hard",
    "time_limit": 1000,
    "memory_limit": 128,
    "test_cases": "[{\"input\":\"0\",\"output\":\"1\"},{\"input\":\"5\",\"output\":\"120\"}]",
    "is_active": false,
    "created_at": "2025-04-01T12:00:00Z",
    "updated_at": "2025-04-01T12:30:00Z"
  }
}
```

### Delete Problem (Admin Only)

```
DELETE /problems/{problem_id}
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Response:**
```json
{
  "message": "Problem deleted successfully"
}
```

## Submissions

### List Submissions

```
GET /submissions
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Query Parameters:**
- `user_id` (optional): Filter submissions by user ID
- `problem_id` (optional): Filter submissions by problem ID
- `language` (optional): Filter submissions by language
- `status` (optional): Filter submissions by status
- `my_submissions` (optional): Set to `true` to get only current user's submissions

**Response:**
```json
{
  "submissions": [
    {
      "id": 1,
      "user_id": 1,
      "problem_id": 1,
      "code": "function helloWorld() { return 'Hello, World!'; }",
      "language": "JavaScript",
      "status": "completed",
      "created_at": "2025-04-01T12:00:00Z",
      "updated_at": "2025-04-01T12:00:00Z",
      "result": {
        "id": 1,
        "submission_id": 1,
        "success": true,
        "execution_time": 5,
        "memory_usage": 1024,
        "output": "Hello, World!",
        "error_message": null,
        "test_results": "[{\"test_case\":1,\"success\":true,\"output\":\"Hello, World!\"}]",
        "created_at": "2025-04-01T12:00:00Z",
        "updated_at": "2025-04-01T12:00:00Z"
      }
    }
  ]
}
```

### Get Submission Details

```
GET /submissions/{submission_id}
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Response:**
```json
{
  "submission": {
    "id": 1,
    "user_id": 1,
    "problem_id": 1,
    "code": "function helloWorld() { return 'Hello, World!'; }",
    "language": "JavaScript",
    "status": "completed",
    "created_at": "2025-04-01T12:00:00Z",
    "updated_at": "2025-04-01T12:00:00Z",
    "result": {
      "id": 1,
      "submission_id": 1,
      "success": true,
      "execution_time": 5,
      "memory_usage": 1024,
      "output": "Hello, World!",
      "error_message": null,
      "test_results": "[{\"test_case\":1,\"success\":true,\"output\":\"Hello, World!\"}]",
      "created_at": "2025-04-01T12:00:00Z",
      "updated_at": "2025-04-01T12:00:00Z"
    }
  }
}
```

### Create Submission

```
POST /submissions
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Request Body:**
```json
{
  "problem_id": 1,
  "code": "function helloWorld() { return 'Hello, World!'; }",
  "language": "JavaScript"
}
```

**Response:**
```json
{
  "message": "Submission created successfully",
  "submission": {
    "id": 2,
    "user_id": 1,
    "problem_id": 1,
    "code": "function helloWorld() { return 'Hello, World!'; }",
    "language": "JavaScript",
    "status": "pending",
    "created_at": "2025-04-01T12:30:00Z",
    "updated_at": "2025-04-01T12:30:00Z"
  },
  "result": {
    "id": 2,
    "submission_id": 2,
    "success": false,
    "output": "Submission queued for processing",
    "created_at": "2025-04-01T12:30:00Z",
    "updated_at": "2025-04-01T12:30:00Z"
  }
}
```

### Update Submission Status (Admin/Instructor Only)

```
PUT /submissions/{submission_id}
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Request Body:**
```json
{
  "status": "completed"
}
```

**Response:**
```json
{
  "message": "Submission updated successfully",
  "submission": {
    "id": 2,
    "user_id": 1,
    "problem_id": 1,
    "code": "function helloWorld() { return 'Hello, World!'; }",
    "language": "JavaScript",
    "status": "completed",
    "created_at": "2025-04-01T12:30:00Z",
    "updated_at": "2025-04-01T12:35:00Z"
  }
}
```

### Delete Submission

```
DELETE /submissions/{submission_id}
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Response:**
```json
{
  "message": "Submission deleted successfully"
}
```

## Results

### List Results

```
GET /results
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Query Parameters:**
- `submission_id` (optional): Filter results by submission ID
- `my_results` (optional): Set to `true` to get only results for current user's submissions

**Response:**
```json
{
  "results": [
    {
      "id": 1,
      "submission_id": 1,
      "success": true,
      "execution_time": 5,
      "memory_usage": 1024,
      "output": "Hello, World!",
      "error_message": null,
      "test_results": "[{\"test_case\":1,\"success\":true,\"output\":\"Hello, World!\"}]",
      "created_at": "2025-04-01T12:00:00Z",
      "updated_at": "2025-04-01T12:00:00Z",
      "submission": {
        "id": 1,
        "user_id": 1,
        "problem_id": 1,
        "language": "JavaScript",
        "status": "completed",
        "created_at": "2025-04-01T12:00:00Z",
        "updated_at": "2025-04-01T12:00:00Z"
      }
    }
  ]
}
```

### Get Result Details

```
GET /results/{result_id}
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Response:**
```json
{
  "result": {
    "id": 1,
    "submission_id": 1,
    "success": true,
    "execution_time": 5,
    "memory_usage": 1024,
    "output": "Hello, World!",
    "error_message": null,
    "test_results": "[{\"test_case\":1,\"success\":true,\"output\":\"Hello, World!\"}]",
    "created_at": "2025-04-01T12:00:00Z",
    "updated_at": "2025-04-01T12:00:00Z"
  }
}
```

### Create Result (Admin/Instructor Only)

```
POST /results
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Request Body:**
```json
{
  "submission_id": 2,
  "success": true,
  "execution_time": 10,
  "memory_usage": 2048,
  "output": "Hello, World!",
  "error_message": null,
  "test_results": "[{\"test_case\":1,\"success\":true,\"output\":\"Hello, World!\"}]"
}
```

**Response:**
```json
{
  "message": "Result created successfully",
  "result": {
    "id": 3,
    "submission_id": 2,
    "success": true,
    "execution_time": 10,
    "memory_usage": 2048,
    "output": "Hello, World!",
    "error_message": null,
    "test_results": "[{\"test_case\":1,\"success\":true,\"output\":\"Hello, World!\"}]",
    "created_at": "2025-04-01T12:40:00Z",
    "updated_at": "2025-04-01T12:40:00Z"
  }
}
```

### Update Result (Admin/Instructor Only)

```
PUT /results/{result_id}
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Request Body:**
```json
{
  "success": false,
  "error_message": "Test case failed",
  "test_results": "[{\"test_case\":1,\"success\":false,\"output\":\"Incorrect output\"}]"
}
```

**Response:**
```json
{
  "message": "Result updated successfully",
  "result": {
    "id": 3,
    "submission_id": 2,
    "success": false,
    "execution_time": 10,
    "memory_usage": 2048,
    "output": "Hello, World!",
    "error_message": "Test case failed",
    "test_results": "[{\"test_case\":1,\"success\":false,\"output\":\"Incorrect output\"}]",
    "created_at": "2025-04-01T12:40:00Z",
    "updated_at": "2025-04-01T12:45:00Z"
  }
}
```

### Delete Result (Admin Only)

```
DELETE /results/{result_id}
```

**Headers:**
```
Authorization: Bearer your_auth_token
```

**Response:**
```json
{
  "message": "Result deleted successfully"
}
```

## Error Responses

All API endpoints return appropriate HTTP status codes:

- `200 OK`: Request succeeded
- `201 Created`: Resource created successfully
- `400 Bad Request`: Invalid request parameters
- `401 Unauthorized`: Authentication required or failed
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation errors
- `500 Internal Server Error`: Server error

Error responses include a message and, when applicable, detailed validation errors:

```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```
