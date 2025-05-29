<?php
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(\Database\Seeders\RolesSeeder::class);
});

it('permite que admin veja todos os tickets abertos', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    Ticket::factory()->count(2)->create(['closed_at' => null]);
    Ticket::factory()->count(1)->create(['closed_at' => now()]);

    $this->getJson('/api/v1/tickets?status=abertos')
        ->assertOk()
        ->assertJsonCount(2);
});

it('permite que admin veja todos os tickets fechados', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    Ticket::factory()->count(2)->create(['closed_at' => null]);
    Ticket::factory()->count(3)->create(['closed_at' => now()]);

    $this->getJson('/api/v1/tickets?status=fechados')
        ->assertOk()
        ->assertJsonCount(3);
});

it('permite que atendente veja apenas seus tickets atribuídos e abertos', function () {
    $atendente = User::factory()->create();
    $atendente->assignRole('atendente');
    Sanctum::actingAs($atendente);

    Ticket::factory()->count(2)->create([
        'assigned_user_id' => $atendente->id,
        'closed_at' => null
    ]);

    Ticket::factory()->count(2)->create([
        'assigned_user_id' => $atendente->id,
        'closed_at' => now()
    ]);

    Ticket::factory()->count(3)->create([
        'closed_at' => null
    ]);

    $this->getJson('/api/v1/tickets?status=abertos')
        ->assertOk()
        ->assertJsonCount(2);

    $this->getJson('/api/v1/tickets?status=fechados')
        ->assertOk()
        ->assertJsonCount(2);
});

it('impede que cliente acesse a listagem de tickets', function () {
    $cliente = User::factory()->create();
    $cliente->assignRole('cliente');
    Sanctum::actingAs($cliente);

    $this->getJson('/api/v1/tickets?status=abertos')
        ->assertForbidden()
        ->assertJson(['message' => 'Acesso negado. Role necessária: admin|atendente']);
});

it('retorna 401 para não autenticado tentando listar tickets', function () {
    $this->getJson('/api/v1/tickets?status=abertos')
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('retorna 400 quando status é inválido', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/tickets?status=invalido')
        ->assertStatus(400)
        ->assertJson(['message' => 'Parâmetro de status inválido. Use "abertos" ou "fechados".']);
});
