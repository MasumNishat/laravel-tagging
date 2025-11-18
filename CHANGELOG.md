# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

#### Critical Documentation & Migration Fix (2025-11-18)
- **Fixed missing migration** in service provider - `add_improvements_to_tagging_tables.php` was not being published
- **Fixed misleading documentation** - All examples now show full namespace requirement
  - Updated Quick Start to use `\App\Models\Equipment::class` instead of `Equipment::class`
  - Added clear warning that `model` field requires fully qualified class name
  - Fixed all code examples throughout README
- **Added comprehensive Troubleshooting section** with common issues and solutions
  - Tags not generated automatically
  - Configuration not found errors
  - Duplicate tag issues
  - Sequential tag counter reset
  - N+1 query warnings
- **Automatic tag generation works correctly** - bootTagable() method properly registers event listeners

### Added

#### Phase 4: New Features (2025-11-17)
- **Events System**: Comprehensive event system for extensibility
  - `TagCreated` - Dispatched when a tag is generated
  - `TagUpdated` - Dispatched when a tag value changes
  - `TagDeleted` - Dispatched when a tag is removed
  - `TagGenerationFailed` - Dispatched when tag generation fails
- **Bulk Operations**: New API endpoints for efficient bulk operations
  - `POST /api/tags/bulk/regenerate` - Regenerate multiple tags at once
  - `POST /api/tags/bulk/delete` - Delete multiple tags at once
- **Enhanced Logging**: Comprehensive logging throughout all operations
  - Success operations logging with context
  - Error operations logging with detailed error info
  - Audit trail capabilities

#### Phase 3: Code Quality (2025-11-17)
- **Custom Exception Classes**: Specific exception hierarchy for better error handling
  - `TaggingException` - Base exception class
  - `TagGenerationException` - Tag generation failures with factory methods
  - `DuplicateTagException` - Duplicate tag attempts with factory methods
  - `InvalidTagFormatException` - Format validation failures with factory methods
- **Input Validation & Sanitization**
  - Search input sanitization to prevent SQL wildcards abuse
  - Length limits on all string inputs
  - Character whitelisting for prefixes (alphanumeric, dash, underscore only)
  - Pagination limits to prevent memory exhaustion
- **Security Improvements**
  - Secure error messages (hide details in production)
  - Laravel Rule::unique() instead of string concatenation
  - Proper validation rules with max lengths

#### Phase 2: Performance Improvements (2025-11-17)
- **Caching System**: Configurable caching for TagConfig lookups
  - Cache enabled/disabled via config
  - Configurable TTL (default 3600 seconds)
  - Automatic cache invalidation on config updates
  - Custom cache driver support
- **Query Optimization**
  - Fixed N+1 query problem with relationship loading detection
  - Debug warnings for N+1 queries in development
  - Eager loading support for tag relationships
- **Configuration Options**
  - `cache.enabled` - Enable/disable caching
  - `cache.ttl` - Cache time-to-live
  - `cache.driver` - Custom cache driver
  - `performance.max_retries` - Maximum retries for concurrent operations
  - `performance.lock_timeout` - Database lock timeout
  - `performance.debug_n_plus_one` - Debug N+1 query warnings

#### Phase 1: Critical Fixes (2025-11-17)
- **Database Improvements**
  - Unique constraint on `(taggable_type, taggable_id)` to prevent duplicate tags
  - Index on `tags.value` column for faster searches
  - `current_number` column in tag_configs for atomic increments
  - `padding_length` column in tag_configs for configurable padding
- **Race Condition Fix**: Database-level pessimistic locking (SELECT FOR UPDATE)
  - Atomic counter increments
  - Retry logic with exponential backoff
  - Configurable max retries
- **Comprehensive Test Suite**
  - Unit tests for Tag and TagConfig models
  - Feature tests for tag generation and caching
  - Test fixtures (Equipment, Brand models)
  - Database migration tests

### Changed

