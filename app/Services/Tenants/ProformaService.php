<?php

namespace App\Services\Tenants;

use App\Models\Tenants\PriceUnit;
use App\Models\Tenants\Product;
use App\Models\Tenants\Proforma;
use App\Models\Tenants\Setting;
use App\Services\VoucherService;
use Exception;
use Illuminate\Support\Facades\DB;

class ProformaService
{
    public function create(array $data)
    {
        try {
            DB::beginTransaction();
            
            // Calcular totales usando la misma lógica que SellingService
            $calculatedData = $this->calculateTotals($data);
            
            // Crear la proforma con los datos calculados
            $proforma = Proforma::create(array_merge($data, $calculatedData));
            
            // Crear los detalles
            if (isset($data['details'])) {
                foreach ($data['details'] as $detail) {
                    $proforma->details()->create($detail);
                }
            }
            
            DB::commit();
            
            return $proforma;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(Proforma $proforma, array $data)
    {
        try {
            DB::beginTransaction();
            
            // Calcular totales
            $calculatedData = $this->calculateTotals($data);
            
            // Actualizar la proforma
            $proforma->update(array_merge($data, $calculatedData));
            
            // Actualizar detalles si se proporcionan
            if (isset($data['details'])) {
                // Eliminar detalles existentes
                $proforma->details()->delete();
                
                // Crear nuevos detalles
                foreach ($data['details'] as $detail) {
                    $proforma->details()->create($detail);
                }
            }
            
            DB::commit();
            
            return $proforma;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function calculateTotals(array $data): array
    {
        if (Setting::get('default_tax', 0) != 0 && !isset($data['tax'])) {
            $data['tax'] = Setting::get('default_tax');
        }

        $total_price = 0;
        $total_discount_per_item = 0;
        $total_cost = 0;

        // Calcular totales de productos (usando la misma lógica que SellingService)
        if (isset($data['details'])) {
            $productsCollection = collect($data['details']);
            $productsCollection->each(
                function ($product) use (&$total_price, &$total_cost, &$total_discount_per_item) {
                    if (isset($product['price_unit_id']) && $product['price_unit_id'] != null) {
                        $product['price'] = PriceUnit::whereId($product['price_unit_id'])->first()->selling_price * $product['qty'];
                    }
                    $modelProduct = Product::find($product['product_id']);
                    $total_price += $product['price'] ?? $modelProduct->selling_price * $product['qty'];
                    $total_discount_per_item += ($product['discount_price'] ?? 0);
                    $total_cost += $modelProduct->initial_price * $product['qty'];
                }
            );
        }

        // Calcular impuesto
        $tax = $data['tax'] ?? 0;
        $tax_price = $total_price * $tax / 100;
        $total_price = $tax_price + $total_price;

        // Calcular descuento global
        $discount_price = $data['discount_price'] ?? 0;
        if ($data['voucher'] ?? false) {
            $voucherService = new VoucherService();
            if ($voucher = $voucherService->applyable($data['voucher'], $total_price)) {
                $discount_price = $voucher->calculate();
                $voucher->reduceUsed();
            }
        }

        return [
            'total_price' => $total_price,
            'tax_price' => $tax_price,
            'tax' => $tax,
            'discount_price' => $discount_price,
            'total_discount_per_item' => $total_discount_per_item,
        ];
    }
}