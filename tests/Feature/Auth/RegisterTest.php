<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesSeeder::class);
});

it('registers a user successfully', function () {
    $payload = [
        'full_name' => 'Wesley Safadão',
        'username' => 'wesley123',
        'email' => 'wesley@example.com',
        'birth_date' => '1990-01-01',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ];

    $response = $this->postJson('/api/v1/register', $payload);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'user' => [
                'id',
                'full_name',
                'username',
                'email',
                'birth_date'
            ],
            'token',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'wesley@example.com',
        'username' => 'wesley123',
    ]);
});

it('fails when required fields are missing', function () {
    $response = $this->postJson('/api/v1/register', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'full_name',
            'username',
            'email',
            'birth_date',
            'password',
        ]);
});

it('fails when password is not confirmed', function () {
    $payload = [
        'full_name' => 'Fulano',
        'username' => 'fulano123',
        'email' => 'fulano@example.com',
        'birth_date' => '2000-01-01',
        'password' => 'password123',
    ];

    $response = $this->postJson('/api/v1/register', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('fails when username is already taken', function () {
    \App\Models\User::factory()->create([
        'username' => 'usertaken',
    ]);

    $payload = [
        'full_name' => 'Novo Usuário',
        'username' => 'usertaken',
        'email' => 'novo@example.com',
        'birth_date' => '1990-01-01',
        'password' => 'senha1234',
        'password_confirmation' => 'senha1234',
    ];

    $response = $this->postJson('/api/v1/register', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['username']);
});
