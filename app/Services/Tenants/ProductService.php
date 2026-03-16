<?php

namespace App\Services\Tenants;

use App\Models\Tenants\Product;
use App\Models\Tenants\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    public function proceedUploadImage(array $heroImages, Product $product): array
    {
        $uploadedHeroImages = [];
        $tmp = UploadedFile::whereIn('url', $heroImages)->get();
        /** @var UploadedFile $item */
        foreach ($tmp as $item) {
            $url = $item->moveToPuplic('product');
            $uploadedHeroImages[] = $url;
        }
        foreach ($product->hero_images as $image) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = UploadedFile::where('url', $image)->first();
            if ($uploadedFile) {
                $uploadedFile->deleteFromPublic('product');
            } else {
                $this->deletePublicFileFromUrl($image);
            }
        }

        return $uploadedHeroImages;
    }

    public function handleCreateUploadedFile(array $heroImages): array
    {
        $urls = [];
        foreach ($heroImages as $heroImage => $originalName) {
            $name = ltrim($heroImage, '/');

            if (! Storage::disk('public')->exists($name)) {
                continue;
            }

            $url = optional(Storage::disk('public'))->url($name);
            $urls[] = $url;
            if (! UploadedFile::where('url', $url)->exists()) {
                UploadedFile::create([
                    'name' => Str::of($name)->replace('product/', ''),
                    'original_name' => $originalName,
                    'url' => $url,
                    'mime_type' => optional(Storage::disk('public'))->mimeType($name),
                    'extension' => File::extension($name),
                    'size' => Storage::disk('public')->size($name),
                    'disk' => 'public',
                    'path' => Storage::disk('public')->path($name),
                ]);
            }
        }

        return $urls;
    }

    public function handleDeleteUploadedFile(array $heroImages): void
    {
        foreach ($heroImages as $heroImage) {
            $uploadedFile = UploadedFile::where('url', $heroImage)->first();
            if ($uploadedFile) {
                $uploadedFile->deleteFromPublic('product');
                $uploadedFile->delete();
            } else {
                $this->deletePublicFileFromUrl($heroImage);
            }
        }
    }

    private function deletePublicFileFromUrl(string $url): void
    {
        $path = ltrim(Str::of(parse_url($url, PHP_URL_PATH) ?? '')->after('/storage/')->value(), '/');

        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
