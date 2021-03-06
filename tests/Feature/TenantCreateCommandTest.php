<?php

namespace Tests\Feature;

use App\Notifications\TenantCreated;
use App\Tenant;
use App\User;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TenantCreateCommandTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        Notification::fake();
    }

    /** @test */
    public function tenant_name_is_required()
    {
        try {
            $this->artisan('tenant:create', ['email' => 'test@example.com']);
        } catch (\Exception $e) {
            $this->assertEquals('Not enough arguments (missing: "name").', $e->getMessage());
        }
    }

    /** @test */
    public function tenant_email_is_required()
    {
        try {
            $this->artisan('tenant:create', ['name' => 'example']);
        } catch (\Exception $e) {
            $this->assertEquals('Not enough arguments (missing: "email").', $e->getMessage());
        }
    }

    /** @test */
    public function can_create_new_tenant()
    {
        $this->artisan('tenant:create', ['name' => 'example', 'email' => 'test@example.com']);
        $this->assertSystemDatabaseHas('customers', ['name' => 'example', 'email' => 'test@example.com']);
    }

    /** @test */
    public function tenant_has_admin()
    {
        $this->artisan('tenant:create', ['name' => 'example', 'email' => 'test@example.com']);
        $this->assertDatabaseHas('users', ['email' =>  'test@example.com']);
    }

    /** @test */
    public function admin_has_proper_roles()
    {
        $this->artisan('tenant:create', ['name' => 'example', 'email' => 'test@example.com']);
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasPermissionTo('edit user'));
        $this->assertTrue($user->hasPermissionTo('create user'));
        $this->assertTrue($user->hasPermissionTo('delete user'));
    }

    /** @test */
    public function admin_is_invited()
    {
        $this->artisan('tenant:create', ['name' => 'example', 'email' => 'test@example.com']);
        Notification::assertSentTo(User::where('email', 'test@example.com')->get(), TenantCreated::class);
    }

    protected function tearDown()
    {
        if ($tenant = Tenant::retrieveBy('example')) {
            $tenant->delete();
        }
        parent::tearDown();
    }
}