#### Phase 3 & 4: Error Handling (2025-11-17)
- **TagConfigController**: Enhanced error handling
  - Specific exception catching (QueryException vs generic Exception)
  - Proper HTTP status codes (422 for validation, 404 for not found, etc.)
  - User-friendly error messages
  - Context-aware logging on all operations
- **TagController**: Enhanced error handling
  - Specific exception catching with proper responses
  - Logging on all operations (index, show, barcode, bulk operations)
  - Transaction support for bulk operations

#### Phase 2: Performance (2025-11-17)
- **Sequential Tag Generation**: Now uses atomic database counter instead of loading all tags
- **TagConfig Lookup**: Now uses cached lookups via `TagConfig::forModel()`
- **Tag Accessor**: Now checks for eager-loaded relationships before querying

#### Phase 1: Database Schema (2025-11-17)
- Migration added: `add_improvements_to_tagging_tables.php`
  - Adds unique constraint on tags table
  - Adds value index on tags table
  - Adds current_number and padding_length columns to tag_configs table

### Fixed

#### Phase 1 & 2 (2025-11-17)
- **Race Conditions**: Fixed concurrent tag generation creating duplicate numbers
- **N+1 Queries**: Fixed tag attribute accessor causing N+1 queries
- **Performance**: Fixed inefficient tag number calculation loading all tags into memory
- **Database Integrity**: Fixed missing unique constraint allowing duplicate tags

### Deprecated

- None yet

### Removed

- None yet

### Security

#### Phase 3 (2025-11-17)
- **Input Sanitization**: Added wildcard escaping in search inputs
- **Validation**: Added length limits and character whitelisting
- **Error Messages**: Hide sensitive error details in production environment
- **SQL Injection Prevention**: Use Laravel's Rule::unique() instead of string concatenation

## [1.0.0] - Initial Release

### Added
- Automatic tag generation on model save
- Multiple tag formats: sequential, random, and branch-based
- Polymorphic relationships - tag any model
- Barcode generation (CODE_128, QR Code, etc.)
- Print-ready barcode labels
- RESTful API for tag and configuration management
- Customizable table names and prefixes
- Tag configurations CRUD operations
- Tag listing and searching
- Barcode generation in multiple formats (SVG, PNG, HTML, Base64)
- Batch barcode generation
- Print labels functionality
- Meta endpoints for available models and number formats

### Features
- `Tagable` trait for easy model integration
- Tag configuration management
- Automatic tag generation strategies
- Barcode generation with multiple format support
- API routes for tags and configurations
- Configurable middleware and route prefixes

---

## Upgrade Guide

### Upgrading to Latest Version (with Phases 1-4)

1. **Run the new migration**:
   ```bash
   php artisan migrate
   ```
   This adds:
   - Unique constraint on tags
   - Index on tags.value
   - current_number and padding_length columns to tag_configs

2. **Publish updated configuration** (optional):
   ```bash
   php artisan vendor:publish --tag=tagging-config --force
   ```
   Review new configuration options:
   - `cache` settings
   - `performance` settings

3. **Update your event listeners** (optional):
   If you want to listen to tag events:
   ```php
   // In EventServiceProvider
   use Masum\Tagging\Events\TagCreated;

   protected $listen = [
       TagCreated::class => [
           YourTagCreatedListener::class,
       ],
   ];
   ```

4. **Update exception handling** (optional):
   You can now catch specific exceptions:
   ```php
   use Masum\Tagging\Exceptions\TagGenerationException;

   try {
       $model = Equipment::create(['name' => 'Router']);
   } catch (TagGenerationException $e) {
       // Handle tag generation failure
   }
   ```

5. **No breaking changes** - All changes are backward compatible!

---

## Version History

- **1.1.0** (Unreleased) - Phase 1-4 improvements
- **1.0.0** - Initial release

---

## Links

- [GitHub Repository](https://github.com/MasumNishat/laravel-tagging)
- [Issue Tracker](https://github.com/MasumNishat/laravel-tagging/issues)
- [Documentation](https://github.com/MasumNishat/laravel-tagging#readme)
