# Contributing to Laravel Tagging

First off, thank you for considering contributing to Laravel Tagging! It's people like you that make this package better for everyone.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [How Can I Contribute?](#how-can-i-contribute)
- [Development Setup](#development-setup)
- [Pull Request Process](#pull-request-process)
- [Coding Standards](#coding-standards)
- [Testing Guidelines](#testing-guidelines)
- [Commit Message Guidelines](#commit-message-guidelines)
- [Reporting Bugs](#reporting-bugs)
- [Suggesting Enhancements](#suggesting-enhancements)

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to the project maintainers.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates. When creating a bug report, include as many details as possible:

- **Use a clear and descriptive title**
- **Describe the exact steps to reproduce the problem**
- **Provide specific examples** - Include code snippets, screenshots, or error messages
- **Describe the behavior you observed** and what you expected to see
- **Include environment details**:
  - Laravel version
  - PHP version
  - Package version
  - Database type and version

**Bug Report Template:**

```markdown
## Bug Description
A clear description of what the bug is.

## Steps to Reproduce
1. Step one
2. Step two
3. ...

## Expected Behavior
What you expected to happen.

## Actual Behavior
What actually happened.

## Environment
- Laravel Version: X.X.X
- PHP Version: X.X.X
- Package Version: X.X.X
- Database: MySQL/PostgreSQL/SQLite X.X.X

## Additional Context
Any other information, configuration, stack traces, etc.
```

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion:

- **Use a clear and descriptive title**
- **Provide a detailed description** of the suggested enhancement
- **Explain why this enhancement would be useful**
- **List some examples** of how it would be used
- **Consider backward compatibility** - will this break existing code?

**Enhancement Template:**

```markdown
## Enhancement Description
A clear description of the enhancement.

## Motivation
Why is this enhancement needed? What problem does it solve?

## Proposed Solution
How should this be implemented?

## Example Usage
```php
// Show how the feature would be used
```

## Alternatives Considered
What other approaches did you consider?

## Backward Compatibility
Will this break existing code? If yes, how can we mitigate?
```

### Pull Requests

We actively welcome your pull requests:

1. Fork the repo and create your branch from `main`
2. If you've added code that should be tested, add tests
3. If you've changed APIs, update the documentation
4. Ensure the test suite passes
5. Make sure your code follows our coding standards
6. Submit your pull request!

## Development Setup

### Prerequisites

- PHP 8.1, 8.2, or 8.3
- Composer
- SQLite (for testing)

### Installation

1. **Fork and clone the repository**
   ```bash
   git clone https://github.com/YOUR_USERNAME/laravel-tagging.git
   cd laravel-tagging
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Run tests to verify setup**
   ```bash
   composer test
   ```

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage
composer test:coverage

# Run specific test file
./vendor/bin/phpunit tests/Unit/TagTest.php

# Run tests with filter
./vendor/bin/phpunit --filter testTagGeneration
```

### Code Quality Tools

```bash
# Run PHPStan (static analysis)
composer analyse

# Run PHP-CS-Fixer (code style)
composer format

# Check code style without fixing
composer format:check
```

## Pull Request Process

1. **Update the CHANGELOG.md** with details of your changes under the "Unreleased" section

2. **Update documentation** if you've changed:
   - Configuration options
   - API endpoints
   - Public methods
   - Database schema

3. **Ensure all tests pass** and add new tests for your changes

4. **Follow the coding standards** - Run `composer format` before committing

5. **Write clear commit messages** - Follow our commit message guidelines

6. **Update the README.md** if needed with details of new features or changes

7. **Request review** from maintainers - Your PR will be reviewed promptly

8. **Be patient and responsive** - We may request changes or ask questions

### PR Title Format

Use conventional commit format for PR titles:

```
feat: Add bulk tag export feature
fix: Resolve race condition in tag generation
docs: Update installation instructions
test: Add tests for barcode generation
refactor: Improve query performance in TagController
```

## Coding Standards

This project follows **PSR-12** coding standard and Laravel best practices.

### PHP Code Style

- Use **PSR-12** coding standard
- Use **type hints** for all parameters and return types
- Add **DocBlocks** for all public methods
- Use **meaningful variable names**
- Follow **SOLID principles**

**Good Example:**

```php
/**
 * Generate a sequential tag for the model.
 *
 * @param TagConfig $tagConfig The tag configuration
 * @return string The generated tag value
 * @throws TagGenerationException If tag generation fails
 */
protected function generateSequentialTag(TagConfig $tagConfig): string
{
    $maxRetries = config('tagging.performance.max_retries', 3);
    $attempt = 0;

    while ($attempt < $maxRetries) {
        try {
            return DB::transaction(function () use ($tagConfig) {
                // Implementation here
            });
        } catch (\Exception $e) {
            $attempt++;
            if ($attempt >= $maxRetries) {
                throw TagGenerationException::concurrencyFailure(
                    get_class($this),
                    $maxRetries
                );
            }
        }
    }
}
```

**Bad Example:**

```php
// No type hints, no docblock, unclear variable names
protected function genSeqTag($tc)
{
    $r = 3; // What is 'r'?
    $a = 0;
    while ($a < $r) {
        try {
            return DB::transaction(function () use ($tc) {
                // ...
            });
        } catch (\Exception $e) {
            $a++;
        }
    }
}
```

### Laravel Best Practices

- Use **Eloquent** relationships properly
- Use **Query Builder** for complex queries
- Use **dependency injection** instead of facades in classes
- Use **config()** helper for configuration
- Use **trans()** or **__()** for translatable strings (if applicable)
- Follow **RESTful** conventions for API endpoints

### Database Guidelines

- Always create **migrations** for schema changes
- Use **indexes** for frequently queried columns
- Use **foreign keys** for relationships
- Use **transactions** for multi-step operations
- Use **pessimistic locking** for concurrent operations

### Security Guidelines

- **Validate all inputs** using Laravel validation
- **Sanitize user inputs** to prevent SQL injection
- **Escape output** to prevent XSS
- **Use prepared statements** (Eloquent does this automatically)
- **Hide error details** in production (`config('app.debug')`)
- **Log security events** appropriately

## Testing Guidelines

### Test Coverage

- Aim for **80%+ code coverage**
- Test **all public methods**
- Test **edge cases** and boundary conditions
- Test **error scenarios**
- Test **concurrency** for race conditions

### Test Structure

Follow **Arrange-Act-Assert** pattern:

```php
public function test_sequential_tag_generation_increments_correctly(): void
{
    // Arrange
    $config = TagConfig::create([
        'model' => Equipment::class,
        'prefix' => 'EQ',
        'separator' => '-',
        'number_format' => 'sequential',
        'current_number' => 5,
    ]);

    // Act
    $equipment = Equipment::create(['name' => 'Router']);

    // Assert
    $this->assertEquals('EQ-006', $equipment->tag);
    $this->assertDatabaseHas('tags', [
        'value' => 'EQ-006',
        'taggable_type' => Equipment::class,
        'taggable_id' => $equipment->id,
    ]);
}
```

### Test Naming

- Use descriptive test method names
- Start with `test_` prefix
- Use underscores to separate words
- Describe what is being tested and expected outcome

**Good:**
- `test_sequential_tag_generation_increments_correctly`
- `test_bulk_regenerate_handles_failures_gracefully`
- `test_cache_is_invalidated_on_config_update`

**Bad:**
- `testTag`
- `test1`
- `testStuff`

## Commit Message Guidelines

We follow the **Conventional Commits** specification.

### Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Type

- **feat**: New feature
- **fix**: Bug fix
- **docs**: Documentation only changes
- **style**: Code style changes (formatting, missing semicolons, etc.)
- **refactor**: Code change that neither fixes a bug nor adds a feature
- **perf**: Performance improvement
- **test**: Adding or updating tests
- **chore**: Changes to build process or auxiliary tools

### Scope (optional)

The scope should specify the place of the commit change:
- `tag-generation`
- `barcode`
- `api`
- `cache`
- `config`
- `migration`
- `tests`

### Subject

- Use imperative, present tense: "change" not "changed" nor "changes"
- Don't capitalize first letter
- No period (.) at the end
- Limit to 50 characters

### Examples

```
feat(api): add bulk tag regeneration endpoint

Add POST /api/tags/bulk/regenerate endpoint that allows
regenerating multiple tags at once with transaction support.

Closes #123
```

```
fix(tag-generation): resolve race condition in sequential tags

Use pessimistic locking (SELECT FOR UPDATE) to prevent
duplicate tag numbers in high-concurrency scenarios.

Fixes #456
```

```
docs(readme): update installation instructions

Add instructions for publishing config and running migrations.
```

## Code Review Process

All submissions require review. We use GitHub pull requests for this purpose.

### What We Look For

- **Code quality** - Follows coding standards
- **Tests** - Adequate test coverage
- **Documentation** - Updated if needed
- **Backward compatibility** - No breaking changes without major version bump
- **Performance** - No performance regressions
- **Security** - No security vulnerabilities

### Review Timeline

- We aim to review PRs within **1 week**
- Small PRs are reviewed faster than large ones
- Breaking changes require more discussion

## Questions?

Don't hesitate to ask questions! You can:

- Open an issue with the "question" label
- Reach out to maintainers
- Check existing issues and discussions

## License

By contributing, you agree that your contributions will be licensed under the same license as the project (MIT License).

---

**Thank you for contributing to Laravel Tagging!** 🎉
