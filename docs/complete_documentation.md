# YouCode Evaluator - Complete Documentation

This document provides comprehensive documentation for the YouCode Evaluator platform, a system for evaluating programming skills of candidates in C, JavaScript, and PHP.

## Table of Contents

1. [Overview](#overview)
2. [System Architecture](#system-architecture)
3. [Features](#features)
4. [Technical Implementation](#technical-implementation)
5. [API Reference](#api-reference)
6. [Database Schema](#database-schema)
7. [Authentication and Authorization](#authentication-and-authorization)
8. [Code Execution System](#code-execution-system)
9. [Evaluation and Scoring](#evaluation-and-scoring)
10. [User Progress Tracking](#user-progress-tracking)
11. [Setup and Deployment](#setup-and-deployment)
12. [Testing](#testing)

## Overview

YouCode Evaluator is a platform designed to assess programming skills of candidates applying to YouCode training programs. The system allows candidates to solve programming problems in C, JavaScript, and PHP, and provides automated evaluation of their solutions.

The platform supports three user roles:
- **Candidates**: Applicants to YouCode training programs
- **Instructors**: Teaching staff responsible for creating and evaluating problems
- **Administrators**: Platform management personnel with full system access

## System Architecture

The YouCode Evaluator platform is built using the following technologies:

- **Backend**: Laravel (PHP framework)
- **Database**: PostgreSQL
- **Authentication**: Laravel Sanctum (token-based)
- **Code Execution**: Sandboxed environment for C, JavaScript, and PHP
- **Containerization**: Docker for development and deployment

The system follows a service-oriented architecture with the following components:

1. **API Layer**: RESTful API endpoints for client interaction
2. **Service Layer**: Business logic services for code execution, evaluation, and user progress
3. **Data Layer**: Database models and repositories
4. **Authentication Layer**: User authentication and authorization
5. **Execution Layer**: Sandboxed code execution environment

## Features

### For Candidates
- Register and login to the platform
- View available programming evaluations
- Solve programming problems in C, JavaScript, and PHP
- Submit code solutions for automated evaluation
- View submission results and feedback
- Track progress and performance
- View leaderboards and rankings

### For Instructors
- Create and manage programming problems
- Define test cases for automated evaluation
- Validate test cases with sample solutions
- Generate test cases from sample solutions
- View candidate submissions and results
- Track candidate progress and performance
- View evaluation statistics

### For Administrators
- Manage users and roles
- Create and manage evaluations
- Configure system settings
- View platform statistics and reports

## Technical Implementation

### Backend Framework

The backend is implemented using Laravel, a PHP framework that provides:
- MVC architecture
- Eloquent ORM for database interactions
- Middleware for request handling
- Authentication and authorization
- API routing
- Job queuing for asynchronous processing

### Database

PostgreSQL is used as the database system, providing:
- Relational data storage
- ACID compliance
- JSON data type support for test cases and results
- Robust indexing and query capabilities

### Code Execution

The code execution system is implemented using:
- Sandboxed environment for secure code execution
- Support for C, JavaScript, and PHP
- Resource limits (time, memory) for execution
- Test case validation against expected outputs
- Performance metrics collection

### Authentication

Authentication is implemented using Laravel Sanctum, providing:
- Token-based authentication for API access
- Secure password hashing
- Session management
- CSRF protection

### Authorization

Authorization is implemented using a role-based access control system:
- Three predefined roles (candidate, instructor, administrator)
- Permission checks for API endpoints
- Middleware for route protection

## API Reference

The API documentation is available in the [API Documentation](api.md) file, which provides detailed information about all available endpoints, request/response formats, and authentication requirements.

## Database Schema

The database schema is documented in the [Database Schema Documentation](database.md) file, which provides detailed information about tables, relationships, and migrations.

## Authentication and Authorization

The authentication and authorization system is documented in the [Authentication Flow Documentation](authentication.md) file, which provides detailed information about the authentication flow, token management, and role-based access control.

## Code Execution System

### Overview

The code execution system is responsible for:
1. Receiving code submissions from users
2. Executing the code in a sandboxed environment
3. Running the code against test cases
4. Evaluating the results
5. Providing feedback to the user

### Components

#### CodeSubmissionService

Handles the submission process:
- Validates submission data
- Creates submission records
- Dispatches execution jobs
- Updates submission status

#### CodeExecutionService

Executes the submitted code:
- Prepares the execution environment
- Compiles code (for C)
- Executes code with test cases
- Measures performance metrics
- Evaluates results against expected outputs

#### CodeTestingService

Manages test cases:
- Creates and validates test cases
- Generates test cases from sample solutions
- Runs tests on submissions

### Execution Flow

1. User submits code through the API
2. CodeSubmissionService creates a submission record
3. Submission is queued for processing
4. CodeExecutionService executes the code in a sandboxed environment
5. Code is run against test cases
6. Results are evaluated and stored
7. User is notified of the results

### Sandboxed Execution

Code is executed in a sandboxed environment with:
- Resource limits (CPU time, memory)
- Restricted access to system resources
- Isolated execution context
- Timeout handling

### Supported Languages

#### C
- Compilation: `gcc code.c -o program`
- Execution: `./program < input.txt`
- Resource limits: CPU time, memory usage

#### JavaScript
- Execution: `node code.js < input.txt`
- Resource limits: CPU time, memory usage

#### PHP
- Execution: `php code.php < input.txt`
- Resource limits: CPU time, memory usage

## Evaluation and Scoring

### Overview

The evaluation and scoring system is responsible for:
1. Evaluating code submissions against test cases
2. Calculating scores based on problem difficulty and performance
3. Updating user rankings
4. Generating leaderboards

### Components

#### EvaluationScoringService

Handles scoring and ranking:
- Calculates scores based on difficulty and performance
- Updates user total scores
- Determines user rankings
- Identifies top performers

### Scoring Algorithm

Scores are calculated based on:
- Problem difficulty (easy, medium, hard)
- Execution time efficiency
- Memory usage efficiency

The formula is:
```
Base Score = Difficulty Score (100 for easy, 200 for medium, 300 for hard)
Performance Multiplier = (Time Efficiency + Memory Efficiency) / 2
Final Score = Base Score * Performance Multiplier
```

### Leaderboards

Leaderboards are generated for:
- Overall platform ranking
- Evaluation-specific ranking
- Language-specific ranking

## User Progress Tracking

### Overview

The user progress tracking system is responsible for:
1. Tracking user submissions and results
2. Calculating progress statistics
3. Providing learning path recommendations
4. Generating activity timelines

### Components

#### UserProgressService

Handles progress tracking:
- Calculates progress statistics
- Tracks evaluation completion
- Generates learning path recommendations
- Creates activity timelines

### Progress Metrics

Progress is tracked using:
- Total submissions
- Successful submissions
- Success rate
- Problems solved by difficulty
- Problems solved by language
- Evaluation completion percentage

### Learning Path Recommendations

Recommendations are based on:
- User's solved problems
- User's attempted but unsolved problems
- User's preferred programming language
- Problem difficulty progression

### Activity Timeline

Activity is tracked with:
- Daily submission counts
- Success rates over time
- Problem-solving patterns

## Setup and Deployment

Setup and deployment instructions are available in the [Setup Instructions](setup.md) file, which provides detailed information about system requirements, installation steps, and deployment options.

## Testing

### Unit Tests

Unit tests cover:
- Authentication and authorization
- API endpoints
- Service methods
- Model relationships

### Integration Tests

Integration tests cover:
- Complete user flows
- Cross-component interactions
- End-to-end scenarios

### Test Coverage

The test suite covers:
- Authentication flow
- Authorization checks
- API functionality
- Code execution
- Evaluation and scoring
- User progress tracking

### Running Tests

Tests can be run using:
```bash
php artisan test
```

For more detailed testing information, see the test files in the `tests` directory.
