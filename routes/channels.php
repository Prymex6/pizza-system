<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private orders channel – only authenticated staff (managers, chefs, waiters, drivers)
Broadcast::channel('orders', function ($user) {
    return $user !== null;
}, ['guards' => ['tenant']]);

// Support channel for tenant – receives admin replies
Broadcast::channel('support.{tenantId}', function ($user) {
    return $user !== null;
}, ['guards' => ['tenant']]);

// Support admin channel – receives all new tickets and replies
Broadcast::channel('support-admin', function ($user) {
    return $user !== null;
}, ['guards' => ['super_admin']]);

Broadcast::channel('contact-admin', function ($user) {
    return $user !== null;
}, ['guards' => ['super_admin']]);

// Staff reports channel – only managers can listen
Broadcast::channel('staff-reports', function ($user) {
    return $user !== null && $user->role === 'manager';
}, ['guards' => ['tenant']]);

// Reviews channel – only managers can listen
Broadcast::channel('reviews-manager', function ($user) {
    return $user !== null && $user->role === 'manager';
}, ['guards' => ['tenant']]);

// Reservations channel – only managers can listen
Broadcast::channel('reservations-manager', function ($user) {
    return $user !== null && $user->role === 'manager';
}, ['guards' => ['tenant']]);
