<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ImageProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    protected ImageProcessingService $service;

    public function __construct(ImageProcessingService $service)
    {
        $this->service = $service;
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'files.*' => 'required|file|mimes:jpg,jpeg,png,gif'
        ]);

        $config = Cache::remember('php_upload_config', 3600, function () {
            return [
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size'      => ini_get('post_max_size'),
                'max_file_uploads'   => ini_get('max_file_uploads'),
                'upload_tmp_dir'     => ini_get('upload_tmp_dir'),
                'memory_limit'       => ini_get('memory_limit'),
            ];
        });
        Log::channel('upload')->info('php_config', $config);

        $files = (array) $request->file('files');
        $dest = 'uploads/'.date('Y/m/d');
        $absolute = realpath(storage_path('app/public/'.$dest));
        if ($absolute === false) {
            $absolute = Storage::disk('public')->path($dest);
        }

        $results = [];
        foreach ($files as $file) {
            $results[] = $this->service->handle($file, $dest);
        }

        return response()->json(['success' => true, 'files' => $results], 201);
    }
}
