<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\CallStatuse;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response;

class TvController extends Controller
{
    public function index(Request $request): Response
    {
        $serviceId = null;

        if ($request->has('service')) {
            try {
                $serviceId = (int) Crypt::decryptString($request->input('service'));
            } catch (\Exception $e) {
                // Si falla la desencriptación, ignoramos el filtro o podríamos redirigir
                $serviceId = null;
            }
        }

        // 1. Obtener la Última Llamada Activa (La que está en el centro)
        // Estados: Calling (2) o In Progress (3)
        $statusesActive = CallStatuse::whereIn('slug', ['calling', 'in_progress'])->pluck('id');

        $currentCall = Call::whereIn('call_status_id', $statusesActive)
            ->today()
            ->when($serviceId, function ($query, $serviceId) {
                $query->whereHas('ticket', function ($q) use ($serviceId) {
                    $q->where('service_id', $serviceId);
                });
            })
            ->with(['ticket', 'counter', 'callStatus'])
            ->latest('updated_at')
            ->first();

        // 2. Historial (Las últimas 4 ya atendidas o pasadas)
        $history = Call::whereNotNull('called_at')
            ->today()
            ->where('id', '!=', $currentCall?->id) // Excluir la actual
            ->when($serviceId, function ($query, $serviceId) {
                $query->whereHas('ticket', function ($q) use ($serviceId) {
                    $q->where('service_id', $serviceId);
                });
            })
            ->with(['ticket', 'counter', 'callStatus'])
            ->latest('updated_at')
            ->limit(5)
            ->get();

        // 3. Configuración
        $settings = Setting::first();

        return Inertia::render('tv/Index', [
            'initialCurrentCall' => $currentCall,
            'initialHistory' => $history,
            'currentServiceId' => $serviceId, // Pasamos el ID real para filtrar WebSockets
            'settings' => $settings,
            'layoutConfig' => [
                'themeColor' => $settings->theme_color,
            ],
        ]);
    }
}
