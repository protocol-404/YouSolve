# API Documentation - YouCode Evaluator

This document provides detailed information about the API endpoints available in the YouCode Evaluator platform.

## Base URL

All API endpoints are relative to the base URL:

```
https://api.youcode-evaluator.com/api
```

## Authentication

Most API endpoints require authentication using a Bearer token. To authenticate, include the token in the Authorization header:

```
Authorization: Bearer {your_token}
```

### Getting a Token

To get an authentication token, use the login endpoint:

```
POST /login
```

## API Endpoints

### Authentication

#### Register a new user

```
POST /register
```

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response:**
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
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
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role_id": 1
  },
  "token": "your_auth_token"
}
```

#### Logout

```
POST /logout
```

**Response:**
```json
{
  "message": "Logged out successfully"
}
```

#### Forgot Password

```
POST /forgot-password
```

**Request Body:**
```json
{
  "email": "john@example.com"
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
  "email": "john@example.com",
  "token": "reset_token",
  "password": "new_password",
  "password_confirmation": "new_password"
}
```

**Response:**
```json
{
  "message": "Password reset successfully"
}
```

#### Get Current User

```
GET /user
```

**Response:**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "role_id": 1
}
```

### User Management

#### List Users (Admin/Instructor only)

```
GET /users
```

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15)
- `role_id`: Filter by role ID

**Response:**
```json
{
  "users": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role_id": 1
    },
    {
      "id": 2,
      "name": "Jane Smith",
      "email": "jane@example.com",
      "role_id": 2
    }
  ],
  "pagination": {
    "total": 10,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1
  }
}
```

#### Create User (Admin only)

```
POST /users
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

#### Get User Details

```
GET /users/{id}
```

**Response:**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role_id": 1,
    "created_at": "2025-04-01T12:00:00.000000Z",
    "updated_at": "2025-04-01T12:00:00.000000Z"
  }
}
```

#### Update User (Admin only)

```
PUT /users/{id}
```

**Request Body:**
```json
{
  "name": "Updated Name",
  "email": "updated@example.com",
  "role_id": 2
}
```

**Response:**
```json
{
  "message": "User updated successfully",
  "user": {
    "id": 1,
    "name": "Updated Name",
    "email": "updated@example.com",
    "role_id": 2
  }
}
```

#### Delete User (Admin only)

```
DELETE /users/{id}
```

**Response:**
```json
{
  "message": "User deleted successfully"
}
```

### Permissions

#### Get Roles

