<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;


Broadcast::routes([
    'middleware' => ['web', 'auth'],
]);
