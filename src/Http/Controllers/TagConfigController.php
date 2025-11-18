<?php

namespace Masum\Tagging\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Masum\Tagging\Models\TagConfig;

class TagConfigController extends Controller
{
    /**
     * Display a listing of tag configurations
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'search' => 'nullable|string|max:255',
                'number_format' => 'nullable|in:sequential,branch_based,random',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = TagConfig::query();

            // Search with input sanitization
            if ($request->filled('search')) {
                $search = $this->sanitizeSearchInput($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('model', 'LIKE', "%{$search}%")
                      ->orWhere('prefix', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            // Filter by number format
            if ($request->filled('number_format')) {
                $query->where('number_format', $request->number_format);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $tagConfigs = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Tag configurations retrieved successfully',
                'data' => $tagConfigs->items(),
                'meta' => [
                    'pagination' => [
                        'current_page' => $tagConfigs->currentPage(),
                        'per_page' => $tagConfigs->perPage(),
                        'total' => $tagConfigs->total(),
                        'last_page' => $tagConfigs->lastPage(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving tag configurations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tag configurations',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created tag configuration
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'model' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique(config('tagging.table_prefix', '') . config('tagging.tables.tag_configs', 'tag_configs'), 'model')
                ],
                'prefix' => 'required|string|max:10|regex:/^[A-Z0-9-_]+$/i',
                'separator' => 'required|string|max:5',
                'number_format' => 'required|in:sequential,branch_based,random',
                'auto_generate' => 'boolean',
                'description' => 'nullable|string|max:1000',
                'padding_length' => 'nullable|integer|min:1|max:10',
                'current_number' => 'nullable|integer|min:0',
            ]);

            $tagConfig = TagConfig::create($validated);

            Log::info('Tag configuration created', [
                'id' => $tagConfig->id,
                'model' => $tagConfig->model,
                'prefix' => $tagConfig->prefix
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tag configuration created successfully',
                'data' => $tagConfig
            ], 201);
        } catch (QueryException $e) {
            Log::error('Database error creating tag configuration', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            // Check for unique constraint violation
            if ($e->getCode() === '23000') {
                return response()->json([
                    'success' => false,
                    'message' => 'A tag configuration already exists for this model',
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error creating tag configuration', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create tag configuration',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified tag configuration
     */
    public function show(TagConfig $tagConfig): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Tag configuration retrieved successfully',
            'data' => $tagConfig
        ]);
    }

    /**
     * Update the specified tag configuration
     */
    public function update(Request $request, TagConfig $tagConfig): JsonResponse
    {
        try {
            $validated = $request->validate([
                'model' => [
                    'sometimes',
                    'string',
                    'max:255',
                    Rule::unique(config('tagging.table_prefix', '') . config('tagging.tables.tag_configs', 'tag_configs'), 'model')->ignore($tagConfig->id)
                ],
                'prefix' => 'sometimes|string|max:10|regex:/^[A-Z0-9-_]+$/i',
                'separator' => 'sometimes|string|max:5',
                'number_format' => 'sometimes|in:sequential,branch_based,random',
                'auto_generate' => 'sometimes|boolean',
                'description' => 'nullable|string|max:1000',
                'padding_length' => 'nullable|integer|min:1|max:10',
                'current_number' => 'nullable|integer|min:0',
            ]);

            $tagConfig->update($validated);

            Log::info('Tag configuration updated', [
                'id' => $tagConfig->id,
                'model' => $tagConfig->model,
                'changes' => $validated
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tag configuration updated successfully',
                'data' => $tagConfig->fresh()
            ]);
        } catch (QueryException $e) {
            Log::error('Database error updating tag configuration', [
                'id' => $tagConfig->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error updating tag configuration', [
                'id' => $tagConfig->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update tag configuration',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified tag configuration
     */
    public function destroy(TagConfig $tagConfig): JsonResponse
    {
        try {
            $modelClass = $tagConfig->model;
            $tagConfig->delete();

            Log::info('Tag configuration deleted', [
                'id' => $tagConfig->id,
                'model' => $modelClass
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tag configuration deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting tag configuration', [
                'id' => $tagConfig->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tag configuration',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get number format options
     */
    public function numberFormats(): JsonResponse
    {
        $formats = [
            'sequential' => [
                'label' => 'Sequential',
                'description' => 'Generates sequential tags (e.g., BRD-001, BRD-002)',
                'example' => 'PREFIX-001',
            ],
            'random' => [
                'label' => 'Random (Timestamp)',
                'description' => 'Generates timestamp-based tags',
                'example' => 'PREFIX-1698765432',
            ],
            'branch_based' => [
                'label' => 'Branch Based',
                'description' => 'Generates tags with branch ID (e.g., PREFIX-001-5)',
                'example' => 'PREFIX-001-{branch_id}',
            ],
        ];

        return response()->json([
            'success' => true,
            'message' => 'Number formats retrieved successfully',
            'data' => $formats
        ]);
    }

    /**
     * Get all models that use the Tagable trait or have TAGABLE constant
     */
    public function availableModels(): JsonResponse
    {
        try {
            $models = [];
            $modelNamespace = 'App\\Models\\';
            $modelPath = app_path('Models');

            if (is_dir($modelPath)) {
                foreach (scandir($modelPath) as $file) {
                    if ($file === '.' || $file === '..' || pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
                        continue;
                    }

                    $class = $modelNamespace . pathinfo($file, PATHINFO_FILENAME);

                    if (
                        class_exists($class) &&
                        is_subclass_of($class, \Illuminate\Database\Eloquent\Model::class)
                    ) {
                        // Check if model uses the Tagable trait from this package AND has TAGABLE constant both
                        $usesPackageTrait = in_array(\Masum\Tagging\Traits\Tagable::class, class_uses_recursive($class));
                        $hasTagableConstant = defined("$class::TAGABLE");

                        if ($usesPackageTrait && $hasTagableConstant) {
                            // TAGABLE constant is a string, use it as the display name
                            $modelName = constant("$class::TAGABLE");
                            $models[$class] = $modelName;
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Available models retrieved successfully',
                'data' => $models
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving available models', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve available models',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Sanitize search input to prevent SQL wildcards abuse
     */
    protected function sanitizeSearchInput(string $search): string
    {
        // Remove excessive wildcards and limit length
        $search = trim($search);
        $search = substr($search, 0, 255);

        // Escape SQL wildcards
        $search = str_replace(['%', '_'], ['\%', '\_'], $search);

        return $search;
    }
}
