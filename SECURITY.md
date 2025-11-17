# Security Policy

## Supported Versions

We release patches for security vulnerabilities. Which versions are eligible for receiving such patches depends on the CVSS v3.0 Rating:

| Version | Supported          | Laravel | PHP     |
| ------- | ------------------ | ------- | ------- |
| 1.1.x   | :white_check_mark: | 10-12   | 8.1-8.3 |
| 1.0.x   | :white_check_mark: | 10-12   | 8.1-8.3 |
| < 1.0   | :x:                | N/A     | N/A     |

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

Instead, please report them via email to the project maintainer. You should receive a response within 48 hours. If for some reason you do not, please follow up via email to ensure we received your original message.

### What to Include

Please include the following information in your report:

- **Type of issue** (e.g., SQL injection, XSS, race condition, etc.)
- **Full paths of source file(s)** related to the manifestation of the issue
- **Location of the affected source code** (tag/branch/commit or direct URL)
- **Step-by-step instructions** to reproduce the issue
- **Proof-of-concept or exploit code** (if possible)
- **Impact of the issue**, including how an attacker might exploit it

This information will help us triage your report more quickly.

### Disclosure Policy

- **Security reports** are confidential until a fix is released
- We will acknowledge your report within **48 hours**
- We will provide a more detailed response within **7 days** indicating next steps
- We will work with you to understand and resolve the issue
- Once the issue is fixed, we will release a security advisory
- We will credit you in the advisory unless you prefer to remain anonymous

## Security Update Process

1. **Report received** - Security issue is reported privately
2. **Triage** - We verify and assess the severity (within 48 hours)
3. **Fix development** - We develop and test a fix (priority based on severity)
4. **Release** - We release a patch version with the fix
5. **Advisory** - We publish a security advisory on GitHub
6. **Notification** - We notify users via GitHub release notes and CHANGELOG

## Security Best Practices for Users

### 1. Keep Dependencies Updated

Always use the latest version of this package to ensure you have the latest security fixes:

```bash
composer update masum/laravel-tagging
```

### 2. Validate All Inputs

Although this package validates inputs, ensure your application also validates data before passing it:

```php
// Good
$validated = $request->validate([
    'name' => 'required|string|max:255',
]);
$equipment = Equipment::create($validated);

// Bad - never trust user input directly
$equipment = Equipment::create($request->all());
```

### 3. Use Appropriate Middleware

Protect API routes with appropriate middleware:

```php
// In your config/tagging.php
'routes' => [
    'middleware' => ['api', 'auth:sanctum', 'throttle:60,1'],
],
```

### 4. Configure Caching Securely

If using Redis or Memcached for caching, ensure they are properly secured:

```env
TAGGING_CACHE_DRIVER=redis
REDIS_PASSWORD=your-secure-password
```

### 5. Enable Production Mode

Never run Laravel in debug mode in production:

```env
APP_DEBUG=false
```

This ensures error messages don't expose sensitive information.

### 6. Database Security

- Use **database user** with minimal required permissions
- Enable **SSL/TLS** for database connections in production
- Regularly **backup** your database

### 7. Rate Limiting

Implement rate limiting on tag generation endpoints to prevent abuse:

```php
Route::middleware(['throttle:60,1'])->group(function () {
    // Tag routes
});
```

### 8. Input Sanitization

The package automatically sanitizes search inputs to prevent SQL injection, but ensure your custom queries do the same:

```php
// Good - uses parameter binding
Tag::where('value', 'LIKE', "%{$search}%")->get();

// Bad - vulnerable to SQL injection
DB::select("SELECT * FROM tags WHERE value LIKE '%{$search}%'");
```

## Known Security Considerations

### 1. Race Conditions (RESOLVED ✅)

**Issue**: Concurrent tag generation could create duplicate tags.

**Resolution**: Fixed in v1.1.0 using pessimistic database locking (SELECT FOR UPDATE).

