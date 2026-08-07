<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;

class ImageService
{

    private const MAIN_WIDTH = 1200;
    private const WEBP_QUALITY = 85;
    private const STORAGE_PATH = 'uploads';

    private ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    public function processUpload(UploadedFile $file, string $folder = 'projects'): ?string
    {
        $start = microtime(true);

        try {
            $storagePath = self::STORAGE_PATH . "/{$folder}";

            if (!Storage::disk('public')->exists($storagePath)) {
                Storage::disk('public')->makeDirectory($storagePath, 0755, true);
            }

            $decodeStart = microtime(true);
            $image = $this->imageManager->decodePath($file->getRealPath());
            $decodeMs = round((microtime(true) - $decodeStart) * 1000, 2);
            $filename = Str::uuid() . '.webp';

            if ($image->width() > self::MAIN_WIDTH) {
                $image = $image->scaleDown(width: self::MAIN_WIDTH);
            }

            $encodeStart = microtime(true);
            $encoded = $image->encodeUsingFormat(Format::WEBP, quality: self::WEBP_QUALITY);
            $encodeMs = round((microtime(true) - $encodeStart) * 1000, 2);

            $fullPath = "{$storagePath}/{$filename}";
            $storagePath = Storage::disk('public')->path($fullPath);

            if (!is_dir(dirname($storagePath))) {
                mkdir(dirname($storagePath), 0755, true);
            }

            $saveStart = microtime(true);
            $encoded->save($storagePath);
            $saveMs = round((microtime(true) - $saveStart) * 1000, 2);

            if (!file_exists($storagePath)) {
                throw new \Exception("Failed to verify saved file at {$storagePath}");
            }

            Log::info('Image processed', [
                'width' => $image->width(),
                'height' => $image->height(),
                'size' => filesize($storagePath),
                'orig_bytes' => $file->getSize(),
                'decode_ms' => $decodeMs,
                'encode_ms' => $encodeMs,
                'save_ms' => $saveMs,
                'total_ms' => round((microtime(true) - $start) * 1000, 2),
            ]);

            return $fullPath;
        } catch (\Throwable $e) {
            Log::error('Image upload failed: ' . $e->getMessage());
            return null;
        }
    }

    public function deleteFile(?string $path): bool
    {
        if (empty($path)) {
            return true; // Nothing to delete
        }

        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                Log::info("Image deleted: {$path}");
            }

            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to delete image: {$path}", [
                'exception' => $e,
            ]);

            return false;
        }
    }

    public function deleteProjectImages(?string $mainPath): bool
    {
        return $this->deleteFile($mainPath);
    }

    public function getConfigInfo(): array
    {
        return [
            'main_width' => self::MAIN_WIDTH,
            'webp_quality' => self::WEBP_QUALITY,
            'storage_path' => self::STORAGE_PATH,
        ];
    }
}
