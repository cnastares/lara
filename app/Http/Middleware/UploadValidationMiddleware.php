<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UploadValidationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('post') && $request->hasFile('files')) {
            $files = (array) $request->file('files');
            if (count($files) > 5) {
                Log::warning('Too many files uploaded', ['count' => count($files)]);
                return response()->json(['message' => 'Maximum 5 files allowed'], 422);
            }

            // Rate limit per IP
            $key = 'upload_rate:'. $request->ip();
            $attempts = Cache::increment($key);
            Cache::put($key, $attempts, now()->addMinute());
            if ($attempts > 20) {
                Log::warning('Upload rate limit exceeded', ['ip' => $request->ip()]);
                return response()->json(['message' => 'Too many upload attempts'], 429);
            }

            // Check checksum header if provided
            if ($checksum = $request->header('X-Checksum')) {
                $computed = hash('sha256', $request->getContent());
                if (!hash_equals($checksum, $computed)) {
                    Log::error('Checksum mismatch');
                    return response()->json(['message' => 'Invalid request checksum'], 400);
                }
            }
        }

        return $next($request);
    }
}