**Recommendation**: Ensure you've run the latest migrations.

### 2. SQL Injection in Search (RESOLVED ✅)

**Issue**: Search inputs could be abused with wildcard characters.

**Resolution**: Fixed in v1.1.0 with input sanitization escaping wildcards.

**Recommendation**: Update to v1.1.0 or later.

### 3. Information Disclosure

**Issue**: Error messages could expose sensitive information in debug mode.

**Resolution**: The package now hides error details when `APP_DEBUG=false`.

**Best Practice**: Always set `APP_DEBUG=false` in production.

### 4. Mass Assignment

**Issue**: Models using the Tagable trait need proper `$fillable` or `$guarded` properties.

**Recommendation**: Always define `$fillable` in your models:

```php
class Equipment extends Model
{
    use Tagable;

    protected $fillable = ['name', 'description']; // Don't include 'id', 'tag', etc.
}
```

### 5. Barcode XSS

**Issue**: Barcode generation with user-controlled tag values.

**Mitigation**: The package validates and sanitizes tag values before generating barcodes.

**Best Practice**: Still validate inputs before creating models.

## Security Checklist

Before deploying to production:

- [ ] `APP_DEBUG=false` in production
- [ ] Use HTTPS for all API requests
- [ ] Implement rate limiting on API endpoints
- [ ] Use authentication middleware on sensitive routes
- [ ] Run latest package version with security fixes
- [ ] Validate all user inputs before model creation
- [ ] Use proper database user permissions
- [ ] Enable database SSL/TLS connections
- [ ] Configure cache securely (Redis password, etc.)
- [ ] Set up proper CORS policies
- [ ] Implement CSRF protection (Laravel default)
- [ ] Review and limit `$fillable` properties on models
- [ ] Monitor logs for suspicious activity
- [ ] Regular security audits of dependencies

## Security Features

This package includes several built-in security features:

### Input Validation ✅
- Length limits on all string inputs
- Character whitelisting for prefixes (alphanumeric, dash, underscore)
- Validation rules for all API endpoints
- Pagination limits to prevent memory exhaustion

### SQL Injection Prevention ✅
- Uses Laravel's query builder and Eloquent (parameterized queries)
- Sanitizes search inputs (escapes SQL wildcards)
- Uses `Rule::unique()` instead of string concatenation

### Error Handling ✅
- Hides sensitive error details in production
- Logs errors securely without exposing to users
- Returns generic error messages to clients

### Database Security ✅
- Pessimistic locking for concurrent operations
- Unique constraints to prevent duplicate tags
- Indexes for query performance
- Transaction support for data integrity

### Logging & Monitoring ✅
- Comprehensive logging of all operations
- Context-aware logging for debugging
- Security event logging

## Vulnerability Severity Ratings

We use CVSS v3.0 to rate vulnerabilities:

- **CRITICAL (9.0-10.0)**: Remote code execution, authentication bypass
- **HIGH (7.0-8.9)**: SQL injection, privilege escalation
- **MEDIUM (4.0-6.9)**: XSS, information disclosure
- **LOW (0.1-3.9)**: Minor information leaks, DoS with local access

Response times:
- **CRITICAL**: Fix within 24-48 hours
- **HIGH**: Fix within 7 days
- **MEDIUM**: Fix within 30 days
- **LOW**: Fix in next planned release

## Security Updates

Security updates will be released as **patch versions** (e.g., 1.0.1, 1.1.1) and will be backward compatible.

Subscribe to security advisories:
1. Watch the repository on GitHub
2. Enable notifications for "Security alerts"
3. Check the CHANGELOG.md regularly

## Contact

For security issues, contact:
- **Email**: [Add maintainer email]
- **GitHub**: Create a security advisory (preferred)

## Credits

We would like to thank the following security researchers for responsibly disclosing vulnerabilities:

- (None yet)

---

**Last Updated**: 2025-11-17
