<?php

namespace App\Services\Tenants;

use App\Models\Tenants\About;
use App\Models\Tenants\Category;
use App\Models\Tenants\Product;
use App\Models\Tenants\Setting;
use App\Support\Storage\StoredFileUrl;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MetaCatalogFeedService
{
    private const HEADERS = [
        'id',
        'title',
        'description',
        'availability',
        'condition',
        'price',
        'link',
        'image_link',
        'brand',
        'additional_image_link',
        'product_type',
        'status',
        'inventory',
        'fb_product_category',
        'google_product_category',
    ];

    public function resolveContext(): ?array
    {
        $baseUrl = $this->normalizeAbsoluteUrl(Setting::get('storefront_public_base_url'));

        if ($baseUrl === null) {
            Log::error('Meta catalog feed skipped because storefront_public_base_url is missing.', [
                'tenant_id' => tenant('id'),
                'host' => request()->getHost(),
            ]);

            return null;
        }

        $about = About::query()->first();
        $currency = strtoupper((string) Setting::get('currency', 'IDR'));
        $brand = $this->resolveBrand($about);

        $productUpdatedAt = Product::query()->storefrontVisible()->max('updated_at');
        $categoryUpdatedAt = Category::query()->max('updated_at');
        $aboutUpdatedAt = About::query()->max('updated_at');
        $settingUpdatedAt = Setting::query()
            ->whereIn('key', ['currency', 'storefront_public_base_url'])
            ->max('updated_at');

        $lastModified = collect([
            $productUpdatedAt,
            $categoryUpdatedAt,
            $aboutUpdatedAt,
            $settingUpdatedAt,
        ])
            ->filter()
            ->map(fn ($value) => CarbonImmutable::parse($value))
            ->sort()
            ->last();

        $etag = '"'.sha1(implode('|', [
            Product::query()->storefrontVisible()->count(),
            $productUpdatedAt,
            $categoryUpdatedAt,
            $aboutUpdatedAt,
            $settingUpdatedAt,
            $baseUrl,
            $currency,
            $brand,
        ])).'"';

        return [
            'base_url' => $baseUrl,
            'brand' => $brand,
            'currency' => $currency,
            'etag' => $etag,
            'last_modified' => $lastModified,
        ];
    }

    public function shouldReturnNotModified(Request $request, array $context): bool
    {
        $ifNoneMatch = $request->header('If-None-Match');

        if (filled($ifNoneMatch)) {
            $etags = collect(explode(',', $ifNoneMatch))
                ->map(fn (string $etag) => trim($etag))
                ->filter();

            if ($etags->contains($context['etag']) || $etags->contains('*')) {
                return true;
            }
        }

        $ifModifiedSince = $request->header('If-Modified-Since');

        if (blank($ifModifiedSince) || ! $context['last_modified'] instanceof CarbonImmutable) {
            return false;
        }

        try {
            return CarbonImmutable::parse($ifModifiedSince)->greaterThanOrEqualTo($context['last_modified']);
        } catch (\Throwable) {
            return false;
        }
    }

    public function headers(array $context): array
    {
        return array_filter([
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'inline; filename="meta-catalog.csv"',
            'Cache-Control' => 'public, max-age=300',
            'ETag' => $context['etag'] ?? null,
            'Last-Modified' => $context['last_modified']?->toRfc7231String(),
        ]);
    }

    public function stream(array $context): StreamedResponse
    {
        return response()->stream(function () use ($context): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, self::HEADERS);

            Product::query()
                ->storefrontVisible()
                ->with([
                    'category:id,name',
                    'images:id,product_id,name,url',
                ])
                ->chunkById(200, function (EloquentCollection $products) use ($handle, $context): void {
                    foreach ($products as $product) {
                        $row = $this->mapProduct($product, $context);

                        if ($row === null) {
                            continue;
                        }

                        fputcsv($handle, $row);
                    }

                    if (function_exists('flush')) {
                        flush();
                    }
                });

            fclose($handle);
        }, 200, $this->headers($context));
    }

    private function mapProduct(Product $product, array $context): ?array
    {
        $images = $this->normalizeImageUrls($product);

        if ($images === []) {
            Log::warning('Meta catalog feed excluded storefront product without a valid public image.', [
                'tenant_id' => tenant('id'),
                'product_id' => $product->getKey(),
                'sku' => $product->sku,
            ]);

            return null;
        }

        $stock = (float) $product->stock;
        $inventory = $stock <= 0 ? 0 : max(1, (int) round($stock));

        return [
            'id' => filled($product->sku) ? (string) $product->sku : (string) $product->getKey(),
            'title' => $this->plainText($product->name),
            'description' => $this->resolveDescription($product),
            'availability' => $stock > 0 ? 'in stock' : 'out of stock',
            'condition' => 'new',
            'price' => number_format((float) $product->selling_price, 2, '.', '').' '.$context['currency'],
            'link' => rtrim($context['base_url'], '/').'/product/'.$product->getKey(),
            'image_link' => $images[0],
            'brand' => $this->resolveProductBrand($product, $context['brand']),
            'additional_image_link' => implode(',', array_slice($images, 1)),
            'product_type' => $this->plainText($product->category?->name),
            'status' => 'active',
            'inventory' => $inventory,
            'fb_product_category' => '',
            'google_product_category' => '',
        ];
    }

    private function resolveDescription(Product $product): string
    {
        $description = $this->plainText(data_get($product->getAttributes(), 'description'));

        if ($description !== '') {
            return $description;
        }

        return $this->plainText($product->name);
    }

    private function resolveProductBrand(Product $product, string $fallbackBrand): string
    {
        $brand = $this->plainText(data_get($product->getAttributes(), 'brand'));

        return $brand !== '' ? $brand : $fallbackBrand;
    }

    private function resolveBrand(?About $about): string
    {
        $brand = $this->plainText($about?->shop_name);

        if ($brand !== '') {
            return $brand;
        }

        return $this->plainText(config('app.name', 'Lakasir'));
    }

    private function normalizeImageUrls(Product $product): array
    {
        $candidates = [];

        $heroImages = $product->hero_images;

        if (is_string($heroImages)) {
            $heroImages = Str::of($heroImages)->explode(',')->all();
        }

        if ($heroImages instanceof \Illuminate\Support\Collection) {
            $heroImages = $heroImages->all();
        }

        if (is_array($heroImages)) {
            $candidates = [...$candidates, ...$heroImages];
        } elseif (filled($heroImages)) {
            $candidates[] = $heroImages;
        }

        if ($product->relationLoaded('images')) {
            foreach ($product->images as $image) {
                $candidates[] = $image->url ?: $image->name;
            }
        }

        return collect($candidates)
            ->map(fn ($candidate) => $this->normalizeImageUrl($candidate))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeImageUrl(mixed $candidate): ?string
    {
        if (! is_string($candidate) || blank(trim($candidate))) {
            return null;
        }

        $candidate = trim($candidate);
        $rebasedStorageUrl = StoredFileUrl::toCurrentPublicUrl($candidate);

        if ($rebasedStorageUrl !== null) {
            return $this->absoluteUrl($rebasedStorageUrl);
        }

        if (Str::startsWith($candidate, ['http://', 'https://'])) {
            return $candidate;
        }

        $path = ltrim((string) Str::of(parse_url($candidate, PHP_URL_PATH) ?? $candidate)->after('/storage/'), '/');

        if ($path === '') {
            $path = ltrim($candidate, '/');
        }

        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return $this->absoluteUrl(Storage::disk('public')->url($path));
    }

    private function absoluteUrl(string $url): string
    {
        return Str::startsWith($url, ['http://', 'https://']) ? $url : url($url);
    }

    private function plainText(?string $value): string
    {
        return Str::of(strip_tags((string) $value))
            ->replaceMatches('/\s+/u', ' ')
            ->trim()
            ->value();
    }

    private function normalizeAbsoluteUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $url = trim((string) $url);

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        if (blank(parse_url($url, PHP_URL_SCHEME)) || blank(parse_url($url, PHP_URL_HOST))) {
            return null;
        }

        return rtrim($url, '/');
    }
}