```
GET /roles
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

#### Check Role

```
GET /check-role/{role}
```

**Response:**
```json
{
  "has_role": true
}
```

#### Get User Permissions

```
GET /permissions
```

**Response:**
```json
{
  "permissions": [
    "view_evaluations",
    "solve_problems",
    "submit_code",
    "view_own_submissions"
  ]
}
```

### Evaluations

#### List Evaluations

```
GET /evaluations
```

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15)
- `is_active`: Filter by active status (true/false)

**Response:**
```json
{
  "evaluations": [
    {
      "id": 1,
      "name": "PHP Basics",
      "description": "Basic PHP programming concepts",
      "language": "PHP",
      "is_active": true
    },
    {
      "id": 2,
      "name": "JavaScript Fundamentals",
      "description": "Core JavaScript concepts",
      "language": "JavaScript",
      "is_active": true
    }
  ],
  "pagination": {
    "total": 5,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1
  }
}
```

#### Get Evaluation Details

```
GET /evaluations/{id}
```

**Response:**
```json
{
  "evaluation": {
    "id": 1,
    "name": "PHP Basics",
    "description": "Basic PHP programming concepts",
    "language": "PHP",
    "is_active": true,
    "created_at": "2025-04-01T12:00:00.000000Z",
    "updated_at": "2025-04-01T12:00:00.000000Z"
  }
}
```

#### Create Evaluation (Admin only)

```
POST /evaluations
```

**Request Body:**
```json
{
  "name": "C Programming",
  "description": "Introduction to C programming",
  "language": "C",
  "is_active": true
}
```

**Response:**
```json
{
  "message": "Evaluation created successfully",
  "evaluation": {
    "id": 3,
    "name": "C Programming",
    "description": "Introduction to C programming",
    "language": "C",
    "is_active": true
  }
}
```

#### Update Evaluation (Admin only)

```
PUT /evaluations/{id}
```

**Request Body:**
```json
{
  "name": "Updated Evaluation",
  "description": "Updated description",
  "is_active": false
}
```

**Response:**
```json
{
  "message": "Evaluation updated successfully",
  "evaluation": {
    "id": 1,
    "name": "Updated Evaluation",
    "description": "Updated description",
    "language": "PHP",
    "is_active": false
  }
}
```

#### Delete Evaluation (Admin only)

```
DELETE /evaluations/{id}
```

**Response:**
```json
{
  "message": "Evaluation deleted successfully"
}
```

#### Get Evaluation Leaderboard

```
GET /evaluations/{id}/leaderboard
```

**Query Parameters:**
- `limit`: Maximum number of top performers to return (default: 10)

**Response:**
```json
{
  "evaluation_id": 1,
  "top_performers": [
    {
      "user_id": 5,
      "name": "Top Performer",
      "score": 850,
      "problems_solved": 3,
      "total_problems": 3
    },
    {
      "user_id": 2,
      "name": "Second Place",
      "score": 720,
      "problems_solved": 3,
      "total_problems": 3
    }
  ]
}
```

### Problems

#### List Problems

```
GET /problems
```

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15)
- `evaluation_id`: Filter by evaluation ID
- `difficulty`: Filter by difficulty (easy, medium, hard)
- `is_active`: Filter by active status (true/false)

**Response:**
```json
{
  "problems": [
    {
      "id": 1,
      "evaluation_id": 1,
      "title": "Hello World",
      "description": "Write a function that returns 'Hello, World!'",
      "difficulty": "easy",
      "is_active": true
    },
    {
      "id": 2,
      "evaluation_id": 1,
      "title": "Factorial",
      "description": "Write a function to calculate factorial",
      "difficulty": "medium",
      "is_active": true
    }
  ],
  "pagination": {
    "total": 10,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1
  }
}
```

#### Get Problem Details

```
GET /problems/{id}
```

**Response:**
```json
{
  "problem": {
    "id": 1,
    "evaluation_id": 1,
    "title": "Hello World",
    "description": "Write a function that returns 'Hello, World!'",
    "example_input": "",
    "example_output": "Hello, World!",
    "difficulty": "easy",
    "time_limit": 1000,
    "memory_limit": 128,
    "is_active": true,
    "created_at": "2025-04-01T12:00:00.000000Z",
    "updated_at": "2025-04-01T12:00:00.000000Z"
  }
}
```

#### Create Problem (Admin/Instructor only)

```
POST /problems
```

**Request Body:**
```json
{
  "evaluation_id": 1,
  "title": "New Problem",
  "description": "Problem description",
  "example_input": "Sample input",
  "example_output": "Sample output",
  "difficulty": "medium",
  "time_limit": 1000,
  "memory_limit": 128,
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
    "title": "New Problem",
    "description": "Problem description",
    "example_input": "Sample input",
    "example_output": "Sample output",
    "difficulty": "medium",
    "time_limit": 1000,
    "memory_limit": 128,
    "is_active": true
  }
}
```

#### Update Problem (Admin/Instructor only)

```
PUT /problems/{id}
```

**Request Body:**
```json
{
  "title": "Updated Problem",
  "description": "Updated description",
  "difficulty": "hard",
  "is_active": false
}
```

**Response:**
```json
{
  "message": "Problem updated successfully",
  "problem": {
    "id": 1,
    "evaluation_id": 1,
    "title": "Updated Problem",
    "description": "Updated description",
    "difficulty": "hard",
    "is_active": false
  }
}
```

#### Delete Problem (Admin only)

```
DELETE /problems/{id}
```

**Response:**
```json
{
  "message": "Problem deleted successfully"
}
```

#### Create Test Cases (Admin/Instructor only)

```
POST /problems/{id}/test-cases
```

**Request Body:**
```json
{
  "test_cases": [
    {
      "input": "",
      "output": "Hello, World!"
    },
    {
      "input": "name=John",
      "output": "Hello, John!"
    }
  ]
}
```

**Response:**
```json
{
  "message": "Test cases created successfully",
  "problem": {
    "id": 1,
    "title": "Hello World"
  }
}
```

#### Validate Test Cases (Admin/Instructor only)

```
POST /problems/{id}/validate-test-cases
```

**Request Body:**
```json
{
  "sample_solution": "<?php echo 'Hello, World!'; ?>",
  "language": "PHP"
}
```

**Response:**
```json
{
  "message": "Test cases are valid",
  "validation_result": {
    "valid": true,
    "test_results": [
      {
        "test_case": 1,
        "success": true,
        "execution_time": 5,
        "memory_usage": 1024,
        "output": "Hello, World!"
      }
    ]
  }
}
```

#### Generate Test Cases (Admin/Instructor only)

```
POST /problems/{id}/generate-test-cases
```

**Request Body:**
```json
{
  "sample_solution": "<?php echo 'Hello, World!'; ?>",
  "language": "PHP",
  "inputs": [
    "",
    "name=John",
    "name=Alice"
  ]
}
```

**Response:**
```json
{
  "message": "Test cases generated successfully",
  "test_cases": [
    {
      "input": "",
      "output": "Hello, World!"
    },
    {
      "input": "name=John",
      "output": "Hello, John!"
    },
    {
      "input": "name=Alice",
      "output": "Hello, Alice!"
    }
  ]
}
```

### Submissions

#### List Submissions

```
GET /submissions
```

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15)
- `user_id`: Filter by user ID
- `problem_id`: Filter by problem ID
- `status`: Filter by status (pending, running, completed, failed)

**Response:**
```json
{
  "submissions": [
    {
      "id": 1,
      "user_id": 1,
      "problem_id": 1,
      "language": "PHP",
      "status": "completed",
      "created_at": "2025-04-01T12:00:00.000000Z"
    },
    {
      "id": 2,
      "user_id": 1,
      "problem_id": 2,
      "language": "PHP",
      "status": "failed",
      "created_at": "2025-04-01T12:30:00.000000Z"
    }
  ],
  "pagination": {
    "total": 5,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1
  }
}
```

#### Create Submission

```
POST /submissions
```

**Request Body:**
```json
{
  "problem_id": 1,
  "code": "<?php echo 'Hello, World!'; ?>",
  "language": "PHP"
}
```

**Response:**
```json
{
  "message": "Submission created successfully",
  "submission": {
    "id": 3,
    "user_id": 1,
    "problem_id": 1,
    "language": "PHP",
    "status": "pending"
  }
}
```

#### Get Submission Details

```
GET /submissions/{id}
```

**Response:**
```json
{
  "submission": {
    "id": 1,
    "user_id": 1,
    "problem_id": 1,
    "code": "<?php echo 'Hello, World!'; ?>",
    "language": "PHP",
    "status": "completed",
    "created_at": "2025-04-01T12:00:00.000000Z",
    "updated_at": "2025-04-01T12:00:05.000000Z",
    "result": {
      "id": 1,
      "submission_id": 1,
      "success": true,
      "execution_time": 5,
      "memory_usage": 1024,
      "output": "Hello, World!",
      "error_message": null,
      "score": 100
    },
    "problem": {
      "id": 1,
      "title": "Hello World",
      "difficulty": "easy"
    }
  }
}
```

#### Delete Submission

```
DELETE /submissions/{id}
```

**Response:**
```json
{
  "message": "Submission deleted successfully"
}
```

### Results

#### List Results

```
GET /results
```

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15)
- `submission_id`: Filter by submission ID
- `success`: Filter by success status (true/false)

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
      "score": 100,
      "created_at": "2025-04-01T12:00:05.000000Z"
    },
    {
      "id": 2,
      "submission_id": 2,
      "success": false,
      "execution_time": 10,
      "memory_usage": 2048,
      "score": 0,
      "created_at": "2025-04-01T12:30:05.000000Z"
    }
  ],
  "pagination": {
    "total": 5,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1
  }
}
```

