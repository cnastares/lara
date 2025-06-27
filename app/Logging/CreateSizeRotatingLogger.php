<?php
namespace App\Logging;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Illuminate\Support\Facades\File;

class CreateSizeRotatingLogger
{
    public function __invoke(array $config): Logger
    {
        $path = $config['path'];
        $level = Logger::toMonologLevel($config['level'] ?? Logger::DEBUG);
        $max = $config['max_bytes'] ?? 5242880; // 5MB

        if (File::exists($path) && File::size($path) >= $max) {
            $archive = $path.'.'.date('Ymd_His');
            File::move($path, $archive);
        }

        $handler = new StreamHandler($path, $level);
        return new Logger('size_rotate', [$handler]);
    }
}
