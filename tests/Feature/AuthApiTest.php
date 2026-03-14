<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login()
    {

        $user = User::factory()->create([
            'email' => 'test@test.com',
            'password' => Hash::make('password')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@test.com',
            'password' => 'password'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'user',
                     'token'
                 ]);
    }

    public function test_user_can_register()
{

    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@test.com',
        'password' => 'password',
        'password_confirmation' => 'password'
    ]);

    $response->assertStatus(201)
             ->assertJsonStructure([
                 'user',
                 'token'
             ]);
}

public function test_login_fails_with_wrong_password()
{

    $user = User::factory()->create([
        'email' => 'test@test.com',
        'password' => Hash::make('password')
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@test.com',
        'password' => 'wrongpassword'
    ]);

    $response->assertStatus(401);
}



public function test_user_can_logout()
{

    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/logout');

    $response->assertStatus(200);
}

}