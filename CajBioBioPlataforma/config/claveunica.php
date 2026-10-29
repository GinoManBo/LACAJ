<?php

return [
    'enabled' => (bool) env('CLAVEUNICA_ENABLED', false),
    'client_id' => env('CLAVEUNICA_CLIENT_ID'),
    'client_secret' => env('CLAVEUNICA_CLIENT_SECRET'),
    'redirect_uri' => env('CLAVEUNICA_REDIRECT_URI'),
    'scope' => 'openid run name',

    'authorize_url' => env('CLAVEUNICA_AUTHORIZE_URL', 'https://accounts.claveunica.gob.cl/openid/authorize/'),
    'token_url' => env('CLAVEUNICA_TOKEN_URL', 'https://accounts.claveunica.gob.cl/openid/token/'),
    'userinfo_url' => env('CLAVEUNICA_USERINFO_URL', 'https://www.claveunica.gob.cl/openid/userinfo/'),
    'logout_url' => env('CLAVEUNICA_LOGOUT_URL', 'https://accounts.claveunica.gob.cl/api/v1/accounts/app/logout'),
];
