<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ProformaDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'proforma_id',
        'product_id',
        'qty',
        'price',
        'discount_price'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function proforma()
    {
        return $this->belongsTo(Proforma::class);
    }

    /**
     * Precio por unidad (igual que en SellingDetail)
     */
    public function pricePerUnit(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->price / $this->qty,
        );
    }

    /**
     * Precio total del ítem después del descuento (igual que en SellingDetail)
     */
    public function totalPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->price - $this->discount_price,
        );
    }
}