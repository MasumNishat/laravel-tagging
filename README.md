# Laravel Tagging Package

A flexible Laravel package for automatic tag generation and management for any Eloquent model. This package provides polymorphic tagging with multiple tag generation strategies including sequential, random, and branch-based formats.

## Features

- Automatic tag generation on model save
- Multiple tag formats: sequential, random, and branch-based
- Polymorphic relationships - tag any model
- Configurable tag prefixes and separators
- **Barcode generation** - Generate barcodes in multiple formats (CODE_128, QR Code, etc.)
- **Print labels** - Print-ready barcode labels for physical tagging
- **Events system** - Hook into tag operations for custom logic, webhooks, audit trails
- **Bulk operations** - Regenerate or delete multiple tags at once
- **Custom exceptions** - Specific exception classes for better error handling
- **Performance optimizations** - Caching, race condition protection, N+1 query prevention
- **Security hardening** - Input sanitization, secure error messages, SQL injection prevention
- Automatic tag cleanup on model deletion
- Easy-to-use trait-based implementation
- RESTful API for tag and configuration management
- Customizable table names
- Support for Laravel 10.x, 11.x, and 12.x

## Installation

Install the package via composer:

```bash
composer require masum/laravel-tagging
```

Publish the migrations:

```bash
php artisan vendor:publish --tag=tagging-migrations
```

Run the migrations:

```bash
php artisan migrate
```

Optionally, publish the config file:

```bash
php artisan vendor:publish --tag=tagging-config
```

## Usage

### Basic Setup

1. Add the `Tagable` trait to your model:

```php
use Masum\Tagging\Traits\Tagable;

class Equipment extends Model
{
    use Tagable;

    // REQUIRED: Define TAGABLE constant as a string with the model's display name
    // This will be used to identify the model in the UI and API responses
    const TAGABLE = 'Equipment::Generic';
}
```

**Important:** Both the `Tagable` trait and `TAGABLE` constant are **required**. The `TAGABLE` constant must be a string value that represents the human-readable name of your model. This name will appear in dropdowns, API responses, and throughout the management interface.

Examples of TAGABLE values:
```php
const TAGABLE = 'Brand';                    // Simple name
const TAGABLE = 'Equipment::Generic';       // Namespaced name
const TAGABLE = 'Fiber::Cable';            // Category-based name
const TAGABLE = 'Equipment::ONU_ONT';      // Equipment type name
```

2. Create a tag configuration for your model (via code or API):

```php
use Masum\Tagging\Models\TagConfig;

TagConfig::create([
    'model' => \App\Models\Equipment::class,
    'prefix' => 'EQ',
    'separator' => '-',
    'number_format' => 'sequential', // sequential, random, or branch_based
    'auto_generate' => true,
    'description' => 'Equipment tags',
]);
```

