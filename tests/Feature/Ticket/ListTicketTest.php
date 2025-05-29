<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(\Database\Seeders\RolesSeeder::class);
});

it('permite que admin veja todos os tickets', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    Ticket::factory()->count(3)->create();

    $this->getJson('/api/v1/tickets')
        ->assertOk()
        ->assertJsonCount(3);
});

it('permite que atendente veja apenas seus tickets atribuídos', function () {
    $atendente = User::factory()->create();
    $atendente->assignRole('atendente');
    $atendente->load('roles');
    Sanctum::actingAs($atendente);

    Ticket::factory()->count(2)->create([
        'assigned_user_id' => $atendente->id,
    ]);

    Ticket::factory()->count(3)->create();

    $this->getJson('/api/v1/tickets')
        ->assertOk()
        ->assertJsonCount(2);
});

it('impede que cliente acesse a listagem de tickets', function () {
    $cliente = User::factory()->create();
    $cliente->assignRole('cliente');
    Sanctum::actingAs($cliente);

    $this->getJson('/api/v1/tickets')
        ->assertForbidden()
        ->assertJson(['message' => 'Acesso negado. Role necessária: admin|atendente']);
});

it('retorna 401 para não autenticado tentando listar tickets', function () {
    $this->getJson('/api/v1/tickets')
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});
