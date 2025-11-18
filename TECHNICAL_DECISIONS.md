# Technical Decisions & Architecture

This document explains the technical decisions made during the improvement process.

## Race Condition Solution

### Decision: Database-Level Pessimistic Locking

**Options Considered:**
1. Optimistic locking (version column)
2. Pessimistic locking (SELECT FOR UPDATE)
3. Application-level locking (Redis/Memcached)
4. Database counter column with atomic increment
5. Database sequences

**Chosen Solution:** Hybrid approach
- Database pessimistic locking for sequential tag generation
- Atomic counter column in `tag_configs` table
- Retry logic with exponential backoff

**Rationale:**
- Works across all database systems (MySQL, PostgreSQL, SQLite)
- No external dependencies (Redis/Memcached)
- Simple to implement and maintain
- Handles high concurrency well
- Atomic operations prevent race conditions

**Implementation:**
```php
DB::transaction(function () use ($tagConfig) {
    // Lock the row
    $config = TagConfig::where('id', $tagConfig->id)
        ->lockForUpdate()
        ->first();

    // Atomic increment
    $nextNumber = $config->increment('current_number');

    return "{$config->prefix}{$config->separator}" .
           str_pad($nextNumber, $config->padding_length, '0', STR_PAD_LEFT);
});
```

**Trade-offs:**
- Slight performance impact due to locking
- Potential for deadlocks (mitigated with retry logic)
- Requires migration to add counter column

## Caching Strategy

### Decision: Laravel Cache with Model Events

**Options Considered:**
1. No caching
2. In-memory array cache (single request)
3. Laravel cache with manual invalidation
4. Laravel cache with model events
5. Full query result caching

**Chosen Solution:** Laravel cache with automatic invalidation via model events

**Rationale:**
- TagConfig rarely changes
- Significant performance improvement (avoid DB query on every tag generation)
- Automatic cache invalidation ensures consistency
- Works with any Laravel cache driver
- Easy to disable if needed

**Implementation:**
```php
// Cache key: "tag_config:{ModelClass}"
public function getTagConfigAttribute(): ?TagConfig
{
    $cacheKey = 'tag_config:' . static::class;

    return Cache::remember($cacheKey, $ttl, function () {
        return TagConfig::where('model', static::class)->first();
    });
}

// Invalidation on TagConfig save/delete
protected static function booted()
{
    static::saved(function ($config) {
        Cache::forget('tag_config:' . $config->model);
    });
}
```

**Configuration:**
```php
'cache' => [
    'enabled' => env('TAGGING_CACHE_ENABLED', true),
    'ttl' => env('TAGGING_CACHE_TTL', 3600), // 1 hour
    'driver' => env('TAGGING_CACHE_DRIVER', null), // Use default
],
```

## N+1 Query Solution

### Decision: Relationship Eager Loading with Helper

**Problem:**
```php
// Creates N+1 queries
foreach ($models as $model) {
    echo $model->tag; // Separate query each time
}
```

**Solution:**
```php
// Method 1: Manual eager loading
$models = Model::with('tag')->get();
foreach ($models as $model) {
    echo $model->tag; // Uses loaded relationship
}

// Method 2: Use loaded relationship in accessor
public function getTagAttribute(): ?string
{
    // Check if relationship is already loaded
    if ($this->relationLoaded('tag')) {
        return $this->getRelation('tag')?->value;
    }

    // Fall back to query (with deprecation warning in debug mode)
    if (config('app.debug')) {
        logger()->warning('Tag relationship not eager loaded', [
            'model' => static::class,
            'id' => $this->id,
        ]);
    }

    return $this->tag()->first()?->value;
}
```

**Documentation:**
Add clear examples in README about eager loading:
```php
// ❌ Bad - Creates N+1 queries
$equipment = Equipment::all();
foreach ($equipment as $item) {
    echo $item->tag;
}

// ✅ Good - Single query
$equipment = Equipment::with('tag')->all();
foreach ($equipment as $item) {
    echo $item->tag;
}
```

## Database Schema Design

### Tags Table
```php
Schema::create('tagging_tags', function (Blueprint $table) {
    $table->id();
    $table->string('value')->index(); // NEW: Index for searches
    $table->string('taggable_type');
    $table->unsignedBigInteger('taggable_id');

    // Polymorphic index (already exists)
    $table->index(['taggable_type', 'taggable_id']);

    // NEW: Unique constraint
    $table->unique(['taggable_type', 'taggable_id'], 'unique_taggable');

    $table->timestamps();
});
```

### Tag Configs Table
```php
Schema::create('tagging_tag_configs', function (Blueprint $table) {
    $table->id();
    $table->string('prefix', 10);
    $table->string('separator', 5)->default('-');
    $table->enum('number_format', ['sequential', 'branch_based', 'random'])
          ->default('sequential');
    $table->boolean('auto_generate')->default(true);
    $table->text('description')->nullable();
    $table->string('model')->unique();

    // NEW: Performance and configuration columns
    $table->unsignedBigInteger('current_number')->default(0); // Atomic counter
    $table->unsignedTinyInteger('padding_length')->default(3); // Configurable padding

    $table->timestamps();
});
```

## Events Architecture

### Event-Driven Design

**Events to Implement:**
1. `TagCreated` - When a tag is generated
2. `TagUpdated` - When a tag value changes
3. `TagDeleted` - When a tag is removed
4. `TagGenerationFailed` - When tag generation fails

**Benefits:**
- Extensibility - users can add custom logic
- Audit trail - track all tag operations
- Webhooks - notify external systems
- Analytics - track tag usage patterns

