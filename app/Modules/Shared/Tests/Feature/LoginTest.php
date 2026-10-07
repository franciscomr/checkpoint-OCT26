<?php

use App\Models\User;
use App\Modules\Auth\Enums\UserStatus;
use App\Modules\Shared\Models\Tenant;
use App\Modules\Shared\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(
    Tests\TestCase::class,
    RefreshDatabase::class
);

function createAndSetTenant(): void
{
    $tenant = Tenant::factory()->create([
        'domain' => 'acme.checkpoint.test',
    ]);
    app(TenantManager::class)->setTenant($tenant);
}

describe('LoginTest', function () {
    it('Let log in with valid credentials', function () {
        createAndSetTenant();
        $user = User::factory()->create([
            'email' => 'ada@acme.test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('http://acme.checkpoint.test/login', [
            'email' => 'ada@acme.test',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    });

    it('rejects invalid credentials', function () {
        createAndSetTenant();
        User::factory()->create([
            'email' => 'ada@acme.test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('http://acme.checkpoint.test/login', [
            'email' => 'ada@acme.test',
            'password' => 'incorrecta',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    });

    it('rejects login attempts from users of other tenants even if the password is correct', function () {
        $globex = Tenant::factory()->create(['domain' => 'globex.checkpoint.test']);
        app(TenantManager::class)->setTenant($globex);

        User::factory()->create([
            'email' => 'ada@acme.test', // mismo email, tenant distinto
            'password' => bcrypt('password'),
        ]);

        createAndSetTenant();
        $response = $this->post('http://acme.checkpoint.test/login', [
            'email' => 'ada@acme.test',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    });

    it('rejects login if user is not active', function () {
        createAndSetTenant();
        $user = User::factory()->create([
            'email' => 'ada@acme.test',
            'password' => bcrypt('password'),
            'status' => UserStatus::INACTIVE,
        ]);

        app(TenantManager::class)->forgetTenant();
        $response = $this->post('http://acme.checkpoint.test/login', [
            'email' => 'ada@acme.test',
            'password' => 'password',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    });
});