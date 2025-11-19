<?php

namespace App\Http\Controllers\Api\V1\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Products\ImportProductsRequest;
use App\Services\Products\ProductImportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Jobs\ProcessProductImport;

class ProductImportController extends Controller
{
    protected $importService;

    public function __construct(ProductImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Import products from CSV
     */
    public function import(ImportProductsRequest $request): JsonResponse
{
    \Log::info('🎯 IMPORT CONTROLLER METHOD CALLED', [
        'timestamp' => now()->toDateTimeString(),
        'user_id' => auth()->id(),
        'user_email' => auth()->user()->email,
        'user_roles' => auth()->user()->getRoleNames(),
        'request_type' => get_class($request),
    ]);

    try {
        \Log::info('🔍 READING REQUEST DATA', [
            'vendor_id' => $request->vendor_id,
            'update_existing' => $request->update_existing,
            'has_csv_file' => $request->hasFile('csv_file'),
            'file_name' => $request->file('csv_file') ? $request->file('csv_file')->getClientOriginalName() : 'NO FILE',
            'all_input' => $request->all(),
        ]);

        $vendorId = (int) $request->vendor_id;
        $updateExisting = $request->boolean('update_existing', false);

        \Log::info('👤 CHECKING USER AUTHORIZATION', [
            'user_has_vendor_role' => auth()->user()->hasRole('vendor'),
            'user_vendor_id' => auth()->user()->vendor->id ?? 'NO VENDOR PROFILE',
            'request_vendor_id' => $vendorId,
        ]);

        // Check vendor authorization
        if (auth()->user()->hasRole('vendor')) {
            \Log::info('🔐 VENDOR AUTHORIZATION CHECK', [
                'user_vendor_id' => auth()->user()->vendor->id,
                'request_vendor_id' => $vendorId,
            ]);

            if (!$vendorId || $vendorId !== auth()->user()->vendor->id) {
                \Log::warning('🚫 VENDOR AUTHORIZATION FAILED', [
                    'reason' => 'Vendor ID mismatch or missing',
                    'user_vendor_id' => auth()->user()->vendor->id,
                    'request_vendor_id' => $vendorId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'You can only import products for your own vendor account.',
                ], 403);
            }
            $vendorId = auth()->user()->vendor->id;
            
            \Log::info('✅ VENDOR AUTHORIZATION PASSED', [
                'final_vendor_id' => $vendorId,
            ]);
        }

        \Log::info('🚀 STARTING PRODUCT IMPORT', [
            'final_vendor_id' => $vendorId,
            'update_existing' => $updateExisting,
            'file_size' => $request->file('csv_file')->getSize(),
        ]);

        $results = $this->importService->importProducts(
            $request->file('csv_file'),
            $vendorId,
            $updateExisting
        );

        \Log::info('📊 IMPORT RESULTS', $results);

        if (!empty($results['errors'])) {
            \Log::warning('⚠️ IMPORT COMPLETED WITH ERRORS', [
                'error_count' => count($results['errors']),
                'success_count' => $results['successful'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import completed with errors.',
                'data' => $results,
            ], 422);
        }

        \Log::info('🎉 IMPORT SUCCESSFUL', [
            'imported_count' => $results['successful'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Products imported successfully.',
            'data' => $results,
        ]);

    } catch (\Exception $e) {
        \Log::error('💥 IMPORT FAILED WITH EXCEPTION', [
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'error_trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to import products.',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}

    public function downloadTemplate(): StreamedResponse
    {
        $templateData = $this->importService->getTemplate();

        return response()->stream(function () use ($templateData) {

            // Clean any previous output buffering
            if (ob_get_length()) {
                ob_end_clean();
            }

            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV Header
            fputcsv($handle, array_keys($templateData[0]));

            // CSV Rows
            foreach ($templateData as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);

        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=product-import-template.csv',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Alternative method that always forces download
     */
    public function downloadTemplateForce(): StreamedResponse
    {
        $templateData = $this->importService->getTemplate();

        return response()->streamDownload(function () use ($templateData) {
            $handle = fopen('php://output', 'w');

            // Add UTF-8 BOM
            fwrite($handle, "\xEF\xBB\xBF");

            // Headers
            fputcsv($handle, array_keys($templateData[0]));

            // Data
            foreach ($templateData as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 'product-import-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Simple and reliable method
     */
    public function downloadTemplateSimple(): StreamedResponse
    {
        $templateData = $this->importService->getTemplate();

        return response()->streamDownload(function () use ($templateData) {
            $handle = fopen('php://output', 'w');

            // Write headers
            fputcsv($handle, [
                'name', 'sku', 'price', 'stock', 'description', 'short_description',
                'base_price', 'compare_at_price', 'variant_name', 'attributes',
                'weight', 'barcode', 'low_stock_threshold', 'is_active', 'tags',
                'meta_title', 'meta_description',
            ]);

            // Write sample data
            foreach ($templateData as $row) {
                fputcsv($handle, [
                    $row['name'],
                    $row['sku'],
                    $row['price'],
                    $row['stock'],
                    $row['description'],
                    $row['short_description'],
                    $row['base_price'],
                    $row['compare_at_price'],
                    $row['variant_name'],
                    $row['attributes'],
                    $row['weight'],
                    $row['barcode'],
                    $row['low_stock_threshold'],
                    $row['is_active'],
                    $row['tags'],
                    $row['meta_title'],
                    $row['meta_description'],
                ]);
            }

            fclose($handle);
        }, 'product-import-template.csv');
    }

    /**
     * Get import status/statistics
     */
    public function getImportStatus(): JsonResponse
    {
        // This could be enhanced to track ongoing imports with jobs
        return response()->json([
            'success' => true,
            'data' => [
                'max_file_size' => '10MB',
                'supported_columns' => $this->getSupportedColumns(),
                'required_columns' => ['name', 'sku', 'price', 'stock'],
            ],
        ]);
    }

    protected function getSupportedColumns(): array
    {
        return [
            'name' => 'Product name (required)',
            'sku' => 'SKU - unique identifier (required)',
            'price' => 'Variant price (required)',
            'stock' => 'Stock quantity (required)',
            'description' => 'Product description',
            'short_description' => 'Short product description',
            'base_price' => 'Base product price',
            'compare_at_price' => 'Compare at price',
            'variant_name' => 'Variant name (e.g., "Blue, Large")',
            'attributes' => 'JSON attributes (e.g., {"color": "blue", "size": "large"})',
            'weight' => 'Product weight',
            'barcode' => 'Barcode',
            'low_stock_threshold' => 'Low stock alert threshold',
            'is_active' => 'Active status (true/false)',
            'tags' => 'Comma-separated tags',
            'meta_title' => 'SEO meta title',
            'meta_description' => 'SEO meta description',
        ];
    }

    /**
     * Import products via queue (for large files)
     */
    public function importAsync(ImportProductsRequest $request): JsonResponse
    {
        try {
            $vendorId = $request->vendor_id;
            $updateExisting = $request->boolean('update_existing', false);

            // Check vendor authorization
            if (auth()->user()->hasRole('vendor')) {
                $vendorId = auth()->user()->vendor->id;
            }

            // Store file
            $filePath = $request->file('csv_file')->store('imports', 'public');

            // Dispatch job
            ProcessProductImport::dispatch(
                $filePath,
                $vendorId,
                $updateExisting,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Product import has been queued. You will be notified when it completes.',
                'data' => [
                    'job_dispatched' => true,
                    'file_size' => $request->file('csv_file')->getSize(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to queue product import.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    
}