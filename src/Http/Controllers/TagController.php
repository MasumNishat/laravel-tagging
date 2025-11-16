<?php

namespace Masum\Tagging\Http\Controllers;

use Masum\Tagging\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class TagController extends Controller
{
    /**
     * Display a listing of tags
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tag::query()->with('taggable');

        // Search by tag value
        if ($request->has('search')) {
            $query->where('value', 'LIKE', "%{$request->search}%");
        }

        // Filter by model type
        if ($request->has('model')) {
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
    }

    /**
     * Display a specific tag
     */
    public function show($id): JsonResponse
    {
        $tag = Tag::with('taggable')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Tag retrieved successfully',
            'data' => $tag
        ]);
    }

    /**
     * Generate barcode for a tag (SVG format)
     */
    public function barcode($id, Request $request): Response
    {
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
    }

    /**
     * Generate barcodes for multiple tags (for batch printing)
     */
    public function batchBarcodes(Request $request): JsonResponse
    {
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
        $tagIds = $request->get('tag_ids', []);

        if (is_string($tagIds)) {
            $tagIds = explode(',', $tagIds);
        }

        $tags = Tag::with('taggable')->whereIn('id', $tagIds)->get();
        $labelsPerRow = (int) $request->get('labels_per_row', 3);
        $labelWidth = $request->get('label_width', '2.5in');
        $labelHeight = $request->get('label_height', '1in');

        return view('tagging::print-labels', compact('tags', 'labelsPerRow', 'labelWidth', 'labelHeight'));
    }
}
