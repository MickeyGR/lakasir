<?php

namespace App\Models\Tenants;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Proforma extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'member_id',
        'number',
        'total_price',
        'status',
        'notes'
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
}
