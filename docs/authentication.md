# Authentication Flow Documentation - YouCode Evaluator

This document provides detailed information about the authentication and authorization flow in the YouCode Evaluator platform.

## Overview

The YouCode Evaluator platform uses a token-based authentication system implemented with Laravel Sanctum. This system provides secure authentication for API access and supports role-based authorization.

## Authentication Flow

### Registration

1. User submits registration information (name, email, password)
2. System validates the information
3. System creates a new user record with a hashed password
4. System assigns the default 'candidate' role to the user
5. System generates an authentication token
6. System returns the token to the user

### Login

1. User submits login credentials (email, password)
2. System validates the credentials
3. System generates an authentication token
4. System returns the token to the user

### Authentication

1. User includes the token in the Authorization header of API requests
2. System validates the token
3. System identifies the user associated with the token
4. System allows or denies access based on the user's role and permissions

### Logout

1. User sends a logout request with their token
2. System invalidates the token
3. System confirms successful logout

### Password Reset

1. User requests a password reset by providing their email
2. System generates a password reset token and sends it to the user's email
3. User submits a new password along with the reset token
4. System validates the token and updates the user's password
5. System confirms successful password reset

## Token Management

### Token Generation

Tokens are generated using Laravel Sanctum's token generation mechanism, which creates a secure, random token for each user session.

### Token Storage

Tokens are stored in the `personal_access_tokens` table with the following information:
- Token ID
- User ID
- Token name
- Hashed token
- Token abilities (permissions)
- Last used timestamp
- Expiration timestamp

### Token Validation

When a request is received with an authentication token:
1. System retrieves the token from the Authorization header
2. System looks up the token in the database
3. System verifies the token is valid and not expired
4. System identifies the associated user
5. System proceeds with the request if the token is valid

### Token Expiration

Tokens can be configured to expire after a certain period of inactivity. The default expiration time is 60 minutes of inactivity, but this can be configured in the application settings.

## Role-Based Authorization

### Roles

The system defines three roles:
1. **Candidate**: Applicant to YouCode training
2. **Instructor**: Teaching staff for evaluation
3. **Administrator**: Platform management personnel

### Role Assignment

- Users are assigned a role during registration (default: candidate)
- Administrators can change user roles through the user management interface

### Permission Checks

Permission checks are performed at several levels:
1. **Route middleware**: Restricts access to routes based on user roles
2. **Controller methods**: Validates user permissions for specific actions
3. **Service layer**: Enforces business rules based on user roles

### Middleware

The system uses custom middleware to enforce role-based access control:
- `role`: Checks if the user has one of the specified roles
- `ensureSessionIsValid`: Validates the user's session

## Implementation Details

### User Model

The User model implements the `HasApiTokens` trait from Laravel Sanctum, which provides methods for token management:
- `createToken()`: Creates a new API token
- `tokens()`: Relationship to the user's tokens
- `currentAccessToken()`: Gets the current token being used
- `withAccessToken()`: Sets the current token

### Authentication Controller

The AuthController handles authentication-related actions:
- `register()`: Registers a new user
- `login()`: Authenticates a user and issues a token
- `logout()`: Invalidates the current token
- `forgotPassword()`: Initiates the password reset process
- `resetPassword()`: Completes the password reset process
- `user()`: Returns the authenticated user's information

### Middleware Registration

Middleware is registered in the `app/Http/Kernel.php` file:
```php
protected $routeMiddleware = [
    // ...
    'role' => \App\Http\Middleware\CheckRole::class,
    'ensureSessionIsValid' => \App\Http\Middleware\EnsureSessionIsValid::class,
];
```

### Route Protection

Routes are protected using middleware in the `routes/api.php` file:
```php
// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Routes accessible to all authenticated users
    
    // Admin only routes
    Route::middleware('role:administrator')->group(function () {
        // Routes accessible only to administrators
    });
    
    // Instructor routes
    Route::middleware('role:administrator,instructor')->group(function () {
        // Routes accessible to administrators and instructors
    });
});
```

## Security Considerations

### Password Hashing

Passwords are hashed using bcrypt with a work factor of 10, providing strong protection against brute force attacks.

### CSRF Protection

Cross-Site Request Forgery (CSRF) protection is implemented for web routes, but API routes using token authentication are exempt from CSRF protection.

### Rate Limiting

API routes are rate-limited to prevent abuse:
- 60 requests per minute for authenticated users
- 10 requests per minute for unauthenticated users

### Token Abilities

Tokens can be created with specific abilities (permissions), allowing fine-grained control over what actions a token can perform.

### Secure Headers

The application sets secure headers to protect against common web vulnerabilities:
- Content-Security-Policy
- X-Content-Type-Options
- X-Frame-Options
- X-XSS-Protection

## Error Handling

### Authentication Errors

- Invalid credentials: Returns a 401 Unauthorized response
- Expired token: Returns a 401 Unauthorized response with a message indicating the token has expired
- Invalid token: Returns a 401 Unauthorized response with a message indicating the token is invalid

### Authorization Errors

- Insufficient permissions: Returns a 403 Forbidden response with a message indicating the user does not have the required permissions

## Example Flows

### Registration Flow

```
POST /api/register
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}

Response:
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role_id": 1
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz123456"
}
```

### Login Flow

```
POST /api/login
{
  "email": "john@example.com",
  "password": "password123"
}

Response:
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role_id": 1
  },
  "token": "2|abcdefghijklmnopqrstuvwxyz123456"
}
```

### Authenticated Request

```
GET /api/user
Authorization: Bearer 2|abcdefghijklmnopqrstuvwxyz123456

Response:
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "role_id": 1
}
```

### Password Reset Flow

```
POST /api/forgot-password
{
  "email": "john@example.com"
}

Response:
{
  "message": "Password reset link sent to your email"
}

POST /api/reset-password
{
  "email": "john@example.com",
  "token": "reset_token_from_email",
  "password": "new_password",
  "password_confirmation": "new_password"
}

Response:
{
  "message": "Password reset successfully"
}
```

## Best Practices

1. **Token Storage**: Store tokens securely on the client side, preferably in HttpOnly cookies or secure local storage.
2. **Token Refresh**: Implement token refresh mechanisms for long-lived sessions.
3. **Logout**: Always logout when finished to invalidate tokens.
4. **HTTPS**: Use HTTPS for all API communications to protect tokens in transit.
5. **Minimal Permissions**: Request and grant only the permissions needed for the specific operation.
6. **Token Expiration**: Set appropriate expiration times for tokens based on security requirements.
7. **Audit Logging**: Log authentication events for security monitoring and auditing.