#### Get Result Details

```
GET /results/{id}
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
    "test_results": [
      {
        "test_case": 1,
        "success": true,
        "execution_time": 5,
        "memory_usage": 1024,
        "output": "Hello, World!"
      }
    ],
    "score": 100,
    "created_at": "2025-04-01T12:00:05.000000Z",
    "updated_at": "2025-04-01T12:00:05.000000Z"
  }
}
```

### Code Execution

#### Submit Code

```
POST /code/submit
```

**Request Body:**
```json
{
  "problem_id": 1,
  "code": "<?php echo 'Hello, World!'; ?>",
  "language": "PHP"
}
```

**Response:**
```json
{
  "message": "Submission processed successfully",
  "submission": {
    "id": 3,
    "user_id": 1,
    "problem_id": 1,
    "language": "PHP",
    "status": "completed"
  },
  "result": {
    "id": 3,
    "submission_id": 3,
    "success": true,
    "execution_time": 5,
    "memory_usage": 1024,
    "output": "Hello, World!",
    "error_message": null,
    "score": 100
  }
}
```

#### Get Submission

```
GET /code/submissions/{id}
```

**Response:**
```json
{
  "submission": {
    "id": 1,
    "user_id": 1,
    "problem_id": 1,
    "code": "<?php echo 'Hello, World!'; ?>",
    "language": "PHP",
    "status": "completed",
    "result": {
      "id": 1,
      "submission_id": 1,
      "success": true,
      "execution_time": 5,
      "memory_usage": 1024,
      "output": "Hello, World!",
      "error_message": null,
      "score": 100
    },
    "problem": {
      "id": 1,
      "title": "Hello World",
      "difficulty": "easy"
    }
  }
}
```

