<?php

namespace App\Http\Controllers;

use App\Events\SettingUpdated;
use App\Models\Setting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    use AuthorizesRequests;

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(): Response
    {
        $this->authorize('viewAny', Setting::class); // Usamos viewAny para 'ver' la pantalla de edición
        // Obtenemos la configuración (siempre ID 1) o fallamos si no corriste el seeder
        $settings = Setting::firstOrFail();

        return Inertia::render('sistema/Edit', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->authorize('update', Setting::class);
        $settings = Setting::firstOrFail();

        $validated = $request->validate([
            // Organización
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',

            // Apariencia
            'logo' => 'nullable|image|max:2048', // Campo 'logo' en el form
            'footer_text' => 'nullable|string|max:255',
            'theme_color' => 'required|string|max:7', // Hex color #000000

            // Kiosco
            'display_notification' => 'nullable|string|max:500',
            'display_font_size' => 'required|integer|min:10|max:100',
            'display_font_color' => 'required|string|max:7',

            // Toggles
            'voice_enabled' => 'boolean',

            // Código Maestro Kiosco
            'kiosk_code' => 'sometimes|required|string|max:20',
            // Control de Tiempo (Minutos)
            'ticket_cooldown_minutes' => 'sometimes|required|integer|min:0|max:120',
        ]);

        // 1. Manejo del Logo (Si se subió uno nuevo)
        if ($request->hasFile('logo')) {
            // Borrar anterior si existe
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            // Guardar nuevo
            $validated['logo_path'] = $request->file('logo')->store('sistema', 'public');
        }

        // 2. Actualizar datos
        $settings->update($validated);

        SettingUpdated::dispatch();

        return back()->with('success', 'Configuración actualizada correctamente.');
    }

    public function regenerateToken(): RedirectResponse
    {
        $this->authorize('update', Setting::class);
        $settings = Setting::firstOrFail();

        // Generamos 200 caracteres aleatorios
        $settings->kiosk_token = Str::random(200);
        $settings->save();

        return back()->with('success', 'Token de seguridad regenerado correctamente.');
    }
}
