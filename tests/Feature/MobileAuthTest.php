<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_user_can_register_and_receive_a_token(): void
    {
        $response = $this->postJson('/api/mobile/register', [
            'username' => 'new-user',
            'password' => 'password123',
            'device_name' => 'ios',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.username', 'new-user')
            ->assertJsonStructure(['token', 'user' => ['id', 'username']]);

        $user = User::where('username', 'new-user')->firstOrFail();
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'ios']);
    }

    public function test_mobile_user_can_login_and_use_the_token(): void
    {
        User::factory()->create([
            'username' => 'mobile-user',
            'password' => 'password123',
        ]);

        $login = $this->postJson('/api/mobile/login', [
            'username' => 'mobile-user',
            'password' => 'password123',
        ])->assertOk();

        $this->withToken($login->json('token'))
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('username', 'mobile-user');
    }

    public function test_invalid_mobile_credentials_are_rejected(): void
    {
        User::factory()->create([
            'username' => 'mobile-user',
            'password' => 'password123',
        ]);

        $this->postJson('/api/mobile/login', [
            'username' => 'mobile-user',
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('username');
    }

    public function test_mobile_registration_validates_password_and_username_uniqueness(): void
    {
        User::factory()->create(['username' => 'existing-user']);

        $this->postJson('/api/mobile/register', [
            'username' => 'existing-user',
            'password' => 'short',
        ])->assertUnprocessable()->assertJsonValidationErrors(['username', 'password']);
    }

    public function test_mobile_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $currentToken = $user->createToken('current')->plainTextToken;
        $user->createToken('other');

        $this->withToken($currentToken)
            ->deleteJson('/api/mobile/logout')
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'current']);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'other']);
        $this->app['auth']->forgetGuards();
        $this->withToken($currentToken)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_mobile_logout_requires_authentication(): void
    {
        $this->deleteJson('/api/mobile/logout')->assertUnauthorized();
    }
}