### User Progress

#### Get My Progress

```
GET /my-progress
```

**Response:**
```json
{
  "user_id": 1,
  "total_submissions": 5,
  "successful_submissions": 3,
  "success_rate": 60,
  "submissions_by_language": {
    "PHP": 3,
    "JavaScript": 2
  },
  "submissions_by_difficulty": {
    "easy": 2,
    "medium": 2,
    "hard": 1
  },
  "solved_problems_count": 3,
  "recent_submissions": [
    {
      "id": 5,
      "problem_id": 3,
      "language": "PHP",
      "status": "completed",
      "created_at": "2025-04-01T14:00:00.000000Z"
    },
    {
      "id": 4,
      "problem_id": 2,
      "language": "JavaScript",
      "status": "failed",
      "created_at": "2025-04-01T13:30:00.000000Z"
    }
  ]
}
```

#### Get User Progress (Admin/Instructor only)

```
GET /users/{id}/progress
```

**Response:**
```json
{
  "user_id": 2,
  "total_submissions": 10,
  "successful_submissions": 7,
  "success_rate": 70,
  "submissions_by_language": {
    "PHP": 5,
    "JavaScript": 3,
    "C": 2
  },
  "submissions_by_difficulty": {
    "easy": 4,
    "medium": 4,
    "hard": 2
  },
  "solved_problems_count": 7,
  "recent_submissions": [
    {
      "id": 15,
      "problem_id": 5,
      "language": "C",
      "status": "completed",
      "created_at": "2025-04-01T15:00:00.000000Z"
    },
    {
      "id": 14,
      "problem_id": 4,
      "language": "JavaScript",
      "status": "completed",
      "created_at": "2025-04-01T14:30:00.000000Z"
    }
  ]
}
```

#### Get My Evaluation Progress

```
GET /my-evaluations/{id}/progress
```

