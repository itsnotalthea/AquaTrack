<?php

namespace Tests\Feature;

use App\Http\Controllers\Staff\ReportController;
use App\Mail\LowStockAlert;
use App\Mail\WeeklyReminder;
use App\Models\ContainerInventory;
use App\Models\Delivery;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Reminder;
use App\Models\User;
use Database\Seeders\InventorySeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * One test per line of the QA checklist in instructions.md.
 */
class QaChecklistTest extends TestCase
{
    use RefreshDatabase;

    private function customer(array $extra = []): User
    {
        return User::factory()->customer()->create($extra + [
            'mobile' => '09171234567',
            'house_no' => '12',
            'street' => 'Katipunan Ave',
            'city' => 'Marikina City',
        ]);
    }

    private function refill(): Product
    {
        return Product::create(['name' => 'Refill '.uniqid(), 'price' => 25, 'unit' => 'gallon', 'is_active' => true]);
    }

    private function order(array $attrs = [], int $qty = 3): Order
    {
        $product = $this->refill();
        $order = Order::factory()->create($attrs + [
            'order_type' => 'pickup',
            'status' => Order::STATUS_PENDING,
            'total_amount' => 25 * $qty,
        ]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => $qty, 'price' => 25, 'subtotal' => 25 * $qty]);

