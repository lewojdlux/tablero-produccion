<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin-channel', function ($user) {
    return in_array($user->perfil_usuario_id, [1, 2]);
});