**Response:**
```json
{
  "user_id": 1,
  "evaluation_id": 1,
  "total_problems": 3,
  "solved_problems": 2,
  "completion_percentage": 66.67,
  "problems": [
    {
      "problem_id": 1,
      "title": "Hello World",
      "difficulty": "easy",
      "is_solved": true,
      "attempts": 1,
      "best_score": 100
    },
    {
      "problem_id": 2,
      "title": "Factorial",
      "difficulty": "medium",
      "is_solved": true,
      "attempts": 2,
      "best_score": 180
    },
    {
      "problem_id": 3,
      "title": "Binary Search",
      "difficulty": "hard",
      "is_solved": false,
      "attempts": 1,
      "best_score": 0
    }
  ]
}
```

#### Get User Evaluation Progress (Admin/Instructor only)

```
GET /users/{user_id}/evaluations/{evaluation_id}/progress
```

**Response:**
```json
{
  "user_id": 2,
  "evaluation_id": 1,
  "total_problems": 3,
  "solved_problems": 3,
  "completion_percentage": 100,
  "problems": [
    {
      "problem_id": 1,
      "title": "Hello World",
      "difficulty": "easy",
      "is_solved": true,
      "attempts": 1,
      "best_score": 100
    },
    {
      "problem_id": 2,
      "title": "Factorial",
      "difficulty": "medium",
      "is_solved": true,
      "attempts": 1,
      "best_score": 200
    },
    {
      "problem_id": 3,
      "title": "Binary Search",
      "difficulty": "hard",
      "is_solved": true,
      "attempts": 3,
      "best_score": 250
    }
  ]
}
```

#### Get My Learning Path Recommendations

```
GET /my-learning-path
```

**Response:**
```json
{
  "user_id": 1,
  "priority_problems": [
    {
      "id": 3,
      "title": "Binary Search",
      "difficulty": "hard"
    }
  ],
  "recommended_by_difficulty": {
    "easy": [
      {
        "id": 4,
        "title": "String Reversal",
        "difficulty": "easy"
      },
      {
        "id": 7,
        "title": "FizzBuzz",
        "difficulty": "easy"
      }
    ],
    "medium": [
      {
        "id": 5,
        "title": "Palindrome Check",
        "difficulty": "medium"
      },
      {
        "id": 8,
        "title": "Array Sorting",
        "difficulty": "medium"
      }
    ],
    "hard": [
      {
        "id": 6,
        "title": "Dijkstra's Algorithm",
        "difficulty": "hard"
      },
      {
        "id": 9,
        "title": "Dynamic Programming",
        "difficulty": "hard"
      }
    ]
  },
  "recommended_by_language": [
    {
      "id": 10,
      "title": "PHP Arrays",
      "difficulty": "medium"
    },
    {
      "id": 11,
      "title": "PHP Functions",
      "difficulty": "medium"
    },
    {
      "id": 12,
      "title": "PHP OOP",
      "difficulty": "hard"
    }
  ],
  "most_used_language": "PHP"
}
```

#### Get My Activity Timeline

```
GET /my-activity
```

**Query Parameters:**
- `days`: Number of days to include in the timeline (default: 30)

**Response:**
```json
{
  "user_id": 1,
  "days": 30,
  "timeline": [
    {
      "date": "2025-04-01",
      "total_submissions": 3,
      "successful_submissions": 2
    },
    {
      "date": "2025-03-31",
      "total_submissions": 2,
      "successful_submissions": 1
    },
    {
      "date": "2025-03-30",
      "total_submissions": 0,
      "successful_submissions": 0
    }
  ]
}
```

#### Get User Score

```
GET /users/{id}/score
```

**Response:**
```json
{
  "user_id": 1,
  "name": "John Doe",
  "total_score": 550,
  "ranking": 3
}
```

## Error Responses

### Validation Error

```json
{
  "message": "Validation failed",
  "errors": {
    "email": [
      "The email field is required."
    ],
    "password": [
      "The password field is required."
    ]
  }
}
```

### Authentication Error

```json
{
  "message": "Unauthenticated."
}
```

### Authorization Error

```json
{
  "message": "Unauthorized"
}
```

### Resource Not Found

```json
{
  "message": "Resource not found"
}
```

### Server Error

```json
{
  "message": "Server error",
  "error": "Error details"
}
```
