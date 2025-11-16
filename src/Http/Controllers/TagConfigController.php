<?php

namespace Masum\Tagging\Http\Controllers;

use Masum\Tagging\Models\TagConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TagConfigController extends Controller
{
    /**
     * Display a listing of tag configurations
     */
    public function index(Request $request): JsonResponse
    {
        $query = TagConfig::query();

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('model', 'LIKE', "%{$search}%")
                  ->orWhere('prefix', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Filter by number format
        if ($request->has('number_format')) {
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
    }

    /**
     * Store a newly created tag configuration
     */
    public function store(Request $request): JsonResponse
    {
        $prefix = config('tagging.table_prefix', 'tagging_');
        $table = config('tagging.tables.tag_configs', 'tag_configs');

        $validated = $request->validate([
            'model' => 'required|string|unique:' . $prefix . $table . ',model',
            'prefix' => 'required|string|max:10',
            'separator' => 'required|string|max:5',
            'number_format' => 'required|in:sequential,branch_based,random',
            'auto_generate' => 'boolean',
            'description' => 'nullable|string',
        ]);

        try {
            $tagConfig = TagConfig::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Tag configuration created successfully',
                'data' => $tagConfig
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tag configuration: ' . $e->getMessage(),
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
        $prefix = config('tagging.table_prefix', 'tagging_');
        $table = config('tagging.tables.tag_configs', 'tag_configs');

        $validated = $request->validate([
            'model' => 'sometimes|string|unique:' . $prefix . $table . ',model,' . $tagConfig->id,
            'prefix' => 'sometimes|string|max:10',
            'separator' => 'sometimes|string|max:5',
            'number_format' => 'sometimes|in:sequential,branch_based,random',
            'auto_generate' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        try {
            $tagConfig->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Tag configuration updated successfully',
                'data' => $tagConfig->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tag configuration: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified tag configuration
     */
    public function destroy(TagConfig $tagConfig): JsonResponse
    {
        try {
            $tagConfig->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tag configuration deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tag configuration: ' . $e->getMessage(),
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
    }
}