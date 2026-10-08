<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleRedirectTest extends TestCase
{
    use RefreshDatabase;

    public static function roleRedirectProvider(): array
    {
        return [
            'admin' => [User::ROLE_ADMIN, '/admin/dashboard'],
            'staff' => [User::ROLE_STAFF, '/staff/dashboard'],
            'driver' => [User::ROLE_DRIVER, '/driver/dashboard'],
            'customer' => [User::ROLE_CUSTOMER, '/customer/dashboard'],
        ];
    }

    /** @dataProvider roleRedirectProvider */
    public function test_dashboard_redirects_each_role_to_its_own_page(string $role, string $expected): void
    {
        $this->actingAs(User::factory()->role($role)->create())
            ->get('/dashboard')
            ->assertRedirect($expected);
    }

    /** @dataProvider roleRedirectProvider */
    public function test_each_role_can_open_its_own_dashboard(string $role): void
    {
        $this->actingAs(User::factory()->role($role)->create())
            ->get('/'.$role.'/dashboard')
            ->assertOk();
    }

    public function test_role_middleware_blocks_other_roles(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_customers_cannot_reach_staff_pages(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get('/staff/dashboard')
            ->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/staff/dashboard')->assertRedirect('/login');
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_page_has_no_role_selector(): void
    {
        $response = $this->get('/login');

        $response->assertOk()->assertDontSee('name="role"', false);
        $response->assertDontSee('Delivery rider', false);
    }
}
