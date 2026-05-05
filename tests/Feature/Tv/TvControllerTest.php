<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

// Suite de pruebas para el controlador de TV (rutas pÃºblicas)
describe('TvController', function () {
    beforeEach(function () {
        // Popula la base de datos con los seeders.
        $this->seed();
    });

    test('guests can access the TV page', function () {
        // Verifica que un usuario no autenticado puede acceder a la página de TV.
        get(route('tv.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('tv/Index'));
    });

    test('authenticated users can access the TV page', function () {
        // Autentica a un usuario.
        actingAs(User::factory()->create());

        // Verifica que un usuario autenticado puede acceder a la página de TV.
        get(route('tv.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('tv/Index'));
    });
});
