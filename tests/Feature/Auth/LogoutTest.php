<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function issueToken(User $user): array
{
    $newToken = $user->createToken('api_token');
    $plain = $newToken->plainTextToken;
    $tokenId = $newToken->accessToken->id;
    return [$plain, $tokenId];
}

it('logs out successfully and revokes ONLY the current token', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'email' => 'wesley@example.com',
        'password' => Hash::make('secret123'),
    ]);

    [$mobileToken] = issueToken($user);
    [$webToken, $webId] = issueToken($user);

    $response = $this->withHeaders([
        'Authorization' => "Bearer {$mobileToken}",
    ])->postJson('/api/v1/logout');

    $response->assertStatus(200)
        ->assertExactJson(['message' => 'Logout realizado com sucesso.']);

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => explode('|', $mobileToken)[0],
    ]);

    $this->assertDatabaseHas('personal_access_tokens', [
        'id' => $webTokenId = explode('|', $webToken)[0],
    ]);
});

it('returns 401 when trying to logout without a token', function () {
    $response = $this->postJson('/api/v1/logout');

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.'
        ]);
});

it('returns 401 when token is invalid or already revoked', function () {
    $fake = '99999999|thisisinvalid';

    $response = $this->withHeaders([
        'Authorization' => "Bearer {$fake}",
    ])->postJson('/api/v1/logout');

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.'
        ]);
});
