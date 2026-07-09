<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Domain events expose ids/slugs/errors for drafts too — panel users only.
Broadcast::channel(
    (string) config('domain_events.broadcast.channel', 'novacms.domain-events'),
    fn ($user) => $user instanceof \App\Models\User && $user->canAccessAdminPanel(),
);
