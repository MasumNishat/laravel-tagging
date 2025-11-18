# Laravel Tagging

<p align="center">
  <img src="https://img.shields.io/packagist/v/masum/laravel-tagging.svg?style=flat-square" alt="Latest Version">
  <img src="https://img.shields.io/packagist/dt/masum/laravel-tagging.svg?style=flat-square" alt="Total Downloads">
  <img src="https://img.shields.io/packagist/l/masum/laravel-tagging.svg?style=flat-square" alt="License">
  <img src="https://img.shields.io/packagist/php-v/masum/laravel-tagging.svg?style=flat-square" alt="PHP Version">
  <img src="https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012-orange.svg?style=flat-square" alt="Laravel Version">
</p>

<p align="center">
  <strong>A comprehensive Laravel package for automatic tag generation and management with barcode support, events, and performance optimizations.</strong>
</p>

---

## Overview

Laravel Tagging is a powerful, production-ready package that provides **automatic tag generation** and management for any Eloquent model. Perfect for inventory systems, asset tracking, equipment management, and any application requiring unique identifiers with barcode support.

### Why Laravel Tagging?

- 🏷️ **Automatic Tag Generation** - Tags are generated automatically when models are created
- 🔢 **Multiple Formats** - Sequential (`EQ-001`), Random (`EQ-1698765432`), Branch-based (`SW-001-5`)
- 📊 **Barcode Support** - Generate CODE_128, QR codes, and more formats for physical labels
- 🖨️ **Print Labels** - Print-ready barcode labels for batch printing
- ⚡ **High Performance** - Race condition protection, caching, query optimization
- 🔔 **Event System** - Hook into tag operations for webhooks, audit trails, notifications
- 🔄 **Bulk Operations** - Regenerate or delete multiple tags efficiently
- 🛡️ **Production Ready** - Comprehensive tests, security hardening, error handling
- 📱 **RESTful API** - Complete API for frontend/mobile integration
- 🎨 **Polymorphic** - Tag any Eloquent model with a single trait

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Testing with Tinker](#testing-with-tinker)
- [Features](#features)
  - [Tag Generation Formats](#tag-generation-formats)
  - [Barcode Generation](#barcode-generation)
  - [Events & Webhooks](#events--webhooks)
  - [Bulk Operations](#bulk-operations)
  - [RESTful API](#restful-api)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Basic Usage](#basic-usage)
  - [Advanced Features](#advanced-features)
  - [API Integration](#api-integration)
- [Performance](#performance)
- [Security](#security)
- [Testing](#testing)
- [Documentation](#documentation)
- [Troubleshooting](#troubleshooting)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)

---

## Requirements

- **PHP:** 8.1, 8.2, or 8.3
- **Laravel:** 10.x, 11.x, or 12.x
- **Database:** MySQL 5.7+, PostgreSQL 10+, SQLite 3.8+, SQL Server 2017+

---

## Installation

Install the package via Composer:

```bash
composer require masum/laravel-tagging
```

Publish and run migrations:

```bash
php artisan vendor:publish --tag=tagging-migrations
php artisan migrate
```

*Optional:* Publish the configuration file:

```bash
php artisan vendor:publish --tag=tagging-config
```

---

## Quick Start

### 1. Add the Trait to Your Model

```php
use Masum\Tagging\Traits\Tagable;

class Equipment extends Model
{
    use Tagable;

    // Required: Define display name for the model
    const TAGABLE = 'Equipment::Generic';

    protected $fillable = ['name', 'description'];
}
```

### 2. Create a Tag Configuration

```php
use Masum\Tagging\Models\TagConfig;

TagConfig::create([
    'model' => \App\Models\Equipment::class,  // Full namespace required
    'prefix' => 'EQ',
    'separator' => '-',
    'number_format' => 'sequential',  // or 'random', 'branch_based'
    'auto_generate' => true,
]);
```

**Important:** The `model` field requires the **fully qualified class name** (e.g., `\App\Models\Equipment::class` or `'App\\Models\\Equipment'`).

### 3. Create Models - Tags Generated Automatically!

```php
$equipment = Equipment::create(['name' => 'Cisco Router']);

echo $equipment->tag;  // Output: EQ-001

$router2 = Equipment::create(['name' => 'TP-Link Switch']);
echo $router2->tag;    // Output: EQ-002
```

**That's it!** Tags are now automatically generated for all Equipment models. 🎉

---

## Testing with Tinker

You can quickly test the package using Laravel Tinker. Here's a complete walkthrough:

### Step 1: Start Tinker

```bash
php artisan tinker
```

### Step 2: Create Tag Configuration

```php
use Masum\Tagging\Models\TagConfig;

TagConfig::create([
    'model' => \App\Models\Equipment::class,  // Full namespace required!
    'prefix' => 'EQ',
    'separator' => '-',
    'number_format' => 'sequential',
    'auto_generate' => true,
    'padding_length' => 3,
    'description' => 'Equipment tags'
]);
```

**Expected Output:**
```
=> Masum\Tagging\Models\TagConfig {#xxxx
     id: 1,
     model: "App\\Models\\Equipment",
     prefix: "EQ",
     separator: "-",
     number_format: "sequential",
     auto_generate: 1,
     ...
   }
```

### Step 3: Create Equipment and See Tags Auto-Generate

```php
use App\Models\Equipment;

$eq1 = Equipment::create(['name' => 'Cisco Router']);
echo $eq1->tag;  // EQ-001

$eq2 = Equipment::create(['name' => 'TP-Link Switch']);
echo $eq2->tag;  // EQ-002

$eq3 = Equipment::create(['name' => 'Dell Server']);
echo $eq3->tag;  // EQ-003
```

### Step 4: Verify Tags in Database

```php
use Masum\Tagging\Models\Tag;

// Get all tags
Tag::all();

// Count tags
Tag::count();  // 3

// View tag details
$tag = Tag::first();
echo "Tag: {$tag->value}\n";
echo "Type: {$tag->taggable_type}\n";
echo "ID: {$tag->taggable_id}\n";
```

### Step 5: Test Tag Search

```php
// Find equipment by tag
$equipment = Equipment::byTag('EQ-001')->first();
echo $equipment->name;  // Cisco Router

// Search with pattern
Equipment::byTag('EQ-00%')->get();  // Returns all matching equipment
```

### Step 6: Test Eager Loading

```php
// Load all equipment with tags (prevents N+1 queries)
$allEquipment = Equipment::with('tag')->get();

foreach ($allEquipment as $eq) {
    echo "{$eq->name} -> {$eq->tag}\n";
}

// Output:
// Cisco Router -> EQ-001
// TP-Link Switch -> EQ-002
// Dell Server -> EQ-003
```

### Step 7: Test Tag Deletion

```php
// When you delete equipment, tags are automatically deleted
$eq = Equipment::find(1);
$tagValue = $eq->tag;
$eq->delete();

// Verify tag was deleted
Tag::where('value', $tagValue)->first();  // null
```

### Quick Verification Script

Copy and paste this into Tinker for a complete test:

```php
use App\Models\Equipment;
use Masum\Tagging\Models\Tag;
use Masum\Tagging\Models\TagConfig;

echo "=== Laravel Tagging Quick Test ===\n\n";

// Create config if not exists
if (!TagConfig::where('model', \App\Models\Equipment::class)->exists()) {
    TagConfig::create([
        'model' => \App\Models\Equipment::class,
        'prefix' => 'EQ',
        'separator' => '-',
        'number_format' => 'sequential',
        'auto_generate' => true,
    ]);
    echo "✓ Config created\n";
}

// Create test equipment
$eq = Equipment::create(['name' => 'Test Item ' . time()]);
echo "✓ Equipment created: ID {$eq->id}\n";

// Check tag
if ($eq->tag) {
    echo "✓ Tag generated: {$eq->tag}\n";
} else {
    echo "✗ Tag NOT generated!\n";
}

// Verify in database
$tag = Tag::where('taggable_type', \App\Models\Equipment::class)
    ->where('taggable_id', $eq->id)
    ->first();

if ($tag) {
    echo "✓ Tag in database: {$tag->value}\n";
} else {
    echo "✗ Tag NOT in database!\n";
}

// Test search
$found = Equipment::byTag($eq->tag)->first();
if ($found && $found->id === $eq->id) {
    echo "✓ Tag search working\n";
} else {
    echo "✗ Tag search failed\n";
}

echo "\n=== All Tests Passed! ===\n";
```

---

## Features

### ✨ Core Features

| Feature | Description |
|---------|-------------|
| **Automatic Generation** | Tags generated on model creation |
| **Multiple Formats** | Sequential, Random, Branch-based |
| **Polymorphic** | Tag any Eloquent model |
| **Barcode Support** | CODE_128, QR, EAN, UPC, and more |
| **Print Labels** | Print-ready barcode labels |
| **Events System** | 4 events for extensibility |
| **Bulk Operations** | Efficient batch processing |
| **RESTful API** | Complete API endpoints |
| **Caching** | Performance optimizations |
| **Race Protection** | Concurrent tag generation safe |
| **Security** | Input validation, SQL injection prevention |
| **Exceptions** | Specific exception classes |
| **N+1 Prevention** | Query optimization |
| **Comprehensive Tests** | Unit and feature tests included |

### 🔢 Tag Generation Formats

#### Sequential Tags
Perfect for inventory systems requiring ordered numbering:
```php
EQ-001, EQ-002, EQ-003, ...
```

#### Random Tags
Great for high-concurrency systems:
```php
EQ-1698765432, EQ-1698765499, ...
```

#### Branch-Based Tags
Ideal for multi-location tracking:
```php
SW-001-5, SW-002-5, SW-001-7
// Format: {PREFIX}-{NUMBER}-{BRANCH_ID}
```

### 📊 Barcode Generation

Generate barcodes in multiple formats for physical tagging:

```php
// In your code
$tag = Tag::find(1);
$barcode = $tag->generateBarcodeSVG();  // SVG format
$png = $tag->generateBarcodePNG();      // PNG format
$base64 = $tag->getBarcodeBase64();     // Base64 data URL
```

**Via API:**
```http
GET /api/tags/1/barcode?format=svg&width_factor=2&height=30
POST /api/tags/batch-barcodes  # Generate multiple barcodes
GET /api/tags/print/labels      # Print-ready labels
```

**Supported Formats:** CODE_128, CODE_39, EAN_13, UPC, QR_CODE, and more

### 🔔 Events & Webhooks

Hook into tag lifecycle for custom logic:

```php
use Masum\Tagging\Events\{TagCreated, TagUpdated, TagDeleted, TagGenerationFailed};

// Send webhook when tag is created
Event::listen(TagCreated::class, function ($event) {
    Http::post('https://api.example.com/webhooks/tag-created', [
        'tag' => $event->tag->value,
        'model' => get_class($event->taggable),
    ]);
});

// Log tag updates to audit trail
Event::listen(TagUpdated::class, function ($event) {
    AuditLog::create([
        'action' => 'tag_updated',
        'old_value' => $event->oldValue,
        'new_value' => $event->tag->value,
    ]);
});

// Alert on generation failures
Event::listen(TagGenerationFailed::class, function ($event) {
    Mail::to('admin@example.com')->send(new TagFailedAlert($event));
});
```

### 🔄 Bulk Operations

Efficient batch processing for large datasets:

**Bulk Regenerate:**
```http
POST /api/tags/bulk/regenerate
{
  "tag_ids": [1, 2, 3, 4, 5]
}
```

**Bulk Delete:**
```http
POST /api/tags/bulk/delete
{
  "tag_ids": [10, 11, 12]
}
```

**Features:**
- Database transactions for consistency
- Individual error handling
- Detailed success/failure reporting
- Automatic logging

### 📱 RESTful API

Complete API for frontend/mobile apps:

**Tag Configurations:**
- `GET /api/tag-configs` - List configurations
- `POST /api/tag-configs` - Create configuration
- `PUT /api/tag-configs/{id}` - Update configuration
- `DELETE /api/tag-configs/{id}` - Delete configuration

**Tags:**
- `GET /api/tags` - List all tags
- `GET /api/tags/{id}` - Get specific tag
- `GET /api/tags/{id}/barcode` - Generate barcode
- `POST /api/tags/batch-barcodes` - Batch barcodes
- `GET /api/tags/print/labels` - Print labels

**Meta Endpoints:**
- `GET /api/tag-configs/meta/number-formats` - Available formats
- `GET /api/tag-configs/meta/available-models` - Taggable models
- `GET /api/tags/meta/barcode-types` - Barcode types

**Full OpenAPI 3.0 specification available in `docs/openapi.yaml`**

---

## Configuration

The package is highly configurable. Publish the config file:

```bash
php artisan vendor:publish --tag=tagging-config
```

### Key Configuration Options

```php
return [
    // Database table names
    'tables' => [
        'tags' => 'tags',
        'tag_configs' => 'tag_configs',
    ],

    // Table prefix
    'table_prefix' => env('TAGGING_TABLE_PREFIX', 'tagging_'),

    // Fallback prefix when no config exists
    'fallback_prefix' => env('TAGGING_FALLBACK_PREFIX', 'TAG'),

    // Default values
    'defaults' => [
        'separator' => '-',
        'number_format' => 'sequential',
        'auto_generate' => true,
    ],

    // Caching configuration
    'cache' => [
        'enabled' => env('TAGGING_CACHE_ENABLED', true),
        'ttl' => env('TAGGING_CACHE_TTL', 3600),
        'driver' => env('TAGGING_CACHE_DRIVER', null),
    ],

    // Performance settings
    'performance' => [
        'max_retries' => env('TAGGING_MAX_RETRIES', 3),
        'lock_timeout' => env('TAGGING_LOCK_TIMEOUT', 10),
        'debug_n_plus_one' => env('TAGGING_DEBUG_N_PLUS_ONE', true),
    ],

    // API Routes
    'routes' => [
        'enabled' => env('TAGGING_ROUTES_ENABLED', true),
        'prefix' => 'api/tag-configs',
        'middleware' => ['api'],  // Add 'auth:sanctum' for authentication
    ],
];
```

### Environment Variables

```env
# Caching
TAGGING_CACHE_ENABLED=true
TAGGING_CACHE_TTL=3600

# Performance
TAGGING_MAX_RETRIES=3
TAGGING_LOCK_TIMEOUT=10
TAGGING_DEBUG_N_PLUS_ONE=true

# API
TAGGING_ROUTES_ENABLED=true

# Custom Settings
TAGGING_FALLBACK_PREFIX=TAG
```

---

## Usage

### Basic Usage

#### Accessing Tags

```php
$equipment = Equipment::find(1);

// Get tag value
echo $equipment->tag;  // EQ-001

// Get tag model
$tagModel = $equipment->tag();

// Get tag configuration
$config = $equipment->tag_config;

// Ensure tag exists (generate if missing)
$equipment->ensureTag();

// Generate next tag without saving
$nextTag = $equipment->generateNextTag();
```

#### Manual Tag Management

```php
// Set custom tag
$equipment->tag = 'CUSTOM-001';

// Remove tag
$equipment->tag = null;
```

#### Querying by Tags

```php
use Masum\Tagging\Models\Tag;

// Find model by tag value
$tag = Tag::where('value', 'EQ-001')->first();
$equipment = $tag->taggable;

// Get all tags for a model type
$equipmentTags = Tag::where('taggable_type', \App\Models\Equipment::class)->get();
```

### Advanced Features

#### Custom Print Labels

Customize what appears on printed labels:

```php
class Brand extends Model
{
    use Tagable;

    const TAGABLE = 'Brand';
    const TAG_LABEL = 'Brand: {name}';  // Variable interpolation

    protected $fillable = ['name'];
}
```

Label output:
```
BRD-001
[BARCODE]
Brand: Cisco
```

#### Exception Handling

```php
use Masum\Tagging\Exceptions\{TagGenerationException, DuplicateTagException};

try {
    $equipment = Equipment::create(['name' => 'Router']);
} catch (TagGenerationException $e) {
    Log::error('Tag generation failed', ['error' => $e->getMessage()]);
    // Assign manual tag or handle error
} catch (DuplicateTagException $e) {
    // Handle duplicate tag scenario
}
```

#### Custom Tag Generation Logic

Override generation methods in your model:

```php
class Equipment extends Model
{
    use Tagable;

    protected function generateSequentialTag(TagConfig $tagConfig): string
    {
        // Custom logic here
        return parent::generateSequentialTag($tagConfig);
    }
}
```

### API Integration

#### JavaScript/TypeScript Example

```javascript
// Fetch available models for dropdown
const models = await fetch('/api/tag-configs/meta/available-models')
  .then(res => res.json());

// Create tag configuration
const response = await fetch('/api/tag-configs', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    model: 'App\\Models\\Equipment',
    prefix: 'EQ',
    separator: '-',
    number_format: 'sequential',
    auto_generate: true
  })
});

// Bulk regenerate tags
const result = await fetch('/api/tags/bulk/regenerate', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ tag_ids: [1, 2, 3] })
});

// Print labels
window.open('/api/tags/print/labels?tag_ids=1,2,3', '_blank');
```

#### Vue.js Example

```vue
<template>
  <div>
    <img v-for="tag in tags" :key="tag.id" :src="tag.barcode" :alt="tag.value">
  </div>
</template>

<script>
export default {
  data() {
    return { tags: [] }
  },
  async mounted() {
    const response = await fetch('/api/tags/batch-barcodes', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        tag_ids: [1, 2, 3, 4, 5],
        width_factor: 2,
        height: 40
      })
    });
    const data = await response.json();
    this.tags = data.data;
  }
}
</script>
```

---

## Performance

### Avoiding N+1 Queries

**Always use eager loading** when loading multiple models with tags:

```php
// ❌ Bad - Creates N+1 queries
$equipment = Equipment::all();
foreach ($equipment as $item) {
    echo $item->tag;  // Separate query each time!
}

// ✅ Good - Single query for all tags
$equipment = Equipment::with('tag')->get();
foreach ($equipment as $item) {
    echo $item->tag;  // Uses loaded relationship
}
```

The package logs N+1 warnings in debug mode.

### Caching

Tag configurations are automatically cached:

```php
// First call: queries database
$config = TagConfig::forModel(\App\Models\Equipment::class);

// Subsequent calls: uses cache (1 hour default)
$config = TagConfig::forModel(\App\Models\Equipment::class);
```

Cache is automatically invalidated on config updates.

### Race Condition Protection

Sequential tag generation uses pessimistic locking:

```php
// Atomic counter increment with SELECT FOR UPDATE
// Retries up to 3 times with exponential backoff
// Falls back to timestamp-based tags if all retries fail
```

### Performance Targets

- Tag generation: **< 100ms** (99th percentile)
- API responses: **< 200ms** (95th percentile)
- Supports **100+ concurrent** tag generations
- Handles **1M+ tags** per model type

### Database Indexes

Automatically created for optimal performance:
- Composite index on `(taggable_type, taggable_id)`
- Unique constraint on `(taggable_type, taggable_id)`
- Index on `value` column

---

## Security

### Built-in Security Features

✅ **Input Validation** - Length limits, character whitelisting
✅ **SQL Injection Prevention** - Parameterized queries, escaped wildcards
✅ **XSS Prevention** - Output escaping in barcode generation
✅ **Error Handling** - Secure error messages in production
✅ **Rate Limiting** - Configurable via middleware
✅ **CSRF Protection** - Laravel default protection

### Security Best Practices

```php
// 1. Always validate inputs
$validated = $request->validate([
    'name' => 'required|string|max:255',
]);
$equipment = Equipment::create($validated);

// 2. Use authentication middleware
'routes' => [
    'middleware' => ['api', 'auth:sanctum'],
],

// 3. Set APP_DEBUG=false in production
APP_DEBUG=false

// 4. Implement rate limiting
Route::middleware(['throttle:60,1'])->group(function () {
    // Tag routes
});
```

**Full security policy available in [SECURITY.md](SECURITY.md)**

---

## Testing

The package includes a comprehensive test suite:

```bash
# Run all tests
composer test

# Run unit tests
composer test-unit

# Run feature tests
composer test-feature

# Run with coverage
composer test-coverage
```

### Test Coverage

- ✅ Tag generation (all formats)
- ✅ Race condition handling
- ✅ Caching behavior
- ✅ API endpoints
- ✅ Barcode generation
- ✅ Bulk operations
- ✅ Event dispatching
- ✅ Exception handling
- ✅ N+1 query prevention

**Target: 80%+ code coverage**

---

## Documentation

### Available Documentation

- **[README.md](README.md)** - This file (main documentation)
- **[CHANGELOG.md](CHANGELOG.md)** - Version history and upgrade guides
- **[CONTRIBUTING.md](CONTRIBUTING.md)** - Contribution guidelines
- **[SECURITY.md](SECURITY.md)** - Security policy and best practices
- **[CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)** - Community standards
- **[docs/openapi.yaml](docs/openapi.yaml)** - OpenAPI 3.0 specification

### Quick Links

- 📖 [Installation Guide](#installation)
- 🚀 [Quick Start](#quick-start)
- 🔧 [Configuration](#configuration)
- 💻 [Usage Examples](#usage)
- 🎯 [API Reference](docs/openapi.yaml)
- 🐛 [Report Issues](https://github.com/MasumNishat/laravel-tagging/issues)

### Interactive API Documentation

View interactive API docs with Swagger:

```bash
docker run -p 8080:8080 -e SWAGGER_JSON=/docs/openapi.yaml \
  -v $(pwd)/docs:/docs swaggerapi/swagger-ui
```

Access at `http://localhost:8080`

---

## Troubleshooting

### Tags Not Generated Automatically

If tags are not being generated when you create models, check the following:

**1. Model Uses the Trait**
```php
use Masum\Tagging\Traits\Tagable;

class Equipment extends Model
{
    use Tagable;  // ✅ Trait must be present

    const TAGABLE = 'Equipment::Generic';  // ✅ Required constant
}
```

**2. TagConfig Uses Full Namespace**

❌ **Wrong:**
```php
TagConfig::create([
    'model' => Equipment::class,  // Missing namespace!
]);
```

✅ **Correct:**
```php
TagConfig::create([
    'model' => \App\Models\Equipment::class,  // Full namespace required
    // OR
    'model' => 'App\\Models\\Equipment',  // String with escaped backslashes
]);
```

**3. Migrations Are Run**

Make sure you've published and run all migrations:
```bash
php artisan vendor:publish --tag=tagging-migrations
php artisan migrate
```

This will create 3 migration files:
- `create_tags_table.php`
- `create_tag_configs_table.php`
- `add_improvements_to_tagging_tables.php`

**4. TagConfig Exists**

Verify your tag configuration exists:
```php
$config = \Masum\Tagging\Models\TagConfig::where('model', \App\Models\Equipment::class)->first();

if (!$config) {
    echo "No configuration found!";
}
```

**5. Check Logs**

Enable debug mode and check logs for errors:
```env
APP_DEBUG=true
```

Tag generation errors are logged to `storage/logs/laravel.log`.

### Common Issues

**Issue: "No configuration found for model"**

Solution: Create a TagConfig with the correct full namespace:
```php
\Masum\Tagging\Models\TagConfig::create([
    'model' => \App\Models\Equipment::class,  // Must match exactly!
    'prefix' => 'EQ',
    'separator' => '-',
    'number_format' => 'sequential',
]);
```

**Issue: "Duplicate tag errors"**

Solution: The improvements migration adds unique constraints. If you have existing duplicate tags:
```php
// Find duplicates
$duplicates = \Masum\Tagging\Models\Tag::select('taggable_type', 'taggable_id')
    ->groupBy('taggable_type', 'taggable_id')
    ->havingRaw('COUNT(*) > 1')
    ->get();

// Delete duplicates (keeping the first)
foreach ($duplicates as $dup) {
    \Masum\Tagging\Models\Tag::where('taggable_type', $dup->taggable_type)
        ->where('taggable_id', $dup->taggable_id)
        ->orderBy('id')
        ->skip(1)
        ->delete();
}
```

**Issue: "Tags are sequential but starting from wrong number"**

Solution: Reset the counter in tag_configs:
```php
$config = \Masum\Tagging\Models\TagConfig::where('model', \App\Models\Equipment::class)->first();
$config->update(['current_number' => 0]);  // Start from 1
```

**Issue: "N+1 query warnings in logs"**

Solution: Use eager loading:
```php
// ❌ Bad
$equipment = Equipment::all();

// ✅ Good
$equipment = Equipment::with('tag')->get();
```

### Debug Mode

Enable verbose logging to troubleshoot issues:
```env
TAGGING_DEBUG_N_PLUS_ONE=true
APP_DEBUG=true
```

Then check `storage/logs/laravel.log` for detailed error messages.

---

## Changelog

All notable changes are documented in [CHANGELOG.md](CHANGELOG.md).

### Latest Version

**Version 1.1.0** - Current Development

**Added:**
- Events system (TagCreated, TagUpdated, TagDeleted, TagGenerationFailed)
- Bulk operations (regenerate, delete)
- Custom exception classes
- Caching system for TagConfig lookups
- Race condition protection with pessimistic locking
- Comprehensive test suite
- OpenAPI 3.0 specification

**Fixed:**
- Race conditions in sequential tag generation
- N+1 query problems
- Missing database constraints
- SQL injection vulnerabilities in search

**See [CHANGELOG.md](CHANGELOG.md) for complete history**

---

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Quick Contribution Guide

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests (`composer test`)
5. Commit changes (`git commit -m 'Add amazing feature'`)
6. Push to branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

### Development Setup

```bash
# Clone repository
git clone https://github.com/MasumNishat/laravel-tagging.git
cd laravel-tagging

# Install dependencies
composer install

# Run tests
composer test
```

### Code of Conduct

This project adheres to the [Contributor Covenant Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code.

---

## Credits

### Author

- **Masum Nishat** - [GitHub](https://github.com/MasumNishat)

### Dependencies

- [picqer/php-barcode-generator](https://github.com/picqer/php-barcode-generator) - Barcode generation
- [Laravel Framework](https://laravel.com) - The framework we build upon

### Contributors

Thank you to all contributors who have helped make this package better!

---

## License

This package is open-sourced software licensed under the [MIT License](LICENSE).

---

## Support

- 🐛 **Bug Reports:** [GitHub Issues](https://github.com/MasumNishat/laravel-tagging/issues)
- 💬 **Questions:** [GitHub Discussions](https://github.com/MasumNishat/laravel-tagging/discussions)
- 📧 **Security Issues:** See [SECURITY.md](SECURITY.md)

---

<p align="center">
  <strong>Made with ❤️ for the Laravel community</strong>
</p>

<p align="center">
  If this package helped you, please consider giving it a ⭐ on <a href="https://github.com/MasumNishat/laravel-tagging">GitHub</a>!
</p>