        return $order->fresh();
    }

    // 1
    public function test_customer_register_requires_ph_mobile_and_full_address(): void
    {
        $this->get('/register')->assertOk();

        $this->post('/register', [
            'first_name' => 'A', 'last_name' => 'B', 'email' => 'qa1@example.com',
            'mobile' => '12345', 'password' => 'password', 'password_confirmation' => 'password',
            'house_no' => '1', 'street' => 'S', 'city' => 'Marikina City',
        ])->assertSessionHasErrors('mobile');

        $this->post('/register', [
            'first_name' => 'A', 'last_name' => 'B', 'email' => 'qa2@example.com',
            'mobile' => '09171234567', 'password' => 'password', 'password_confirmation' => 'password',
            'city' => 'Marikina City',
        ])->assertSessionHasErrors(['house_no', 'street']);
    }

    // 2
    public function test_duplicate_email_rejected_and_short_password_rejected(): void
    {
        User::factory()->customer()->create(['email' => 'dup@example.com']);

        $this->post('/register', [
            'first_name' => 'A', 'last_name' => 'B', 'email' => 'dup@example.com',
            'mobile' => '09171234567', 'password' => 'password', 'password_confirmation' => 'password',
            'house_no' => '1', 'street' => 'S', 'city' => 'Marikina City',
        ])->assertSessionHasErrors('email');

        $this->post('/register', [
            'first_name' => 'A', 'last_name' => 'B', 'email' => 'short@example.com',
            'mobile' => '09171234567', 'password' => 'abcd', 'password_confirmation' => 'abcd',
            'house_no' => '1', 'street' => 'S', 'city' => 'Marikina City',
        ])->assertSessionHasErrors('password');
    }

    // 3
    public function test_login_redirects_by_role_with_no_role_selector(): void
    {
        $response = $this->get('/login');

        $response->assertOk()->assertDontSee('name="role"', false);

        $this->post('/login', ['email' => User::factory()->staff()->create()->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');
    }

    // 4
    public function test_internal_accounts_are_only_creatable_by_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/users', [
            'first_name' => 'New', 'last_name' => 'Staffer', 'email' => 'new@staff.com',
            'password' => 'password', 'password_confirmation' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'new@staff.com', 'role' => User::ROLE_STAFF]);

        // a customer domain is refused for internal accounts
        $this->actingAs($admin)->post('/admin/users', [
            'first_name' => 'No', 'last_name' => 'Pe', 'email' => 'nope@gmail.com',
            'password' => 'password', 'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');

        // staff cannot reach admin user creation at all
        $this->actingAs(User::factory()->staff()->create())
            ->post('/admin/users', [
                'first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@staff.com',
                'password' => 'password', 'password_confirmation' => 'password',
            ])->assertForbidden();
    }

    // 5
    public function test_ordering_requires_login(): void
    {
        $this->get('/customer/orders')->assertRedirect('/login');
        $this->get('/customer/orders/create')->assertRedirect('/login');
    }

    // 6 and 7
    public function test_refill_quantity_capped_and_priced_per_gallon(): void
    {
        $user = $this->customer();

        $this->actingAs($user)->post('/customer/orders', [
            'product_id' => $this->refill()->id, 'quantity' => 6, 'order_type' => 'pickup',
            'preferred_date' => now()->addDay()->toDateString(), 'payment_method' => 'cash',
        ])->assertSessionHasErrors('quantity');

        $this->actingAs($user)->post('/customer/orders', [
            'product_id' => $this->refill()->id, 'quantity' => 4, 'order_type' => 'pickup',
            'preferred_date' => now()->addDay()->toDateString(), 'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();

        $this->assertSame('100.00', Order::first()->total_amount);
    }

    public function test_delivery_fee_applies_only_to_delivery_orders(): void
    {
        $user = $this->customer();

        $this->actingAs($user)->post('/customer/orders', [
            'product_id' => $this->refill()->id, 'quantity' => 2, 'order_type' => 'pickup',
            'preferred_date' => now()->addDay()->toDateString(), 'payment_method' => 'cash',
        ]);
        $this->assertSame('50.00', Order::first()->total_amount);

        $this->actingAs($user)->post('/customer/orders', [
            'product_id' => $this->refill()->id, 'quantity' => 2, 'order_type' => 'delivery',
            'delivery_address' => '12 Katipunan Ave',
            'preferred_date' => now()->addDay()->toDateString(), 'payment_method' => 'cash',
        ]);
        // 2 x 25 = 50, plus 30 delivery
        $this->assertSame('80.00', Order::orderByDesc('id')->first()->total_amount);
    }

    // 8
    public function test_container_swap_requires_a_quantity_when_selected(): void
    {
        $this->actingAs($this->customer())->post('/customer/orders', [
            'product_id' => $this->refill()->id, 'quantity' => 1, 'order_type' => 'pickup',
            'preferred_date' => now()->addDay()->toDateString(), 'payment_method' => 'cash',
            'container_swap' => '1',
        ])->assertSessionHasErrors('container_swap_qty');
    }

    // 9
    public function test_status_transitions_follow_defined_flows(): void
    {
        $pickup = $this->order(['order_type' => 'pickup']);
        $this->assertSame([Order::STATUS_CONFIRMED, Order::STATUS_CANCELLED], $pickup->allowedTransitions());

        $delivery = $this->order(['order_type' => 'delivery']);
        $delivery->status = Order::STATUS_OUT_FOR_DELIVERY;
        $this->assertSame([Order::STATUS_DELIVERED], $delivery->allowedTransitions());

        $delivery->status = Order::STATUS_COMPLETED;
        $this->assertSame([], $delivery->allowedTransitions());
    }

    // 10
    public function test_cancel_only_from_pending_or_confirmed(): void
    {
        $staff = User::factory()->staff()->create();

        $pending = $this->order();
        $this->actingAs($staff)->put("/staff/orders/{$pending->id}/status", ['status' => Order::STATUS_CANCELLED]);
        $this->assertSame(Order::STATUS_CANCELLED, $pending->refresh()->status);

        $preparing = $this->order(['status' => Order::STATUS_PREPARING]);
        $this->actingAs($staff)->put("/staff/orders/{$preparing->id}/status", ['status' => Order::STATUS_CANCELLED])
            ->assertSessionHasErrors('status');
        $this->assertSame(Order::STATUS_PREPARING, $preparing->refresh()->status);
    }

    // 11
    public function test_driver_sees_only_assigned_deliveries_and_cannot_record_payments(): void
    {
        $driver = User::factory()->driver()->create();
        $mine = Delivery::create([
            'order_id' => $this->order(['order_type' => 'delivery'])->id, 'driver_id' => $driver->id,
        ]);
        $theirs = Delivery::create([
            'order_id' => $this->order(['order_type' => 'delivery'])->id,
            'driver_id' => User::factory()->driver()->create()->id,
        ]);

        $list = $this->actingAs($driver)->get('/driver/deliveries');
        $this->assertSame(1, $list->viewData('deliveries')->total());

        $this->actingAs($driver)->get("/driver/deliveries/{$theirs->id}")->assertForbidden();

        $paid = $this->order(['status' => Order::STATUS_PICKED_UP, 'order_type' => 'pickup']);
        $this->actingAs($driver)->put("/staff/orders/{$paid->id}/payment", ['method' => 'cash'])->assertForbidden();
        $this->assertSame(0, Payment::count());
    }

    // 12
    public function test_staff_can_record_payment_and_partial_is_impossible(): void
    {
        $order = $this->order(['status' => Order::STATUS_PICKED_UP]);
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->put("/staff/orders/{$order->id}/payment", ['method' => 'cash'])
            ->assertSessionHasNoErrors();

        $payment = Payment::first();

        // the amount is the full order total, there is no amount field at all
        $this->assertSame('75.00', $payment->amount);
        $this->assertSame($staff->id, $payment->recorded_by);

        // a supplied partial amount is ignored
        $other = $this->order(['status' => Order::STATUS_PICKED_UP]);
        $this->actingAs($staff)->put("/staff/orders/{$other->id}/payment", ['method' => 'cash', 'amount' => 10]);
        $this->assertSame('75.00', Payment::where('order_id', $other->id)->value('amount'));
    }

    // 13
    public function test_paid_pickup_order_completes_and_moves_inventory(): void
    {
        $this->seed(InventorySeeder::class);

        $order = $this->order(['status' => Order::STATUS_PICKED_UP]);
        $staff = User::factory()->staff()->create();

        $before = ContainerInventory::orderBy('id')->pluck('quantity', 'status')->all();

        $this->actingAs($staff)->put("/staff/orders/{$order->id}/payment", ['method' => 'cash']);

        $order->refresh();

        $this->assertSame(Order::STATUS_COMPLETED, $order->status);
        $this->assertSame('paid', $order->payment_status);

        $after = ContainerInventory::orderBy('id')->pluck('quantity', 'status')->all();
        $this->assertSame($before['at_station_full'] - 3, $after['at_station_full']);
        $this->assertSame($before['with_customer'] + 3, $after['with_customer']);
    }

    // 14
    public function test_inventory_updates_on_completion_for_stock_products(): void
    {
        $this->seed(InventorySeeder::class);

        $container = Product::create(['name' => 'NC '.uniqid(), 'price' => 250, 'unit' => 'piece', 'is_active' => true]);
        $order = Order::factory()->create(['order_type' => 'pickup', 'status' => Order::STATUS_PICKED_UP]);
        $order->items()->create(['product_id' => $container->id, 'quantity' => 2, 'price' => 250, 'subtotal' => 500]);

        $before = InventoryItem::where('name', 'new_container')->value('quantity');

        $this->actingAs(User::factory()->staff()->create())
            ->put("/staff/orders/{$order->id}/payment", ['method' => 'cash']);

        $this->assertSame(Order::STATUS_COMPLETED, $order->refresh()->status);
        $this->assertSame($before - 2, InventoryItem::where('name', 'new_container')->value('quantity'));
    }

    // 15
    public function test_container_fill_ratio_is_correct(): void
    {
        $full = ContainerInventory::create(['status' => ContainerInventory::AT_STATION_FULL, 'quantity' => 40]);
        $empty = ContainerInventory::create(['status' => ContainerInventory::AT_STATION_EMPTY, 'quantity' => 60]);

        $model = new ContainerInventory;
        $model->setAttribute(ContainerInventory::AT_STATION_FULL, $full->quantity);
        $model->setAttribute(ContainerInventory::AT_STATION_EMPTY, $empty->quantity);

        $this->assertEqualsWithDelta(0.4, $model->fill_ratio, 0.0001);

        $this->actingAs(User::factory()->staff()->create())
            ->getJson('/api/containers')
            ->assertJsonPath('fill_ratio', 0.4);
    }

    // 16
    public function test_low_stock_alert_on_dashboard_and_email(): void
    {
        Mail::fake();
        InventoryItem::create(['name' => 'low', 'quantity' => 1, 'threshold' => 5]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertSee('low');

        $this->artisan('inventory:low-stock');
        Mail::assertSent(LowStockAlert::class, 1);
    }

    // 17
    public function test_admin_can_edit_thresholds(): void
    {
        $item = InventoryItem::create(['name' => 'thing', 'quantity' => 9, 'threshold' => 5]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put('/admin/inventory', ['thresholds' => [$item->id => 20]])
            ->assertSessionHasNoErrors();

        $this->assertSame(20, $item->refresh()->threshold);
    }

    // 18
    public function test_weekly_reminder_is_scheduled_short_and_linked(): void
    {
        Mail::fake();

        $schedule = app(Schedule::class);
        $event = collect($schedule->events())->first(fn ($e) => str_contains($e->command, 'reminders:send'));

        $this->assertNotNull($event);
        $this->assertSame('0 8 * * 1', $event->expression);

        $customer = $this->customer();
        $reminder = Reminder::create([
            'customer_id' => $customer->id, 'email' => $customer->email,
            'frequency' => 'weekly', 'is_active' => true,
        ]);

        $rendered = (new WeeklyReminder($reminder->fresh()))->render();

        $this->assertStringContainsString('/customer/orders/create', $rendered);
        $this->assertLessThanOrEqual(3, substr_count(trim(strip_tags($rendered)), '.'));

        $this->artisan('reminders:send');
        Mail::assertSent(WeeklyReminder::class, 1);
    }

    // 19
    public function test_all_eight_reports_render_with_filters(): void
    {
        $this->seed(InventorySeeder::class);
        $staff = User::factory()->staff()->create();

        foreach (array_keys(ReportController::TYPES) as $type) {
            $this->actingAs($staff)->get("/staff/reports/{$type}")->assertOk();
        }

        // every documented filter param is accepted without error
        $this->actingAs($staff)->get('/staff/reports/sales?from=2020-01-01&to=2030-01-01&status=completed&payment_status=paid&payment_method=cash&order_type=delivery&barangay=Malanday')->assertOk();
        $this->actingAs($staff)->get('/staff/reports/deliveries?status=delivered')->assertOk();
        $this->actingAs($staff)->get('/staff/reports/inventory?low_stock=1')->assertOk();
        $this->actingAs($staff)->get('/staff/reports/inventory?container_status=at_station_full')->assertOk();
    }

    // 20
    public function test_no_csv_or_pdf_export_buttons(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staff()->create();

        $adminPages = ['/admin/dashboard', '/admin/users', '/admin/products', '/admin/inventory', '/admin/settings'];
        $staffPages = [
            '/staff/dashboard', '/staff/orders', '/staff/inventory', '/staff/containers',
            '/staff/reports/sales', '/staff/reports/orders', '/staff/reports/payments',
        ];

        foreach ($adminPages as $page) {
            $this->assertNoExport($this->actingAs($admin)->get($page)->assertOk()->getContent(), $page);
        }

        foreach ($staffPages as $page) {
            $this->assertNoExport($this->actingAs($staff)->get($page)->assertOk()->getContent(), $page);
        }
    }

    private function assertNoExport(string $content, string $page): void
    {
        $this->assertStringNotContainsStringIgnoringCase('csv', $content, $page);
        $this->assertStringNotContainsStringIgnoringCase('.pdf', $content, $page);
        $this->assertStringNotContainsStringIgnoringCase('download', $content, $page);
    }

    // 21 and 22
    public function test_public_pages_keep_the_original_markup(): void
    {
        $home = $this->get('/')->assertOk()->getContent();

        // tiktok iframe and calendar survive untouched
        $this->assertStringContainsString('id="tiktok-player"', $home);
        $this->assertStringContainsString('7313952032467979566', $home);
        $this->assertStringContainsString('tiktok-landscape-wrapper', $home);
        $this->assertStringContainsString('id="calendar"', $home);
        $this->assertStringContainsString('<nav class="navbar', $home);

        $this->get('/about')->assertOk()->assertSee('About AquaTrack');
        $this->get('/services')->assertOk()->assertSee('Place an order');
    }

    // 23
    public function test_role_middleware_blocks_unauthorized_access(): void
    {
        $staff = User::factory()->staff()->create();
        $driver = User::factory()->driver()->create();
        $customer = $this->customer();

        $this->actingAs($customer)->get('/admin/users')->assertForbidden();
        $this->actingAs($customer)->get('/staff/orders')->assertForbidden();
        $this->actingAs($driver)->get('/staff/orders')->assertForbidden();
        $this->actingAs($staff)->get('/admin/users')->assertForbidden();
        $this->actingAs($driver)->get('/customer/orders')->assertForbidden();
        $this->actingAs($customer)->get('/driver/deliveries')->assertForbidden();
    }

    // 24
    // ValidateCsrfToken returns early while running tests, so assert the guard
    // is actually wired into every mutating route instead
    public function test_every_mutating_route_is_csrf_protected(): void
    {
        $groups = app(Kernel::class)->getMiddlewareGroups();
        $mutating = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route) => array_intersect(['POST', 'PUT', 'PATCH', 'DELETE'], $route->methods()))
            // storage/{path} is laravel's own local file route, not application code
            ->reject(fn ($route) => str_starts_with($route->uri(), 'storage/'));

        $this->assertGreaterThan(0, $mutating->count());

        foreach ($mutating as $route) {
            $resolved = [];

            foreach ($route->gatherMiddleware() as $entry) {
                $key = is_string($entry) ? $entry : get_class($entry);
                $resolved = array_merge($resolved, $groups[$key] ?? [$key]);
            }

            $this->assertContains(
                ValidateCsrfToken::class,
                $resolved,
                $route->uri().' is missing csrf protection'
            );
        }
    }

    public function test_mutating_forms_emit_a_csrf_token(): void
    {
        $staff = User::factory()->staff()->create();
        $order = $this->order(['status' => Order::STATUS_PENDING]);

        $pages = [
            '/register' => $this->get('/register')->getContent(),
            '/login' => $this->get('/login')->getContent(),
            '/admin/users/create' => $this->actingAs(User::factory()->admin()->create())->get('/admin/users/create')->getContent(),
            '/customer/orders/create' => $this->actingAs($this->customer())->get('/customer/orders/create')->getContent(),
            "/staff/orders/{$order->id}" => $this->actingAs($staff)->get("/staff/orders/{$order->id}")->getContent(),
        ];

        foreach ($pages as $name => $html) {
            $this->assertStringContainsString('name="_token"', $html, $name.' is missing a csrf field');
        }
    }

    // 25
    public function test_passwords_are_hashed(): void
    {
        $user = User::factory()->customer()->create(['password' => 'secret123']);

        $this->assertNotSame('secret123', $user->password);
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertStringStartsWith('$2y$', $user->password);
    }
}
