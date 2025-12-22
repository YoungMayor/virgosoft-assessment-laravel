<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    /** @use HasFactory<\Database\Factories\TradeFactory> */
    protected $fillable = [
        'buyer_id',
        'seller_id',
        'symbol',
        'price',
        'amount',
        'fee',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:10',
            'amount' => 'decimal:10',
            'fee' => 'decimal:10',
        ];
    }

    public function buyer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
