<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
}
