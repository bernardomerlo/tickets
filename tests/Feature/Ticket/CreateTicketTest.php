<?php

use App\Enums\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesSeeder::class);
});

/** Helper para autenticar rapidamente */
function acting(): User
{
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $user->assignRole('cliente');
    return $user;
}

it('creates a ticket successfully', function () {
    acting();

    $payload = [
        'title' => 'Servidor fora do ar',
        'description' => 'Apache da intranet caiu às 9h',
        'type' => TicketType::TI->value,
    ];

    $this->postJson('/api/v1/tickets', $payload)
        ->assertCreated()
        ->assertJsonFragment([
            'title' => $payload['title'],
            'closed_at' => null,
            'assigned_user_id' => null,
        ]);

    $this->assertDatabaseHas('tickets', [
        'title' => $payload['title'],
        'type' => TicketType::TI->value,
    ]);
});

it('returns 401 when unauthenticated', function () {
    $payload = [
        'title' => 'Queda de link',
        'description' => 'Sem internet',
        'type' => TicketType::TI->value,
    ];

    $this->postJson('/api/v1/tickets', $payload)
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('validates required fields', function () {
    acting();

    $this->postJson('/api/v1/tickets', [])
    ->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'description', 'type']);
});

it('rejects an invalid type', function () {
    acting();

    $payload = [
        'title' => 'Mudança de layout',
        'description' => 'Trocar banner do site',
        'type' => 'piratas-do-caribe',
    ];

    $this->postJson('/api/v1/tickets', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['type']);
});

it('blocks prohibited fields', function () {
    acting();

    $payload = [
        'title' => 'Testando campos proibidos',
        'description' => 'Tentativa de injeção',
        'type' => TicketType::MARKETING->value,
        'opened_at' => now()->subDay()->toISOString(),
        'closed_at' => now()->toISOString(),
        'assigned_user_id' => User::factory()->create()->id,
    ];

    $this->postJson('/api/v1/tickets', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'opened_at',
            'closed_at',
            'assigned_user_id',
        ]);
});
