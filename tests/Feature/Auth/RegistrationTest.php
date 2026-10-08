<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertStatus(200);
    }

    public function test_new_customers_can_register(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.com',
            'mobile' => '09171234567',
            'password' => 'password',
            'password_confirmation' => 'password',
            'house_no' => '12',
            'street' => 'Katipunan Ave',
            'subdivision' => null,
            'city' => 'Marikina City',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('customer.dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'juan@example.com',
            'role' => User::ROLE_CUSTOMER,
        ]);
    }

    public function test_registration_cannot_grant_internal_roles(): void
    {
        $this->post('/register', [
            'first_name' => 'Mallory',
            'last_name' => 'Admin',
            'email' => 'mallory@gmail.com',
            'mobile' => '09171234567',
            'password' => 'password',
            'password_confirmation' => 'password',
            'house_no' => '1',
            'street' => 'Street',
            'city' => 'Marikina City',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertSame(User::ROLE_CUSTOMER, User::where('email', 'mallory@gmail.com')->first()->role);
    }

    public function test_registration_requires_a_valid_mobile_number(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Bad',
            'last_name' => 'Mobile',
            'email' => 'bad@example.com',
            'mobile' => '12345',
            'password' => 'password',
            'password_confirmation' => 'password',
            'house_no' => '1',
            'street' => 'Street',
            'city' => 'Marikina City',
        ]);

        $response->assertSessionHasErrors('mobile');
        $this->assertGuest();
    }

    public function test_registration_rejects_passwords_shorter_than_five_characters(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Short',
            'last_name' => 'Password',
            'email' => 'short@example.com',
            'mobile' => '09171234567',
            'password' => 'abcd',
            'password_confirmation' => 'abcd',
            'house_no' => '1',
            'street' => 'Street',
            'city' => 'Marikina City',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_registration_requires_a_full_address(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'No',
            'last_name' => 'Address',
            'email' => 'noaddress@example.com',
            'mobile' => '09171234567',
            'password' => 'password',
            'password_confirmation' => 'password',
            'city' => 'Marikina City',
        ]);

        $response->assertSessionHasErrors(['house_no', 'street']);
        $this->assertGuest();
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->post('/register', [
            'first_name' => 'Dupe',
            'last_name' => 'Email',
            'email' => 'taken@example.com',
            'mobile' => '09171234567',
            'password' => 'password',
            'password_confirmation' => 'password',
            'house_no' => '1',
            'street' => 'Street',
            'city' => 'Marikina City',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
