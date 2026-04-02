<?php

namespace App\Http\Requests\Tenants\Master;

use App\Features\ProductExpired;
use App\Features\ProductStock;
use App\Models\Tenants\Category;
use App\Models\Tenants\Product;
use App\Models\Tenants\ProductImage;
use App\Rules\UniqueBarcode;
use App\Services\Tenants\ProductService;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * @property string $name Example: Coca Cola 1L
 * @property int $category Example: 1
 * @property int $stock Example: 100
 * @property int $initial_price Example: 5000
 * @property int $selling_price Example: 7000
 * @property string $type Example: product
 * @property bool $is_non_stock Example: false
 */
class ProductRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules(): array
    {
        $hasStorefrontVisibility = $this->has('show_in_storefront');

        if ($this->method() == 'DELETE') {
            return [];
        }

        if (! feature(ProductStock::class)) {
            $this->merge([
                'is_non_stock' => true,
            ]);
        }

        if (! $this->isMethod('put') && ! $hasStorefrontVisibility) {
            $this->merge([
                'show_in_storefront' => true,
            ]);
        }

        if ($this->get('is_non_stock', false)) {
            $this->merge([
                'stock' => 0,
            ]);
        }

        $primaryBarcodeId = null;

        if ($this->method() == 'PUT') {
            $product = Product::findorfail($this->route('product'));
            $primaryBarcodeId = $product->barcodes()->primary()->active()->value('id');
            $this->merge([
                'sku' => $this->filled('sku') ? $this->sku : $product->sku,
                'barcode' => $this->filled('barcode') ? $this->barcode : $product->barcodes()->primary()->active()->value('code'),
                'name' => $this->filled('name') ? $this->name : $product->name,
                'category' => $this->filled('category') ? $this->category : $product->category_id,
                'stock' => $this->filled('stock') ? $this->stock : $product->stock,
                'initial_price' => $this->filled('initial_price') ? $this->initial_price : $product->initial_price,
                'selling_price' => $this->filled('selling_price') ? $this->selling_price : $product->selling_price,
                'type' => $this->filled('type') ? $this->type : $product->type,
                'hero_images_url' => $this->filled('hero_images_url') ? $this->hero_images_url : $product->hero_images[0] ?? '',
                'is_non_stock' => $this->filled('is_non_stock') ? $this->is_non_stock : $product->is_non_stock,
                'show_in_storefront' => $hasStorefrontVisibility
                    ? $this->boolean('show_in_storefront')
                    : $product->show_in_storefront,
            ]);
        }

        $requireExpiredOnCreate = feature(ProductExpired::class) && $this->isMethod('post');
        $isNonStock = $this->boolean('is_non_stock');

        return [
            'sku' => ['nullable', Rule::unique(Product::class)->ignore($this->route('product'))],
            'barcode' => ['nullable', 'min:3', new UniqueBarcode($primaryBarcodeId)],
            'name' => ['required', 'min:3'],
            'category' => ['required'],
            'stock' => ['numeric', Rule::requiredIf(! $isNonStock)],
            'initial_price' => ['numeric', 'required', 'lte:selling_price'],
            'selling_price' => ['numeric', 'required', 'gte:initial_price'],
            'type' => ['required', Rule::in('product', 'service')],
            'hero_images_url' => ['nullable', 'string'],
            'is_non_stock' => ['boolean', 'required'],
            'show_in_storefront' => ['boolean', 'required'],
            'expired' => $requireExpiredOnCreate
                ? ['required', 'date', 'after_or_equal:now']
                : ['nullable', 'date', 'after_or_equal:now'],
        ];
    }

    public function created(): void
    {
        try {
            DB::beginTransaction();
            $product = new Product();
            $product->fill($this->merging());
            $product->save();
            $this->uploadImage($product);
            $this->syncPrimaryBarcode($product);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updated(): void
    {
        try {
            DB::beginTransaction();
            $product = Product::findorfail($this->route('product'));
            $product->fill($this->merging());
            $product->update();
            $this->uploadImage($product);
            $this->syncPrimaryBarcode($product);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function merging(): array
    {
        return $this->merge([
            'category_id' => Category::findorfail($this->category)->id,
        ])->except('category', 'images', 'barcode');
    }

    private function images(): ?array
    {
        return $this->images;
    }

    private function uploadImage(Product $product): void
    {
        if ($this->filled('hero_images_url') && $this->hero_images_url != ($product->hero_images[0] ?? '')) {
            $productService = new ProductService();
            $heroImages = $productService->proceedUploadImage(Str::of($this->hero_images_url)->explode(',')->toArray(), $product);
            $product->hero_images = $heroImages;
            $product->save();
        }
    }

    private function syncPrimaryBarcode(Product $product): void
    {
        if (! $this->filled('barcode')) {
            return;
        }

        $barcode = $this->barcode;
        $existing = $product->barcodes()->primary()->active()->first();

        if ($existing) {
            $existing->update([
                'code' => $barcode,
                'type' => 'primary',
                'is_active' => true,
            ]);

            return;
        }

        $product->barcodes()->create([
            'code' => $barcode,
            'type' => 'primary',
            'description' => __('Barcode from API'),
            'is_active' => true,
        ]);
    }

    public function deleteImages(): void
    {
        $product = $this->route('product');
        $images = ProductImage::where('product_id', $product->id)->get();
        foreach ($images as $image) {
            if (Storage::disk('public')->exists($image->name)) {
                Storage::disk('public')->delete($image->name);
            }
            $image->delete();
        }
    }
}
