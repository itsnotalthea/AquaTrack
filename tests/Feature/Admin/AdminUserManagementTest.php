<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_can_list_all_users_including_customers(): void
    {
        $customer = User::factory()->customer()->create(['first_name' => 'Customer', 'last_name' => 'Person']);

        $this->actingAs($this->admin())
            ->get('/admin/users')
            ->assertOk()
            ->assertSee($customer->email);
    }

    public function test_admin_can_create_internal_users_per_email_domain(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/users', [
            'first_name' => 'New',
            'last_name' => 'Staffer',
            'email' => 'new.staffer@staff.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/admin/users');

        $user = User::where('email', 'new.staffer@staff.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(User::ROLE_STAFF, $user->role);
        $this->assertSame('New Staffer', $user->name);
    }

    public function test_role_follows_the_email_domain_and_ignores_any_supplied_role(): void
    {
        $this->actingAs($this->admin())->post('/admin/users', [
            'first_name' => 'Rider',
            'last_name' => 'Person',
            'email' => 'rider@delivery.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertSame(User::ROLE_DRIVER, User::where('email', 'rider@delivery.com')->first()->role);
    }

    public function test_internal_account_cannot_use_a_customer_domain(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/users', [
            'first_name' => 'Nope',
            'last_name' => 'Nope',
            'email' => 'someone@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'someone@gmail.com']);
    }

    public function test_new_user_password_requires_five_characters(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/users', [
            'first_name' => 'Short',
            'last_name' => 'Password',
            'email' => 'short@staff.com',
            'password' => 'abcd',
            'password_confirmation' => 'abcd',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'short@staff.com']);
    }

    public function test_customer_accounts_are_not_editable_by_admin(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($this->admin())
            ->get("/admin/users/{$customer->id}/edit")
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->put("/admin/users/{$customer->id}", [
                'first_name' => 'Hacked',
                'last_name' => 'Name',
                'email' => $customer->email,
            ])
            ->assertForbidden();
    }

    public function test_user_with_orders_cannot_be_deleted(): void
    {
        $customer = User::factory()->customer()->create();
        Order::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($this->admin())
            ->delete("/admin/users/{$customer->id}")
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('users', ['id' => $customer->id]);
    }

    public function test_user_without_orders_can_be_deleted(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($this->admin())
            ->delete("/admin/users/{$staff->id}")
            ->assertRedirect('/admin/users');

        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete("/admin/users/{$admin->id}")
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_cannot_demote_themselves(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put("/admin/users/{$admin->id}", [
            'first_name' => $admin->first_name,
            'last_name' => $admin->last_name,
            'email' => 'myself@staff.com',
        ])->assertSessionHasErrors('email');

        $this->assertSame(User::ROLE_ADMIN, $admin->refresh()->role);
    }

    public function test_password_is_optional_when_editing(): void
    {
        $staff = User::factory()->staff()->create(['first_name' => 'Before']);

        $this->actingAs($this->admin())->put("/admin/users/{$staff->id}", [
            'first_name' => 'After',
            'last_name' => $staff->last_name,
            'email' => $staff->email,
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect('/admin/users');

        $this->assertSame('After', $staff->refresh()->first_name);
    }

    public function test_non_admin_roles_cannot_reach_admin_pages(): void
    {
        foreach ([User::ROLE_STAFF, User::ROLE_DRIVER, User::ROLE_CUSTOMER] as $role) {
            $this->actingAs(User::factory()->role($role)->create())
                ->get('/admin/users')
                ->assertForbidden();
        }
    }
}
