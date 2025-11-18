<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VendorApprovedMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->hasRole('vendor')) {
            if (!auth()->user()->isVendorApproved()) {
                return redirect()->route('vendor.pending')
                    ->with('error', 'Your vendor account is pending approval.');
            }
        }

        return $next($request);
    }
}