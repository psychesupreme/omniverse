<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * The attributes that are mass assignable in addition to
     * those defined by the base Stancl Tenant model.
     *
     * @return array<string, string>
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'subscription_plan_id',
            'stripe_customer_id',
            'trial_ends_at',
            'subscription_ends_at',
            'status',
        ];
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trial_ends_at'        => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    /**
     * Get the subscription plan associated with this tenant.
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Check if the tenant's trial period is still active.
     */
    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if the tenant has an active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        if ($this->onTrial()) {
            return true;
        }

        return $this->status === 'active'
            && $this->subscription_ends_at
            && $this->subscription_ends_at->isFuture();
    }

    /**
     * Check if the tenant has exceeded a given usage limit.
     */
    public function hasExceededLimit(string $attribute, int $currentCount): bool
    {
        $plan = $this->subscriptionPlan;

        if (! $plan) {
            return false;
        }

        if ($plan->isUnlimited($attribute)) {
            return false;
        }

        return $currentCount >= $plan->{$attribute};
    }
}