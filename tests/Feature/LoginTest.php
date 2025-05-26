<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs in successfully with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'wesley@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $payload = [
        'email' => 'wesley@example.com',
        'password' => 'secret123',
    ];

    $response = $this->postJson('/api/v1/login', $payload);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'user' => ['id', 'full_name', 'username', 'email', 'birth_date'],
            'token',
        ]);
});

it('fails to login with wrong credentials', function () {
    User::factory()->create([
        'email' => 'wrong@example.com',
        'password' => Hash::make('correct_password'),
    ]);

    $payload = [
        'email' => 'wrong@example.com',
        'password' => 'wrong_password',
    ];

    $response = $this->postJson('/api/v1/login', $payload);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Credenciais inválidas.'
        ]);
});
