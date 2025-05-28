<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesSeeder::class);
});

/** Helper: Admin autenticado */
function admin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');
    Sanctum::actingAs($user);
    return $user;
}

/** Helper: Cliente autenticado */
function cliente(): User
{
    $user = User::factory()->create();
    $user->assignRole('cliente');
    Sanctum::actingAs($user);
    return $user;
}

it('assigns a user to a ticket successfully', function () {
    $cliente = User::factory()->create();
    $cliente->assignRole('cliente');
    Sanctum::actingAs($cliente);

    $ticket = Ticket::factory()->create([
        'created_by' => $cliente->id,
        'assigned_user_id' => null,
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $responsible = User::factory()->create();
    $responsible->assignRole('atendente');

    $this->patchJson("/api/v1/tickets/{$ticket->id}/assign", [
        'assigned_user_id' => $responsible->id,
    ])
        ->assertOk()
        ->assertJsonFragment([
            'assigned_user_id' => $responsible->id,
        ]);

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'assigned_user_id' => $responsible->id,
    ]);
});

it('returns 403 if user is not admin', function () {
    cliente();

    $ticket = Ticket::factory()->create(['assigned_user_id' => null]);
    $responsible = User::factory()->create();

    $this->patchJson("/api/v1/tickets/{$ticket->id}/assign", [
        'assigned_user_id' => $responsible->id,
    ])->assertForbidden();
});

it('returns 401 if unauthenticated', function () {
    $ticket = Ticket::factory()->create();
    $responsible = User::factory()->create();

    $this->patchJson("/api/v1/tickets/{$ticket->id}/assign", [
        'assigned_user_id' => $responsible->id,
    ])->assertUnauthorized();
});

it('returns 404 if ticket does not exist', function () {
    admin();
    $responsible = User::factory()->create();

    $this->patchJson("/api/v1/tickets/9999/assign", [
        'assigned_user_id' => $responsible->id,
    ])->assertNotFound();
});

it('returns 422 if responsible user is invalid', function () {
    admin();
    $ticket = Ticket::factory()->create();

    $this->patchJson("/api/v1/tickets/{$ticket->id}/assign", [
        'assigned_user_id' => 9999,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['assigned_user_id']);
});
