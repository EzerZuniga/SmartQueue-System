<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReniecService
{
    /**
     * Busca los datos de un ciudadano por su DNI.
     * Retorna el nombre completo si es válido, o null si falla.
     */
    public function search(string $dni): ?string
    {
        if (! preg_match('/^\d{8}(\d{3})?$/', $dni)) {
            return null;
        }

        if ((bool) config('services.reniec.mock', ! app()->isProduction())) {
            return $this->mockName($dni);
        }

        $token = config('services.reniec.token');
        $baseUrl = rtrim((string) config('services.reniec.base_url', 'https://api.apis.net.pe/v1'), '/');
        $timeout = (int) config('services.reniec.timeout', 6);

        if (! $token) {
            Log::warning('RENIEC token is not configured.');

            return null;
        }

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->timeout($timeout)
                ->get($baseUrl.'/dni', ['numero' => $dni]);

            if (! $response->successful()) {
                Log::warning('RENIEC lookup failed.', [
                    'status' => $response->status(),
                    'dni' => $dni,
                ]);

                return null;
            }

            $data = $response->json();
            if (! is_array($data)) {
                return null;
            }

            $fullName = trim(implode(' ', array_filter([
                $data['nombres'] ?? null,
                $data['apellidoPaterno'] ?? null,
                $data['apellidoMaterno'] ?? null,
            ])));

            return $fullName !== '' ? $fullName : null;

        } catch (\Exception $e) {
            Log::error('RENIEC connection error: '.$e->getMessage());

            return null;
        }
    }

    private function mockName(string $dni): string
    {
        $nombres = ['Juan', 'Maria', 'Carlos', 'Ana', 'Luis', 'Sofia'];
        $apellidos = ['Perez', 'Garcia', 'Lopez', 'Torres', 'Ramirez', 'Diaz'];

        $seed = abs(crc32($dni));

        $nombre = $nombres[$seed % count($nombres)];
        $apellidoPaterno = $apellidos[($seed >> 4) % count($apellidos)];
        $apellidoMaterno = $apellidos[($seed >> 8) % count($apellidos)];

        return "$nombre $apellidoPaterno $apellidoMaterno";
    }
}
