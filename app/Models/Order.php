<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'order_number',
        'user_id',
        'outlet_id',
        'total_amount',
        'status',
        'notes',
        'placed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'placed_at'    => 'datetime',
        ];
    }

    /**
     * Get the worker/user who placed the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the outlet where the order was placed.
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    /**
     * Get items included in this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
