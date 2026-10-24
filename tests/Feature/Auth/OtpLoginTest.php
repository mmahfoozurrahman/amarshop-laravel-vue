<?php

namespace Tests\Feature\Auth;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\ApiTestCase;

class OtpLoginTest extends ApiTestCase
{
    public function test_otp_can_be_requested_for_a_valid_bangladeshi_number(): void
    {
        $response = $this->postJson('/api/auth/request-otp', ['phone' => '01712345678']);

        $response->assertOk()
            ->assertJsonStructure(['message', 'expires_in', 'demo_otp']);

        $this->assertDatabaseHas('otp_verifications', ['phone' => '01712345678']);
        $this->assertSame(6, strlen((string) $response->json('demo_otp')));
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $this->assertValidationError(
            $this->postJson('/api/auth/request-otp', ['phone' => '12345']),
            'phone',
        );
    }

    public function test_otp_requests_are_throttled(): void
    {
        $this->postJson('/api/auth/request-otp', ['phone' => '01712345678'])->assertOk();

        $this->assertValidationError(
            $this->postJson('/api/auth/request-otp', ['phone' => '01712345678']),
            'phone',
        );
    }

    public function test_wrong_otp_increments_attempts(): void
    {
        $otp = $this->postJson('/api/auth/request-otp', ['phone' => '01712345678'])->json('demo_otp');
        $wrong = $otp === '111111' ? '222222' : '111111';

        $this->assertValidationError(
            $this->postJson('/api/auth/verify-otp', ['phone' => '01712345678', 'otp' => $wrong]),
            'otp',
        );

        $this->assertSame(1, OtpVerification::where('phone', '01712345678')->value('attempts'));
    }

    public function test_expired_otp_is_rejected(): void
    {
        OtpVerification::create([
            'phone' => '01712345678',
            'otp_hash' => Hash::make('123456'),
            'purpose' => 'login',
            'attempts' => 0,
            'expires_at' => now()->subMinute(),
        ]);

        $this->assertValidationError(
            $this->postJson('/api/auth/verify-otp', ['phone' => '01712345678', 'otp' => '123456']),
            'otp',
        );
    }

    public function test_valid_otp_creates_customer_and_returns_token(): void
    {
        $otp = $this->postJson('/api/auth/request-otp', ['phone' => '01712345678'])->json('demo_otp');

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => '01712345678',
            'otp' => $otp,
            'name' => 'Rahim Uddin',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'token_type', 'user', 'cart']);

        $this->assertDatabaseHas('users', [
            'phone' => '01712345678',
            'name' => 'Rahim Uddin',
            'role' => 'customer',
        ]);

        $token = $response->json('token');

        $this->withToken($token)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.phone', '01712345678');
    }

    public function test_second_login_reuses_the_same_user(): void
    {
        $this->travel(2)->minutes();

        $firstOtp = $this->postJson('/api/auth/request-otp', ['phone' => '01712345678'])->json('demo_otp');
        $this->postJson('/api/auth/verify-otp', ['phone' => '01712345678', 'otp' => $firstOtp])->assertOk();

        $this->travel(2)->minutes();

        $secondOtp = $this->postJson('/api/auth/request-otp', ['phone' => '01712345678'])->json('demo_otp');
        $this->postJson('/api/auth/verify-otp', ['phone' => '01712345678', 'otp' => $secondOtp])->assertOk();

        $this->assertSame(1, User::where('phone', '01712345678')->count());
    }

    public function test_logout_revokes_the_token(): void
    {
        $otp = $this->postJson('/api/auth/request-otp', ['phone' => '01712345678'])->json('demo_otp');
        $token = $this->postJson('/api/auth/verify-otp', ['phone' => '01712345678', 'otp' => $otp])->json('token');

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // The CLI/test harness caches resolved guards between requests.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_admin_can_login_with_password_but_customers_cannot(): void
    {
        $admin = $this->admin(['email' => 'admin@amarshop.test', 'password' => 'password']);

        $response = $this->postJson('/api/admin/auth/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user']);

        $this->postJson('/api/admin/auth/login', [
            'email' => 'admin@amarshop.test',
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }

    public function test_customers_cannot_access_admin_endpoints(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/admin/dashboard')
            ->assertStatus(403);
    }
}
