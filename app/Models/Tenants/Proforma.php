<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Proforma extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'member_id',
        'number',
        'total_price',
        'tax_price',
        'discount_price',
        'total_discount_per_item',
        'tax',
        'status',
        'notes'
    ];

    protected $appends = [
        'grand_total_price',
    ];

    public function details()
    {
        return $this->hasMany(ProformaDetail::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Calcular el total final de la proforma (igual que en Selling)
     */
    public function grandTotalPrice(): Attribute
    {
        return Attribute::make(get: fn () => $this->total_price - $this->tax_price - $this->total_discount_per_item - $this->discount_price);
    }
}