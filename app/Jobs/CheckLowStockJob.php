<?php

namespace App\Jobs;

use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class CheckLowStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    public $variant;

    /**
     * Create a new job instance.
     */
    public function __construct(ProductVariant $variant)
    {
        $this->variant = $variant;
        $this->user = Auth::user();
    }

    public function middleware(): array
    {
        // simple rate limit to protect notification spam
        return [new RateLimited('low-stock')];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->variant->refresh();

        if ($this->variant->stock <= $this->variant->low_stock_threshold && ! $this->variant->low_stock_notified) {
            // Send notification to admins — customize recipients as you like.
            // Example: notify all users with 'manage inventory' permission. For simplicity, notify first admin user.
            $admins = User::role('Admin')->get();

            foreach ($admins as $admin) {
                $admin->notify(new LowStockNotification($this->variant));
            }

            $this->variant->updateQuietly(['low_stock_notified' => true]);
        }

        if ($this->variant->stock > $this->variant->low_stock_threshold && $this->variant->low_stock_notified) {
            // Reset the flag once replenished.
            $this->variant->updateQuietly(['low_stock_notified' => false]);
        }
    }
}
