<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanTempFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete temporary uploaded files older than one hour.';

    public function handle(): void
    {
        $tempPath = storage_path('app/temporary');
        $files = glob($tempPath . '/*');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) >= 3600) {
                @unlink($file);
            }
        }

        $this->info('Temporary files cleaned.');
    }
}