**Example Event:**
```php
namespace Masum\Tagging\Events;

class TagCreated
{
    public function __construct(
        public Tag $tag,
        public Model $taggable,
        public ?TagConfig $config = null
    ) {}
}
```

**Dispatching:**
```php
// In Tagable trait
static::saved(function ($model) {
    if (!$existingTag) {
        $tag = Tag::create([...]);

        event(new TagCreated($tag, $model, $model->tag_config));
    }
});
```

**User Implementation:**
```php
// In user's EventServiceProvider
Event::listen(TagCreated::class, function ($event) {
    // Log to audit trail
    AuditLog::create([
        'action' => 'tag_created',
        'tag' => $event->tag->value,
        'model' => get_class($event->taggable),
    ]);

    // Send webhook
    Http::post('https://api.example.com/webhooks', [
        'event' => 'tag.created',
        'tag' => $event->tag->value,
    ]);
});
```

## Error Handling Strategy

### Custom Exceptions

**Exception Hierarchy:**
```
TaggingException (base)
├── TagGenerationException
│   ├── DuplicateTagException
│   ├── InvalidConfigException
│   └── ConcurrencyException
├── TagNotFoundException
└── InvalidTagFormatException
```

**Usage:**
```php
try {
    $tag = $model->generateNextTag();
} catch (InvalidConfigException $e) {
    Log::error('Tag config missing or invalid', [
        'model' => get_class($model),
        'exception' => $e->getMessage(),
    ]);

    // Fallback to default tag
    $tag = $this->generateFallbackTag();
} catch (ConcurrencyException $e) {
    // Retry logic
    return $this->retryTagGeneration($model, $attempts + 1);
}
```

**Controller Error Handling:**
```php
try {
    $tagConfig = TagConfig::create($validated);

    return response()->json([
        'success' => true,
        'data' => $tagConfig
    ], 201);

} catch (QueryException $e) {
    Log::error('Database error creating tag config', [
        'error' => $e->getMessage(),
        'data' => $validated,
    ]);

    return response()->json([
        'success' => false,
        'message' => 'Failed to create tag configuration',
        'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
    ], 500);
}
```

## Testing Strategy

### Test Pyramid

**Unit Tests (60%):**
- Individual methods in isolation
- Tag generation algorithms
- Validation logic
- Helper functions

**Feature Tests (30%):**
- API endpoints
- Complete workflows
- Database interactions
- Cache behavior

**Integration Tests (10%):**
- Multiple components together
- External dependencies
- Performance benchmarks

### Concurrency Testing

**Approach:**
```php
public function test_concurrent_tag_generation_prevents_duplicates()
{
    $iterations = 100;
    $promises = [];

    // Create 100 concurrent requests
    for ($i = 0; $i < $iterations; $i++) {
        $promises[] = async(function () {
            return Equipment::create(['name' => 'Test'])->tag;
        });
    }

    $tags = await($promises);

    // Assert all tags are unique
    $this->assertCount($iterations, array_unique($tags));
}
```

### Database Testing

**Use Database Transactions:**
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_generation()
    {
        // Test automatically rolls back
    }
}
```

## Performance Targets

### Benchmarks

| Operation | Target | Acceptable |
|-----------|--------|------------|
| Single tag generation | < 50ms | < 100ms |
| Batch tag generation (100) | < 2s | < 5s |
| API response (list) | < 100ms | < 200ms |
| Barcode generation | < 50ms | < 100ms |
| Concurrent tags (100) | < 5s | < 10s |

### Optimization Techniques

1. **Database Indexes:** On frequently queried columns
2. **Caching:** For rarely-changing config data
3. **Eager Loading:** To prevent N+1 queries
4. **Atomic Operations:** For counter increments
5. **Query Optimization:** Use SQL aggregates instead of loading all data

## Backward Compatibility

### Migration Strategy

**For Breaking Changes:**
1. Add new columns with default values
2. Keep old behavior as default
3. Add config flag to enable new behavior
4. Deprecate old behavior with warnings
5. Remove after major version bump

**For New Features:**
1. All optional with sensible defaults
2. Document in CHANGELOG
3. Add migration guide
4. Test with existing implementations

**Example:**
```php
// Old behavior (still works)
TagConfig::create([
    'model' => Equipment::class,
    'prefix' => 'EQ',
]);

// New behavior (optional)
TagConfig::create([
    'model' => Equipment::class,
    'prefix' => 'EQ',
    'padding_length' => 5, // NEW: Optional
    'cache_enabled' => true, // NEW: Optional
]);
```

## Security Considerations

### Input Validation

**All User Input:**
- Validate type and format
- Sanitize for SQL (Laravel does this)
- Escape for XSS (barcode labels)
- Length limits
- Character whitelist

**Rate Limiting:**
```php
// In RouteServiceProvider or routes
Route::middleware(['throttle:60,1'])->group(function () {
    // Tag API routes
});
```

### Authorization

**Example Middleware:**
```php
// Users can add their own authorization
'middleware' => ['api', 'auth:sanctum', 'can:manage-tags'],
```

## Deployment Strategy

### Version Numbering

Follow Semantic Versioning (semver.org):
- **MAJOR:** Breaking changes
- **MINOR:** New features (backward compatible)
- **PATCH:** Bug fixes (backward compatible)

### Release Process

1. Update CHANGELOG.md
2. Bump version in composer.json
3. Tag in git: `v1.2.0`
4. Create GitHub release
5. Packagist auto-updates
6. Announce in community

---

**Last Updated:** 2025-11-17
**Version:** Pre-1.1.0 (improvements in progress)
