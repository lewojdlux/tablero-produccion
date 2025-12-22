<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin-channel', function ($user) {
    return in_array($user->perfil_usuario_id, [1, 2]);
});



/*
|--------------------------------------------------------------------------
| Canal PRIVADO por USUARIO (notifications broadcast)
|--------------------------------------------------------------------------
*/
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