Or use the built-in API (see [API Management](#api-management) section below).

### Tag Generation Formats

#### Sequential Tags
Generates tags like: `EQ-001`, `EQ-002`, `EQ-003`

```php
TagConfig::create([
    'model' => \App\Models\Equipment::class,
    'prefix' => 'EQ',
    'separator' => '-',
    'number_format' => 'sequential',
]);
```

#### Random Tags (Timestamp-based)
Generates tags like: `EQ-1698765432`

```php
TagConfig::create([
    'model' => \App\Models\Cable::class,
    'prefix' => 'CB',
    'separator' => '-',
    'number_format' => 'random',
]);
```

#### Branch-Based Tags
Generates tags like: `SW-001-5` (where 5 is the branch_id)

```php
TagConfig::create([
    'model' => \App\Models\Switch::class,
    'prefix' => 'SW',
    'separator' => '-',
    'number_format' => 'branch_based',
]);

// Your model should have a branch_id attribute
$switch = Switch::create([
    'name' => 'Main Switch',
    'branch_id' => 5,
]);
// Tag will be automatically generated: SW-001-5
```

### Accessing Tags

```php
$equipment = Equipment::create(['name' => 'Router']);

// Access the tag value
echo $equipment->tag; // Output: EQ-001

// Access the full tag relationship
$tagModel = $equipment->tag();

// Get tag configuration
$config = $equipment->tag_config;
```

### Manual Tag Management

```php
// Manually set a tag
$equipment->tag = 'CUSTOM-001';

// Ensure a tag exists (generate if missing)
$equipment->ensureTag();

// Generate next tag without saving
$nextTag = $equipment->generateNextTag();

// Remove a tag
$equipment->tag = null;
```

### Automatic Tag Generation

Tags are automatically generated when:
- A new model is created
- An existing model without a tag is saved

Tags are automatically deleted when:
- The model is deleted

## Configuration

The package configuration file (`config/tagging.php`) allows you to customize:

```php
return [
    // Customize table names
    'tables' => [
        'tags' => 'tags',
        'tag_configs' => 'tag_configs',
    ],

    // Fallback prefix when no config exists
    'fallback_prefix' => env('TAGGING_FALLBACK_PREFIX', 'TAG'),

    // Default configuration values
    'defaults' => [
        'separator' => '-',
        'number_format' => 'sequential',
        'auto_generate' => true,
    ],
];
```

## Database Schema

### Tags Table
```
- id
- value (string)
- taggable_type (string)
- taggable_id (bigint)
- timestamps
```

### Tag Configs Table
```
- id
- prefix (string)
- separator (string)
- number_format (enum: sequential, branch_based, random)
- auto_generate (boolean)
- description (text, nullable)
- model (string, unique)
- timestamps
```

## API Management

The package includes built-in REST API endpoints for managing tag configurations from your frontend or mobile app.

### API Endpoints

The following endpoints are automatically registered at `/api/tag-configs`:

#### List All Tag Configurations
```http
GET /api/tag-configs
```

Query Parameters:
- `search` - Search by model, prefix, or description
- `number_format` - Filter by format (sequential, random, branch_based)
- `per_page` - Items per page (default: 15)

Response:
```json
{
  "success": true,
  "message": "Tag configurations retrieved successfully",
  "data": [...],
  "meta": {
    "pagination": { ... }
  }
}
```

#### Create Tag Configuration
```http
POST /api/tag-configs
Content-Type: application/json

{
  "model": "App\\Models\\Equipment",
  "prefix": "EQ",
  "separator": "-",
  "number_format": "sequential",
  "auto_generate": true,
  "description": "Equipment tags"
}
```

#### Get Single Tag Configuration
```http
GET /api/tag-configs/{id}
```

#### Update Tag Configuration
```http
PUT /api/tag-configs/{id}
Content-Type: application/json

{
  "prefix": "EQUIP",
  "description": "Updated description"
}
```

#### Delete Tag Configuration
```http
DELETE /api/tag-configs/{id}
```

#### Get Number Format Options
```http
GET /api/tag-configs/meta/number-formats
```

Returns available formats with descriptions and examples.

Response:
```json
{
  "success": true,
  "message": "Number formats retrieved successfully",
  "data": {
    "sequential": {
      "label": "Sequential",
      "description": "Sequential numbering (e.g., EQ-001, EQ-002)",
      "example": "EQ-001"
    },
    "random": {
      "label": "Random",
      "description": "Random timestamp-based (e.g., EQ-1698765432)",
      "example": "EQ-1698765432"
    },
    "branch_based": {
      "label": "Branch Based",
      "description": "Branch-specific sequential (e.g., EQ-001-5)",
      "example": "EQ-001-5"
    }
  }
}
```

#### Get Available Models
```http
GET /api/tag-configs/meta/available-models
```

Returns all models in your application that:
1. Use the `Masum\Tagging\Traits\Tagable` trait
2. Define a `TAGABLE` constant as a string

Response:
```json
{
  "success": true,
  "message": "Available models retrieved successfully",
  "data": {
    "App\\Models\\Equipment": "Equipment::Generic",
    "App\\Models\\Brand": "Brand",
    "App\\Models\\Location": "Location",
    "App\\Models\\FiberCable": "Fiber::Cable"
  }
}
```

The response maps fully-qualified model class names to their display names (from the `TAGABLE` constant). This is useful for:
- Populating dropdowns in your UI
- Showing user-friendly model names
- Filtering models that are eligible for tagging

**Note:** Only models that have BOTH the `Tagable` trait AND the `TAGABLE` constant will be returned by this endpoint.

### Bulk Operations API

The package provides efficient bulk operations for managing multiple tags at once.

#### Bulk Regenerate Tags
```http
POST /api/tags/bulk/regenerate
Content-Type: application/json

{
  "tag_ids": [1, 2, 3, 4, 5]
}
```

Regenerates tags for multiple items at once. Useful when:
- Changing tag format or prefix
- Fixing incorrect tags
- Resequencing tags

Response:
```json
{
  "success": true,
  "message": "Bulk regeneration completed",
  "data": {
    "regenerated": [
      {
        "id": 1,
        "old_value": "EQ-001",
        "new_value": "EQ-006"
      },
      {
        "id": 2,
        "old_value": "EQ-002",
        "new_value": "EQ-007"
      }
    ],
    "failed": [
      {
        "id": 5,
        "error": "Configuration not found"
      }
    ]
  }
}
```

**Features:**
- Database transaction for consistency
- Individual error handling (some can succeed while others fail)
- Detailed success/failure reporting
- Automatic logging

#### Bulk Delete Tags
```http
POST /api/tags/bulk/delete
Content-Type: application/json

{
  "tag_ids": [1, 2, 3]
}
```

Deletes multiple tags at once.

Response:
```json
{
  "success": true,
  "message": "Successfully deleted 3 tags",
  "data": {
    "deleted_count": 3
  }
}
```

**Frontend Example:**
```javascript
// Regenerate selected tags
async function regenerateSelectedTags(tagIds) {
  const response = await fetch('/api/tags/bulk/regenerate', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ tag_ids: tagIds })
  });

  const result = await response.json();

  console.log(`Regenerated: ${result.data.regenerated.length}`);
  console.log(`Failed: ${result.data.failed.length}`);

  // Handle failures
  result.data.failed.forEach(failure => {
    console.error(`Tag ${failure.id}: ${failure.error}`);
  });
}

// Delete selected tags
async function deleteSelectedTags(tagIds) {
  const response = await fetch('/api/tags/bulk/delete', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ tag_ids: tagIds })
  });

  const result = await response.json();
  console.log(result.message);
}
```

### API Configuration

Configure the API routes in `config/tagging.php`:

```php
'routes' => [
    'enabled' => true,                    // Enable/disable API routes
    'prefix' => 'api/tag-configs',        // Route prefix
    'middleware' => ['api'],              // Middleware (add 'auth:sanctum' for auth)
],
```

To disable API routes, set in `.env`:
```env
TAGGING_ROUTES_ENABLED=false
```

To add authentication:
```php
'routes' => [
    'middleware' => ['api', 'auth:sanctum'],
],
```

### Example: Frontend Integration

```javascript
// Fetch available models (for model selection dropdown)
const modelsResponse = await fetch('/api/tag-configs/meta/available-models');
const modelsData = await modelsResponse.json();
// modelsData.data will be: { "App\\Models\\Equipment": "Equipment::Generic", ... }

// Fetch all tag configurations
const response = await fetch('/api/tag-configs');
const data = await response.json();

// Get number formats for dropdown
const formatsResponse = await fetch('/api/tag-configs/meta/number-formats');
const formats = await formatsResponse.json();

// Create new tag configuration
const createResponse = await fetch('/api/tag-configs', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    model: 'App\\Models\\Product',      // Full class name
    prefix: 'PRD',
    separator: '-',
    number_format: 'sequential',
    auto_generate: true,
    description: 'Product tags'
  })
});
```

## Barcode Generation

The package includes built-in barcode generation for physical label printing. Tags can be automatically converted to various barcode formats.

### Features

- Multiple barcode formats (CODE_128, CODE_39, EAN, UPC, QR Code, etc.)
- SVG, PNG, HTML, and Base64 output formats
- Batch barcode generation
- Print-ready label views
- Customizable barcode dimensions

### Barcode API Endpoints

#### Get Available Barcode Types
```http
GET /api/tags/meta/barcode-types
```

Returns all supported barcode formats:
```json
{
  "success": true,
  "message": "Available barcode types retrieved successfully",
  "data": {
    "CODE_128": "C128",
    "CODE_39": "C39",
    "EAN_13": "EAN13",
    "QR_CODE": "QRCODE"
  }
}
```

#### Generate Single Barcode
```http
GET /api/tags/{tag_id}/barcode?format={format}&width_factor={width}&height={height}
```

**Parameters:**
- `format` (optional): svg, png, base64, or html (default: svg)
- `width_factor` (optional): Width multiplier (default: 2)
- `height` (optional): Height in pixels (default: 30)

**Examples:**
```javascript
// Get SVG barcode
const svg = await fetch('/api/tags/1/barcode');

// Get PNG barcode
const png = await fetch('/api/tags/1/barcode?format=png');

// Get Base64 for inline display
const response = await fetch('/api/tags/1/barcode?format=base64');
const data = await response.json();
// data.data.barcode contains: data:image/png;base64,...
```

#### Batch Barcode Generation
```http
POST /api/tags/batch-barcodes
Content-Type: application/json

{
  "tag_ids": [1, 2, 3, 4],
  "width_factor": 2,
  "height": 30
}
```

Returns base64 encoded barcodes for multiple tags:
```json
{
  "success": true,
  "message": "Barcodes generated successfully",
  "data": [
    {
      "id": 1,
      "value": "EQ-001",
      "barcode": "data:image/png;base64,...",
      "taggable_type": "App\\Models\\Equipment",
      "taggable_id": 1
    }
  ]
}
```

### Print Labels

#### Print Labels View
```http
GET /api/tags/print/labels?tag_ids=1,2,3&labels_per_row=3&label_width=2.5in&label_height=1in
```

**Parameters:**
- `tag_ids` (required): Comma-separated tag IDs or array
- `labels_per_row` (optional): Labels per row (default: 3)
- `label_width` (optional): Label width (default: 2.5in)
- `label_height` (optional): Label height (default: 1in)

Opens a print-ready page with barcode labels for batch printing.

### Customizing Print Labels

You can customize what information appears on printed labels by defining a `TAG_LABEL` constant in your model. The label supports variable interpolation using `{attribute}` syntax.

**Basic Example:**
```php
use Masum\Tagging\Traits\Tagable;

class Brand extends Model
{
    use Tagable;

    const TAGABLE = 'Brand';
    const TAG_LABEL = 'Brand: {name}';

    protected $fillable = ['name'];
}
```

When printed, labels for this model will display:
```
BRD-001
[BARCODE]
Brand: Cisco
```

**Advanced Example with Multiple Attributes:**
```php
class Equipment extends Model
{
    use Tagable;

    const TAGABLE = 'Equipment::Generic';
    const TAG_LABEL = '{name} - {serial_no}';

    protected $fillable = ['name', 'serial_no'];
}
```

Label output:
```
EQ-001
[BARCODE]
Router-R1 - SN12345
```

**Nested Relationships (Upcoming):**
```php
class Equipment extends Model
{
    use Tagable;

    const TAGABLE = 'Equipment::Generic';
    const TAG_LABEL = '{name} ({location.name})';

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
```

**Important Notes:**
- If `TAG_LABEL` is not defined, the label defaults to the model class name
- Variable interpolation uses model attributes directly
- Supports simple attribute access with `{attribute_name}`
- Nested relationships like `{relationship.attribute}` are supported

### Programmatic Usage

```php
use Masum\Tagging\Models\Tag;

$tag = Tag::find(1);

// Generate SVG barcode
$svg = $tag->generateBarcodeSVG();

// Generate PNG barcode
$png = $tag->generateBarcodePNG();

// Get base64 data URL for inline display
$base64 = $tag->getBarcodeBase64();

// Use in Blade views
{!! $tag->generateBarcodeSVG() !!}

// Or as img tag
<img src="{{ $tag->getBarcodeBase64() }}" alt="Barcode">
```

### Frontend Integration Example

```javascript
// Fetch tags and display with barcodes
async function displayTagsWithBarcodes() {
  const response = await fetch('/api/tags/batch-barcodes', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      tag_ids: [1, 2, 3, 4, 5],
      width_factor: 2,
      height: 40
    })
  });

  const { data } = await response.json();

  data.forEach(tag => {
    const img = document.createElement('img');
    img.src = tag.barcode;
    img.alt = tag.value;
    document.getElementById('barcodes').appendChild(img);
  });
}

// Print labels for selected tags
function printLabels(tagIds) {
  const url = `/api/tags/print/labels?tag_ids=${tagIds.join(',')}`;
  window.open(url, '_blank');
}
```

## Advanced Usage

### Custom Tag Generation Logic

You can override the tag generation methods in your model:

```php
class Equipment extends Model
{
    use Tagable;

    protected function generateSequentialTag(TagConfig $tagConfig, ?string $oldTag): string
    {
        // Your custom logic here
        return parent::generateSequentialTag($tagConfig, $oldTag);
    }
}
```

### Querying by Tags

```php
use Masum\Tagging\Models\Tag;

// Find models by tag value
$tag = Tag::where('value', 'EQ-001')->first();
$equipment = $tag->taggable;

// Get all tags for a model type
$equipmentTags = Tag::where('taggable_type', Equipment::class)->get();
```

### Working with Multiple Models

```php
// Different configurations for different models
TagConfig::create([
    'model' => \App\Models\Equipment::class,
    'prefix' => 'EQ',
    'number_format' => 'sequential',
]);

TagConfig::create([
    'model' => \App\Models\Cable::class,
    'prefix' => 'CB',
    'number_format' => 'branch_based',
]);

TagConfig::create([
    'model' => \App\Models\Port::class,
    'prefix' => 'PT',
    'number_format' => 'random',
]);
```

## Events & Extensibility

The package dispatches events for all tag operations, allowing you to hook into the tagging lifecycle for custom logic, webhooks, audit trails, notifications, and more.

### Available Events

All events are in the `Masum\Tagging\Events` namespace:

#### TagCreated
Dispatched when a new tag is generated for a model.

**Properties:**
- `$tag` - The Tag model instance
- `$taggable` - The model that was tagged
- `$config` - The TagConfig used (optional)

**Example:**
```php
use Masum\Tagging\Events\TagCreated;

// In your EventServiceProvider
Event::listen(TagCreated::class, function ($event) {
    // Send webhook
    Http::post('https://api.example.com/webhooks/tag-created', [
        'tag' => $event->tag->value,
        'model' => get_class($event->taggable),
        'id' => $event->taggable->id,
    ]);

    // Log to audit trail
    AuditLog::create([
        'action' => 'tag_created',
        'tag_value' => $event->tag->value,
        'user_id' => auth()->id(),
    ]);

    // Send notification
    Mail::to('admin@example.com')->send(new TagCreatedMail($event->tag));
});
```

#### TagUpdated
Dispatched when a tag value is changed.

**Properties:**
- `$tag` - The updated Tag model instance
- `$taggable` - The model whose tag was updated
- `$oldValue` - The previous tag value

**Example:**
```php
use Masum\Tagging\Events\TagUpdated;

Event::listen(TagUpdated::class, function ($event) {
    Log::info('Tag updated', [
        'old' => $event->oldValue,
        'new' => $event->tag->value,
        'model' => get_class($event->taggable),
    ]);
});
```

#### TagDeleted
Dispatched when a tag is deleted (usually when the model is deleted).

**Properties:**
- `$tagValue` - The deleted tag value (string)
- `$taggableType` - The model class name
- `$taggableId` - The model ID

**Example:**
```php
use Masum\Tagging\Events\TagDeleted;

Event::listen(TagDeleted::class, function ($event) {
    // Archive deleted tags
    DeletedTagArchive::create([
        'value' => $event->tagValue,
        'model' => $event->taggableType,
        'model_id' => $event->taggableId,
        'deleted_at' => now(),
    ]);
});
```

#### TagGenerationFailed
Dispatched when tag generation fails (e.g., after max retries).

**Properties:**
- `$taggable` - The model that failed to get a tag
- `$exception` - The exception that was thrown
- `$fallbackTag` - Fallback tag used (if any)

**Example:**
```php
use Masum\Tagging\Events\TagGenerationFailed;

Event::listen(TagGenerationFailed::class, function ($event) {
    // Alert administrators
    Log::critical('Tag generation failed', [
        'model' => get_class($event->taggable),
        'id' => $event->taggable->id,
        'error' => $event->exception->getMessage(),
    ]);

    // Send urgent notification
    Mail::to('admin@example.com')->send(
        new TagGenerationFailedMail($event)
    );
});
```

### Registering Event Listeners

**In EventServiceProvider:**
```php
use Masum\Tagging\Events\{TagCreated, TagUpdated, TagDeleted, TagGenerationFailed};

protected $listen = [
    TagCreated::class => [
        SendTagCreatedNotification::class,
        LogTagCreation::class,
        UpdateInventorySystem::class,
    ],
    TagUpdated::class => [
        LogTagUpdate::class,
        SyncWithExternalSystem::class,
    ],
    TagDeleted::class => [
        ArchiveDeletedTag::class,
    ],
    TagGenerationFailed::class => [
        AlertAdministrators::class,
    ],
];
```

**Using Event Subscribers:**
```php
class TagEventSubscriber
{
    public function handleTagCreated($event)
    {
        // Handle tag created
    }

    public function handleTagUpdated($event)
    {
        // Handle tag updated
    }

    public function subscribe($events)
    {
        $events->listen(
            TagCreated::class,
            [TagEventSubscriber::class, 'handleTagCreated']
        );

        $events->listen(
            TagUpdated::class,
            [TagEventSubscriber::class, 'handleTagUpdated']
        );
    }
}

// Register in EventServiceProvider
protected $subscribe = [
    TagEventSubscriber::class,
];
```

### Use Cases

**Audit Trail:**
```php
Event::listen(TagCreated::class, function ($event) {
    DB::table('tag_audit_log')->insert([
        'action' => 'created',
        'tag_value' => $event->tag->value,
        'model_type' => get_class($event->taggable),
        'model_id' => $event->taggable->id,
        'user_id' => auth()->id(),
        'created_at' => now(),
    ]);
});
```

**Webhook Integration:**
```php
Event::listen(TagCreated::class, function ($event) {
    Http::post(config('services.inventory.webhook_url'), [
        'event' => 'tag.created',
        'tag' => $event->tag->value,
        'item' => [
            'type' => get_class($event->taggable),
            'id' => $event->taggable->id,
            'name' => $event->taggable->name ?? null,
        ],
        'timestamp' => now()->toIso8601String(),
    ]);
});
```

**Slack Notifications:**
```php
Event::listen(TagGenerationFailed::class, function ($event) {
    Notification::route('slack', config('services.slack.webhook'))
        ->notify(new SlackNotification([
            'title' => '⚠️ Tag Generation Failed',
            'message' => "Failed to generate tag for " . get_class($event->taggable),
            'error' => $event->exception->getMessage(),
        ]));
});
```

## Exception Handling

The package provides specific exception classes for better error handling and debugging.

### Available Exceptions

All exceptions are in the `Masum\Tagging\Exceptions` namespace and extend `TaggingException`.

#### TagGenerationException
Thrown when tag generation fails.

**Factory Methods:**
- `configNotFound(string $modelClass)` - No configuration found for model
- `concurrencyFailure(string $modelClass, int $attempts)` - Failed after max retries
- `invalidConfig(string $reason)` - Invalid tag configuration

**Example:**
```php
use Masum\Tagging\Exceptions\TagGenerationException;

try {
    $equipment = Equipment::create(['name' => 'Router']);
} catch (TagGenerationException $e) {
    // Handle tag generation failure
    Log::error('Failed to generate tag', [
        'error' => $e->getMessage(),
        'equipment' => $equipment->id ?? null,
    ]);

    // Maybe assign a manual tag
    if (isset($equipment)) {
        $equipment->update(['tag' => 'MANUAL-' . time()]);
    }

    // Show user-friendly message
    return response()->json([
        'error' => 'Could not generate automatic tag. Please assign manually.'
    ], 500);
}
```

#### DuplicateTagException
Thrown when attempting to create a duplicate tag.

**Factory Methods:**
- `forModel(string $tagValue, string $modelClass)` - Tag already exists for model
- `valueExists(string $tagValue)` - Tag value already in use

**Example:**
```php
use Masum\Tagging\Exceptions\DuplicateTagException;

try {
    $tag = Tag::create([
        'value' => 'EQ-001',
        'taggable_type' => Equipment::class,
        'taggable_id' => 1,
    ]);
} catch (DuplicateTagException $e) {
    return response()->json([
        'error' => 'This tag already exists. Please choose a different value.'
    ], 409);
}
```

#### InvalidTagFormatException
Thrown when tag format validation fails.

**Factory Methods:**
- `create(string $tag, string $reason)` - Generic format error
- `lengthExceeded(string $tag, int $maxLength)` - Tag too long
- `invalidCharacters(string $tag, string $allowedPattern)` - Invalid characters

**Example:**
```php
use Masum\Tagging\Exceptions\InvalidTagFormatException;

try {
    $equipment->tag = 'INVALID TAG WITH SPACES!!!';
} catch (InvalidTagFormatException $e) {
    return response()->json([
        'error' => 'Invalid tag format. Use only alphanumeric characters and dashes.'
    ], 422);
}
```

### Global Exception Handling

**In your Handler.php:**
```php
use Masum\Tagging\Exceptions\TaggingException;
use Masum\Tagging\Exceptions\TagGenerationException;
use Masum\Tagging\Exceptions\DuplicateTagException;

public function register(): void
{
    $this->renderable(function (TagGenerationException $e, Request $request) {
        Log::error('Tag generation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'error' => 'Failed to generate tag automatically.',
                'message' => config('app.debug') ? $e->getMessage() : 'Please contact support.',
            ], 500);
        }

        return back()->with('error', 'Failed to generate tag. Please try again.');
    });

    $this->renderable(function (DuplicateTagException $e, Request $request) {
        if ($request->wantsJson()) {
            return response()->json([
                'error' => 'Duplicate tag detected.',
            ], 409);
        }

        return back()->with('error', 'This tag already exists.');
    });
}
```

### Best Practices

**1. Always catch specific exceptions first:**
```php
try {
    $equipment = Equipment::create($data);
} catch (DuplicateTagException $e) {
    // Handle duplicates
} catch (InvalidTagFormatException $e) {
    // Handle format errors
} catch (TagGenerationException $e) {
    // Handle generation failures
} catch (\Exception $e) {
    // Handle other errors
}
```

**2. Log exceptions with context:**
```php
catch (TagGenerationException $e) {
    Log::error('Tag generation failed', [
        'exception' => get_class($e),
        'message' => $e->getMessage(),
        'model' => $modelClass,
        'data' => $data,
    ]);
}
```

**3. Provide user-friendly messages:**
```php
catch (TaggingException $e) {
    $userMessage = config('app.debug')
        ? $e->getMessage()
        : 'An error occurred. Please contact support.';

    return response()->json(['error' => $userMessage], 500);
}
```

## Performance & Best Practices

### Avoiding N+1 Queries

When loading multiple models with tags, **always use eager loading** to avoid N+1 query problems:

```php
// ❌ Bad - Creates N+1 queries (one query per model)
$equipment = Equipment::all();
foreach ($equipment as $item) {
    echo $item->tag; // Separate query each time!
}

// ✅ Good - Single query for all tags
$equipment = Equipment::with('tag')->all();
foreach ($equipment as $item) {
    echo $item->tag; // Uses loaded relationship
}
```

The package will log warnings in debug mode when N+1 queries are detected. Disable warnings in `.env`:

```env
TAGGING_DEBUG_N_PLUS_ONE=false
```

### Caching Configuration

Tag configurations are automatically cached to improve performance. Configure caching in `config/tagging.php`:

```php
'cache' => [
    'enabled' => env('TAGGING_CACHE_ENABLED', true),
    'ttl' => env('TAGGING_CACHE_TTL', 3600), // 1 hour
],
```

Or via `.env`:

```env
TAGGING_CACHE_ENABLED=true
TAGGING_CACHE_TTL=3600
```

Cache is automatically invalidated when tag configurations are updated or deleted.

### Configurable Padding Length

Customize the number of digits in sequential tags:

```php
TagConfig::create([
    'model' => \App\Models\Equipment::class,
    'prefix' => 'EQ',
    'separator' => '-',
    'number_format' => 'sequential',
    'padding_length' => 5, // Generates: EQ-00001, EQ-00002, etc.
]);
```

Default padding is 3 digits (`001`, `002`, etc.).

### Race Condition Protection

Sequential and branch-based tag generation uses database-level locking to prevent duplicate tags in high-concurrency scenarios. Configure retry behavior:

```env
TAGGING_MAX_RETRIES=3
TAGGING_LOCK_TIMEOUT=10
```

The package will:
1. Lock the tag configuration row during generation
2. Atomically increment the counter
3. Retry up to 3 times with exponential backoff if conflicts occur
4. Fall back to timestamp-based tags if all retries fail

### High-Concurrency Tips

For applications with high concurrent tag generation:

1. **Use a robust database**: PostgreSQL or MySQL with InnoDB engine
2. **Monitor lock timeouts**: Check logs for lock timeout errors
3. **Consider random format**: For very high throughput, use `random` format to avoid locking
4. **Database indexes**: The package adds indexes automatically, but ensure your database is properly tuned

### Performance Monitoring

Enable query logging in development to monitor performance:

```php
// In your controller or service
\DB::enableQueryLog();

$equipment = Equipment::with('tag')->take(100)->get();

dd(\DB::getQueryLog()); // Should show only 2-3 queries total
```

### Memory Optimization

For bulk operations with thousands of records:

```php
// ✅ Good - Chunk large datasets
Equipment::with('tag')->chunk(100, function ($equipment) {
    foreach ($equipment as $item) {
        // Process item
    }
});

// ❌ Bad - Loads all records into memory
$allEquipment = Equipment::with('tag')->get(); // Could run out of memory
```

### Index Usage

The package creates these indexes for optimal performance:

- Composite index on `(taggable_type, taggable_id)` - Fast polymorphic lookups
- Unique constraint on `(taggable_type, taggable_id)` - Prevents duplicates
- Index on `value` - Fast tag searches

Verify indexes are created:

```sql
SHOW INDEXES FROM tagging_tags;
```

## Requirements

- PHP 8.1 or higher
- Laravel 10.x or 11.x

## License

This package is open-sourced software licensed under the MIT license.

## Credits

- Masum
- All Contributors

## Support

For issues, questions, or contributions, please visit the GitHub repository.