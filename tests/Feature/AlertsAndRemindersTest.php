<?php

namespace Tests\Feature;

use App\Mail\LowStockAlert;
use App\Mail\WeeklyReminder;
use App\Models\InventoryItem;
use App\Models\Reminder;
use App\Models\User;
use Database\Seeders\InventorySeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AlertsAndRemindersTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::factory()->customer()->create(['first_name' => 'Rene', 'last_name' => 'Reminder']);
    }

    // --- weekly reminders ---

    public function test_reminders_send_to_active_due_rows(): void
    {
        Mail::fake();

        $customer = $this->customer();
        Reminder::create([
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'frequency' => 'weekly',
            'is_active' => true,
        ]);

        $this->artisan('reminders:send')->assertSuccessful();

        Mail::assertSent(WeeklyReminder::class, 1);
        Mail::assertSent(fn (WeeklyReminder $mail) => $mail->hasTo($customer->email));
    }

    public function test_reminders_skip_inactive_rows(): void
    {
        Mail::fake();

        $customer = $this->customer();
        Reminder::create([
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'frequency' => 'weekly',
            'is_active' => false,
        ]);

        $this->artisan('reminders:send');

        Mail::assertNothingSent();
    }

    public function test_reminders_skip_rows_sent_less_than_a_week_ago(): void
    {
        Mail::fake();

        $customer = $this->customer();
        Reminder::create([
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'frequency' => 'weekly',
            'is_active' => true,
            'last_sent_at' => now()->subDays(2),
        ]);

        $this->artisan('reminders:send');

        Mail::assertNothingSent();
    }

    public function test_reminders_send_again_after_a_week(): void
    {
        Mail::fake();

        $customer = $this->customer();
        Reminder::create([
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'frequency' => 'weekly',
            'is_active' => true,
            'last_sent_at' => now()->subDays(8),
        ]);

        $this->artisan('reminders:send');

        Mail::assertSent(WeeklyReminder::class, 1);
    }

    public function test_reminder_stamps_last_sent_and_does_not_resend(): void
    {
        Mail::fake();

        $customer = $this->customer();
        $reminder = Reminder::create([
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'frequency' => 'weekly',
            'is_active' => true,
        ]);

        $this->artisan('reminders:send');
        $this->assertNotNull($reminder->refresh()->last_sent_at);

        Mail::fake();
        $this->artisan('reminders:send');

        Mail::assertNothingSent();
    }

    // rule 13: two sentences plus a link to the order page
    public function test_reminder_body_is_two_sentences_with_a_link(): void
    {
        $customer = $this->customer();
        $reminder = Reminder::create([
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'frequency' => 'weekly',
            'is_active' => true,
        ]);

        $rendered = (new WeeklyReminder($reminder->fresh()))->render();

        $sentences = substr_count(trim(strip_tags($rendered)), '.');

        $this->assertLessThanOrEqual(3, $sentences);
        $this->assertStringContainsString('/customer/orders/create', $rendered);
        $this->assertStringContainsString($customer->first_name, $rendered);
    }

    // --- low stock ---

    public function test_low_stock_alert_goes_to_admins_only(): void
    {
        Mail::fake();

        InventoryItem::create(['name' => 'thing', 'quantity' => 1, 'threshold' => 5]);

        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staff()->create();

        $this->artisan('inventory:low-stock')->assertSuccessful();

        Mail::assertSent(LowStockAlert::class, 1);
        Mail::assertSent(fn (LowStockAlert $mail) => $mail->hasTo($admin->email));
        Mail::assertNotSent(fn (LowStockAlert $mail) => $mail->hasTo($staff->email));
    }

    public function test_low_stock_does_not_email_when_everything_is_stocked(): void
    {
        Mail::fake();

        InventoryItem::create(['name' => 'plenty', 'quantity' => 50, 'threshold' => 5]);
        User::factory()->admin()->create();

        $this->artisan('inventory:low-stock');

        Mail::assertNothingSent();
    }

    public function test_low_stock_alert_lists_only_low_items(): void
    {
        InventoryItem::create(['name' => 'low_one', 'quantity' => 1, 'threshold' => 5]);
        InventoryItem::create(['name' => 'fine', 'quantity' => 50, 'threshold' => 5]);

        $mailable = new LowStockAlert(
            InventoryItem::all()->filter(fn (InventoryItem $i) => $i->isLowStock())->values()
        );

        $rendered = $mailable->render();

        $this->assertStringContainsString('low one', $rendered);
        $this->assertStringNotContainsString('fine', $rendered);
    }

    public function test_commands_are_scheduled(): void
    {
        $schedule = app(Schedule::class);

        $commands = collect($schedule->events())
            ->map(fn ($event) => $event->command)
            ->implode(' ');

        $this->assertStringContainsString('reminders:send', $commands);
        $this->assertStringContainsString('inventory:low-stock', $commands);
    }

    // --- container visual ---

    public function test_staff_container_page_shows_the_fill_ratio(): void
    {
        $this->seed(InventorySeeder::class);

        $response = $this->actingAs(User::factory()->staff()->create())
            ->get('/staff/containers');

        // 40 full of 65 at station is 61.5 percent
        $response->assertOk()->assertSee('61.5% full at station');
    }

    public function test_admin_dashboard_shows_low_stock_and_sales(): void
    {
        $this->seed(InventorySeeder::class);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('dispenser maintenance')
            ->assertSee('Needs restock')
            ->assertSee('Container status');
    }
}
