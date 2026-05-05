<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

// Suite de pruebas para el controlador de ajustes (SettingController)
describe('SettingController', function () {
    beforeEach(function () {
        // Popula la base de datos con los seeders, que deben incluir la configuración predeterminada.
        $this->seed();
    });

    // Pruebas para usuarios no autenticados
    describe('unauthenticated users', function () {
        test('cannot access settings page', function () {
            // Verifica que un usuario no autenticado es redirigido al intentar ver la página de ajustes.
            get(route('settings.edit'))->assertRedirect(route('login'));
        });

        test('cannot update settings', function () {
            // Intenta actualizar los ajustes sin autenticar.
            $setting = Setting::first();
            put(route('settings.update', $setting), [])->assertRedirect(route('login'));
        });
    });

    // Pruebas para usuarios autenticados
    describe('authenticated users', function () {
        beforeEach(function () {
            // Autentica a un usuario antes de cada prueba.
            $this->user = User::factory()->create();
            actingAs($this->user);
        });

        // Pruebas "Happy Path"
        test('can view settings page', function () {
            // Verifica que un usuario autenticado puede ver la página de ajustes.
            get(route('settings.edit'))
                ->assertStatus(200)
                ->assertInertia(fn ($page) => $page->component('sistema/Edit'));
        });

        test('can update settings', function () {
            // Asegura que existe una entrada de configuración para actualizar.
            $setting = Setting::first(); // Obtiene las configuraciones

            // Nuevos datos para los ajustes.
            $updatedData = [
                'name' => 'Updated App Name',
                'theme_color' => '#2563RT',
                'display_font_size' => '26',
                'display_font_color' => '#1f2956',
            ];

            // Envía la petición para actualizar los ajustes.
            put(route('settings.update', $setting), $updatedData)
                ->assertRedirect()
                ->assertSessionHas('success');

            // Verifica que los datos de los ajustes fueron actualizados en la base de datos.
            assertDatabaseHas('settings', [
                'name' => 'Updated App Name',
                'theme_color' => '#2563RT',
                'display_font_size' => '26',
                'display_font_color' => '#1f2956',
            ]);
        });

        // Pruebas de Validación
        test('settings update requires name', function () {
            // Asegura que existe una entrada de configuración.
            $setting = Setting::firstOrCreate([]);

            // Envía una petición de actualización sin nombre.
            put(route('settings.update', $setting), ['name' => ''])
                ->assertSessionHasErrors(['name']);
        });

        /*test('settings update requires kiosk_token to be string', function () {
            // Asegura que existe una entrada de configuración.
            $setting = Setting::firstOrCreate([]);

            // Envía una petición con kiosk_token no válido.
            put(route('settings.update', $setting), ['kiosk_token' => 123])
                ->assertSessionHasErrors(['kiosk_token']);
        });*/

        // Pruebas de Casos Límite
        /*test('updating a non-existent setting returns 404', function () {
            // Intenta actualizar un ajuste con un ID que no existe (asumiendo que Setting::first() siempre existe o se crea).
            // Si solo hay una fila, esta prueba es mÃ¡s teÃ³rica que prÃ¡ctica a menos que se fuerce un ID no vÃ¡lido.
            put(route('settings.update', 999), [
                'name' => 'Updated App Name',
                'theme_color' => '#2563RT',
                'display_font_size' => '26',
                'display_font_color' => '#1f2956',
            ])
                ->assertStatus(404);
        });*/
    });
});
