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

it('permite que admin feche um ticket aberto', function () {
    $admin = User::factory()->create()->assignRole('admin');
    Sanctum::actingAs($admin);

    $ticket = Ticket::factory()->create(['closed_at' => null]);

    $this->patchJson("/api/v1/tickets/{$ticket->id}/close")
        ->assertOk()
        ->assertJson([
            'message' => 'Ticket fechado com sucesso.',
            'ticket' => ['id' => $ticket->id],
        ]);

    expect($ticket->fresh()->closed_at)->not->toBeNull();
});

it('retorna 400 se ticket já estiver fechado', function () {
    $admin = User::factory()->create()->assignRole('admin');
    Sanctum::actingAs($admin);

    $ticket = Ticket::factory()->create(['closed_at' => now()]);

    $this->patchJson("/api/v1/tickets/{$ticket->id}/close")
        ->assertStatus(400)
        ->assertJson(['message' => 'Ticket já está fechado.']);
});

it('retorna 404 se ticket não existir', function () {
    $admin = User::factory()->create()->assignRole('admin');
    Sanctum::actingAs($admin);

    $this->patchJson("/api/v1/tickets/99999/close")
        ->assertNotFound();
});

it('retorna 403 se cliente tentar fechar ticket', function () {
    $cliente = User::factory()->create()->assignRole('cliente');
    Sanctum::actingAs($cliente);

    $ticket = Ticket::factory()->create();

    $this->patchJson("/api/v1/tickets/{$ticket->id}/close")
        ->assertForbidden();
});

it('retorna 401 se não autenticado', function () {
    $ticket = Ticket::factory()->create();

    $this->patchJson("/api/v1/tickets/{$ticket->id}/close")
        ->assertUnauthorized();
});
