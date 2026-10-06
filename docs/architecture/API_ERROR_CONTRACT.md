# API Error Contract

This document defines the standardized error response formats returned by the Portfolio Manager API. The application relies on Laravel's native exception rendering to ensure consistent JSON structures.

## 1. General Principles

* All API errors return a standard JSON object containing a `message` key.
* Field-specific errors (like validation or business rules) include an `errors` object.
* Sensitive debugging information (like stack traces) is never exposed in the production environment (`APP_DEBUG=false`).

## 2. Standardized Error Formats

### 2.1 Not Found (404)
When a requested resource or route does not exist.

```json
{
    "message": "The route api/v1/missing could not be found."
}
```

### 2.2 Method Not Allowed (405)
When the HTTP method is not supported for the requested route.

```json
{
    "message": "The POST method is not supported for this route. Supported methods: GET, HEAD."
}
```

### 2.3 Unauthenticated (401)
When a valid authentication token is missing or expired.

```json
{
    "message": "Unauthenticated."
}
```

### 2.4 Unauthorized / Forbidden (403)
When the authenticated user does not have permission to access the requested resource.

```json
{
    "message": "This action is unauthorized."
}
```

### 2.5 Rate Limit Exceeded (429)
When the user has exceeded their permitted request quota.

```json
{
    "message": "Too Many Attempts."
}
```

### 2.6 Validation Errors (422)
When the provided request data fails validation rules.

```json
{
    "message": "The name field is required.",
    "errors": {
        "name": [
            "The name field is required."
        ]
    }
}
```

### 2.7 Business Logic / Domain Errors (409)
Custom domain exceptions (e.g., `MissingMarketDataException`) use the standardized Laravel validation error structure to provide actionable feedback.

```json
{
    "message": "Market data is temporarily unavailable for one or more holdings.",
    "errors": {
        "market_data": [
            "Missing data for ABC"
        ]
    }
}
```

### 2.8 Server Errors (500)
When an unexpected fatal error occurs in production. The system will mask the error details and report the exception securely to Sentry.

```json
{
    "message": "Server Error"
}
```

**Note**: In the local development environment (`APP_DEBUG=true`), the 500 response will include a detailed `trace` and `exception` properties.

## 3. Telemetry and Sentry Integration

* Fatal exceptions (5xx) are automatically reported to Sentry using the native Laravel Sentry integration.
* PII (Personally Identifiable Information) such as passwords and session tokens are scrubbed from Sentry reports natively.
* Custom application logging explicitly uses standard PHP contexts to augment telemetry without exposing sensitive data.
