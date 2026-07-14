<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'price_monthly',
        'price_annual',
        'max_users',
        'max_outlets',
        'features',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_annual'  => 'decimal:2',
            'max_users'     => 'integer',
            'max_outlets'   => 'integer',
            'features'      => 'array',
        ];
    }

    /**
     * Check if a given limit is unlimited (-1).
     */
    public function isUnlimited(string $attribute): bool
    {
        return $this->{$attribute} === -1;
    }

    /**
     * Get the tenants subscribed to this plan.
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'subscription_plan_id');
    }
}
