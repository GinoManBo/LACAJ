<?php

namespace App\Services;

use App\Support\Rut;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class ClaveUnica
{
    public static function habilitada(): bool
    {
        return config('claveunica.enabled')
            && config('claveunica.client_id')
            && config('claveunica.client_secret')
            && config('claveunica.redirect_uri');
    }

    public static function urlAutorizacion(): array
    {
        $state = Str::random(40);
        $url = config('claveunica.authorize_url').'?'.http_build_query([
            'client_id' => config('claveunica.client_id'),
            'response_type' => 'code',
            'scope' => config('claveunica.scope'),
            'redirect_uri' => config('claveunica.redirect_uri'),
            'state' => $state,
        ]);

        return [$url, $state];
    }

    /** @throws RuntimeException si el Estado rechaza el canje */
    public static function perfil(string $code, string $state): array
    {
        $token = Http::asForm()->timeout(15)->post(config('claveunica.token_url'), [
            'client_id' => config('claveunica.client_id'),
            'client_secret' => config('claveunica.client_secret'),
            'redirect_uri' => config('claveunica.redirect_uri'),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'state' => $state,
        ]);
        if ($token->failed() || ! $token->json('access_token')) {
            throw new RuntimeException('ClaveÚnica rechazó el código de autorización.');
        }

        $info = Http::withToken($token->json('access_token'))->timeout(15)->post(config('claveunica.userinfo_url'));
        if ($info->failed() || ! $info->json('RolUnico.numero')) {
            throw new RuntimeException('ClaveÚnica no entregó el RUN del usuario.');
        }

        $rut = Rut::normalizar($info->json('RolUnico.numero').$info->json('RolUnico.DV'));

        return [
            'rut' => $rut,
            'nombre' => trim(implode(' ', (array) $info->json('name.nombres'))),
            'apellido' => trim(implode(' ', (array) $info->json('name.apellidos'))),
        ];
    }
}
