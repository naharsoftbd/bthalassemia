<?php

namespace App\Jobs;

use App\Services\Products\ProductImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessProductImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $filePath;

    public $vendorId;

    public $updateExisting;

    public $userId;

    public function __construct(string $filePath, ?int $vendorId, bool $updateExisting, int $userId)
    {
        $this->filePath = $filePath;
        $this->vendorId = $vendorId;
        $this->updateExisting = $updateExisting;
        $this->userId = $userId;
    }

    public function handle(ProductImportService $importService): void
    {
        try {
            $file = new \Illuminate\Http\File(public_path('storage/' . $this->filePath));

            $results = $importService->importProducts(
                new \Illuminate\Http\UploadedFile(
                    $file->getPathname(),
                    $file->getFilename(),
                    $file->getMimeType(),
                    null,
                    true
                ),
                $this->vendorId,
                $this->updateExisting
            );

            // Store results or send notification
            $this->storeResults($results);

            // Clean up file
            Storage::delete($this->filePath);

        } catch (\Exception $e) {
            \Log::error('Product import job failed: '.$e->getMessage());

            // Clean up file on failure
            if (Storage::exists($this->filePath)) {
                Storage::delete($this->filePath);
            }
        }
    }

    protected function storeResults(array $results): void
    {
        // Store results in database or send notification
        // You can create an ImportResult model or send email notification
        \Log::info('Product import completed', $results);
    }
}
