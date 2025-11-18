<?php

namespace Masum\Tagging\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Masum\Tagging\Models\Tag;

class TagController extends Controller
{
    /**
     * Display a listing of tags
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'search' => 'nullable|string|max:255',
                'model' => 'nullable|string|max:255',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = Tag::query()->with('taggable');

            // Search by tag value
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('value', 'LIKE', "%{$search}%");
            }

            // Filter by model type
            if ($request->filled('model')) {
                $query->where('taggable_type', $request->model);
            }

            // Pagination
            $perPage = $request->get('per_page', 50);
            $tags = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Tags retrieved successfully',
                'data' => $tags->items(),
                'meta' => [
                    'pagination' => [
                        'current_page' => $tags->currentPage(),
                        'per_page' => $tags->perPage(),
                        'total' => $tags->total(),
                        'last_page' => $tags->lastPage(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving tags', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tags',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display a specific tag
     */
    public function show($id): JsonResponse
    {
        try {
            $tag = Tag::with('taggable')->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Tag retrieved successfully',
                'data' => $tag
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving tag', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Tag not found',
            ], 404);
        }
    }

    /**
     * Bulk regenerate tags for specified tag IDs
     */
    public function bulkRegenerate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tag_ids' => 'required|array|min:1',
                'tag_ids.*' => 'exists:' . (new Tag())->getTable() . ',id'
            ]);

            $regenerated = [];
            $failed = [];

            DB::transaction(function () use ($validated, &$regenerated, &$failed) {
                foreach ($validated['tag_ids'] as $tagId) {
                    try {
                        $tag = Tag::with('taggable')->findOrFail($tagId);
                        $taggable = $tag->taggable;

                        if ($taggable) {
                            $oldValue = $tag->value;
                            $newValue = $taggable->generateNextTag();

                            $tag->update(['value' => $newValue]);

                            $regenerated[] = [
                                'id' => $tag->id,
                                'old_value' => $oldValue,
                                'new_value' => $newValue,
                            ];
                        }
                    } catch (\Exception $e) {
                        $failed[] = [
                            'id' => $tagId,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            });

            Log::info('Bulk tag regeneration completed', [
                'regenerated_count' => count($regenerated),
                'failed_count' => count($failed)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bulk regeneration completed',
                'data' => [
                    'regenerated' => $regenerated,
                    'failed' => $failed,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in bulk regeneration', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk regeneration failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Bulk delete tags
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tag_ids' => 'required|array|min:1',
                'tag_ids.*' => 'exists:' . (new Tag())->getTable() . ',id'
            ]);

            $deletedCount = Tag::whereIn('id', $validated['tag_ids'])->delete();

            Log::info('Bulk tag deletion completed', [
                'deleted_count' => $deletedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} tags",
                'data' => [
                    'deleted_count' => $deletedCount
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in bulk deletion', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk deletion failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Generate barcode for a tag (SVG format)
     */
    public function barcode($id, Request $request): Response
    {
        try {
            $tag = Tag::findOrFail($id);

            $format = $request->get('format', 'svg'); // svg, png, base64, html
            $widthFactor = (int) $request->get('width_factor', 2);
            $height = (int) $request->get('height', 30);

            switch ($format) {
                case 'png':
                    $barcode = $tag->generateBarcodePNG(null, $widthFactor, $height);
                    return response($barcode, 200, [
                        'Content-Type' => 'image/png',
                        'Content-Disposition' => 'inline; filename="barcode-' . $tag->value . '.png"'
                    ]);

                case 'base64':
                    $barcode = $tag->getBarcodeBase64(null, $widthFactor, $height);
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'barcode' => $barcode,
                            'tag' => $tag->value
                        ]
                    ]);

                case 'html':
                    $barcode = $tag->generateBarcodeHTML(null, $widthFactor, $height);
                    return response($barcode, 200, [
                        'Content-Type' => 'text/html'
                    ]);

                case 'svg':
                default:
                    $barcode = $tag->generateBarcodeSVG(null, $widthFactor, $height);
                    return response($barcode, 200, [
                        'Content-Type' => 'image/svg+xml',
                        'Content-Disposition' => 'inline; filename="barcode-' . $tag->value . '.svg"'
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('Error generating barcode', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate barcode',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Generate barcodes for multiple tags (for batch printing)
     */
    public function batchBarcodes(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'tag_ids' => 'required|array',
                'tag_ids.*' => 'exists:' . (new Tag())->getTable() . ',id'
            ]);

            $tags = Tag::whereIn('id', $request->tag_ids)->get();
            $widthFactor = (int) $request->get('width_factor', 2);
            $height = (int) $request->get('height', 30);

            $barcodes = $tags->map(function ($tag) use ($widthFactor, $height) {
                return [
                    'id' => $tag->id,
                    'value' => $tag->value,
                    'barcode' => $tag->getBarcodeBase64(null, $widthFactor, $height),
                    'taggable_type' => $tag->taggable_type,
                    'taggable_id' => $tag->taggable_id,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Barcodes generated successfully',
                'data' => $barcodes
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating batch barcodes', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate barcodes',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get available barcode types
     */
    public function barcodeTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Available barcode types retrieved successfully',
            'data' => Tag::availableBarcodeTypes()
        ]);
    }

    /**
     * Get print label view
     */
    public function printLabels(Request $request)
    {
        try {
            $tagIds = $request->get('tag_ids', []);

            if (is_string($tagIds)) {
                $tagIds = explode(',', $tagIds);
            }

            $tags = Tag::with('taggable')->whereIn('id', $tagIds)->get();
            $labelsPerRow = (int) $request->get('labels_per_row', 3);
            $labelWidth = $request->get('label_width', '2.5in');
            $labelHeight = $request->get('label_height', '1in');

            return view('tagging::print-labels', compact('tags', 'labelsPerRow', 'labelWidth', 'labelHeight'));
        } catch (\Exception $e) {
            Log::error('Error generating print labels', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate print labels',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
