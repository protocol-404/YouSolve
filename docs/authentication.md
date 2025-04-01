# Authentication Flow Documentation for YouCode Evaluator

This document describes the authentication flow and security mechanisms implemented in the YouCode Evaluator platform.

## Overview

The YouCode Evaluator platform uses Laravel Sanctum for API token authentication, providing a secure and stateless authentication system for the API. The authentication flow includes user registration, login, logout, password reset, and session management.

## Authentication Flow

### User Registration

1. User submits registration information (name, email, password, role)
2. System validates the input data
3. If validation passes, the system:
   - Creates a new user record with hashed password
   - Assigns the specified role to the user
   - Generates an API token for the user
   - Returns the user data and token

### User Login

1. User submits login credentials (email, password)
2. System validates the credentials
3. If credentials are valid, the system:
   - Generates a new API token for the user
   - Returns the user data and token
4. If credentials are invalid, the system returns an authentication error

### User Logout

1. User sends a logout request with their API token
2. System invalidates and deletes the current token
3. Returns a success message

### Password Reset

1. User requests a password reset by providing their email
2. System sends a password reset link to the user's email
3. User clicks the link and submits a new password
4. System validates the token and updates the user's password
5. Returns a success message

## Token-based Authentication

The YouCode Evaluator platform uses Laravel Sanctum for token-based authentication:

1. When a user logs in or registers, the system generates a personal access token
2. This token must be included in the `Authorization` header of all API requests:
   ```
   Authorization: Bearer {token}
   ```
3. The system validates the token for each request to protected endpoints
4. If the token is valid, the request is processed
5. If the token is invalid or expired, the system returns a 401 Unauthorized response

## Session Management

The platform includes a custom middleware (`EnsureSessionIsValid`) to manage API sessions:

1. The middleware checks if the user is authenticated
2. It verifies that the token being used is still valid and not expired
3. If the session is invalid, the middleware returns a 401 Unauthorized response

## Role-based Access Control

Authentication is tightly integrated with the authorization system:

1. Each user is assigned a role (candidate, instructor, or administrator)
2. The `CheckRole` middleware validates user roles for protected routes
3. Different API endpoints require different roles for access
4. The system checks the user's role before allowing access to protected resources

## Security Measures

The YouCode Evaluator platform implements several security measures:

1. **Password Hashing**: All passwords are hashed using Laravel's bcrypt implementation
2. **CSRF Protection**: Cross-Site Request Forgery protection for web routes
3. **Rate Limiting**: API routes are rate-limited to prevent abuse
4. **Validation**: All input data is validated before processing
5. **Secure Headers**: HTTP headers are configured for security
6. **Token Expiration**: API tokens can be configured to expire after a certain period

## Authentication Controllers and Middleware

### AuthController

The `AuthController` handles all authentication-related functionality:

- `register()`: Handles user registration
- `login()`: Handles user login
- `logout()`: Handles user logout
- `user()`: Returns the authenticated user's data
- `forgotPassword()`: Handles password reset requests
- `resetPassword()`: Handles password reset

### Middleware

- `EnsureSessionIsValid`: Validates the user's session
- `CheckRole`: Validates the user's role for protected routes

## Authentication Routes

```
POST /api/register
POST /api/login
POST /api/logout
GET /api/user
POST /api/forgot-password
POST /api/reset-password
```

## Error Handling

The authentication system includes comprehensive error handling:

- Invalid credentials return a 401 Unauthorized response
- Invalid tokens return a 401 Unauthorized response
- Insufficient permissions return a 403 Forbidden response
- Validation errors return a 422 Unprocessable Entity response with detailed error messages

## Best Practices

The YouCode Evaluator platform follows these authentication best practices:

1. Use HTTPS for all API requests
2. Store tokens securely on the client side
3. Include the token in the Authorization header for all requests
4. Implement token refresh mechanisms for long-lived sessions
5. Log out users when they are inactive for extended periods
6. Implement proper error handling for authentication failures
