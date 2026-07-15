<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('tenant.{tenantId}.sync', function ($user, $tenantId) {
    return tenant('id') === $tenantId;
});

Broadcast::channel('tenant.{tenantId}.worker.{userId}', function ($user, $tenantId, $userId) {
    return tenant('id') === $tenantId && (int) $user->id === (int) $userId;
});
