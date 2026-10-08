<?php

namespace Tests\Feature\Staff;

use App\Http\Controllers\Staff\ReportController;
use App\Models\Barangay;
use App\Models\ContainerInventory;
use App\Models\ContainerTransaction;
use App\Models\Delivery;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\InventorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->staff()->create();
    }

    private function customer(string $barangay = 'Malanday'): User
    {
        return User::factory()->customer()->create(['barangay' => $barangay]);
    }

    private function order(User $customer, float $total, string $status = Order::STATUS_COMPLETED, string $paymentStatus = 'paid', string $type = 'pickup'): Order
    {
        $product = Product::create(['name' => 'P '.uniqid(), 'price' => 25, 'unit' => 'gallon', 'is_active' => true]);

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => $total,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'order_type' => $type,
            'barangay' => $customer->barangay,
            'payment_method' => 'cash',
        ]);

        $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 25, 'subtotal' => $total]);

        return $order;
    }

    public static function reportTypes(): array
    {
        return array_map(fn ($t) => [$t], array_keys(
            ReportController::TYPES
        ));
    }

    /** @dataProvider reportTypes */
    public function test_every_report_renders(string $type): void
    {
        $this->actingAs($this->staff())
            ->get("/staff/reports/{$type}")
            ->assertOk();
    }

    public function test_unknown_report_type_returns_404(): void
    {
        $this->actingAs($this->staff())->get('/staff/reports/nonsense')->assertNotFound();
    }

    public function test_customers_cannot_read_reports(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get('/staff/reports/sales')
            ->assertForbidden();
    }

    public function test_sales_report_totals_gross_and_collected(): void
    {
        $customer = $this->customer();
        $this->order($customer, 100, Order::STATUS_COMPLETED, 'paid');
        $this->order($customer, 50, Order::STATUS_COMPLETED, 'unpaid');

        $response = $this->actingAs($this->staff())->get('/staff/reports/sales');

        $response->assertOk();
        $totals = $response->viewData('totals');
        $this->assertSame(150.0, $totals['gross']);
        $this->assertSame(100.0, $totals['collected']);
        $this->assertSame(2, $totals['orders']);
    }

    public function test_sales_report_excludes_cancelled_orders(): void
    {
        $customer = $this->customer();
        $this->order($customer, 100);
        $this->order($customer, 999, Order::STATUS_CANCELLED);

        $totals = $this->actingAs($this->staff())->get('/staff/reports/sales')->viewData('totals');

        $this->assertSame(100.0, $totals['gross']);
        $this->assertSame(1, $totals['orders']);
    }

    public function test_barangay_filter_narrows_the_sales_report(): void
    {
        $mal = $this->customer('Malanday');
        $par = $this->customer('Parang');
        $this->order($mal, 100);
        $this->order($par, 300);

        $totals = $this->actingAs($this->staff())
            ->get('/staff/reports/sales?barangay=Malanday')
            ->viewData('totals');

        $this->assertSame(100.0, $totals['gross']);
    }

    public function test_status_filter_works(): void
    {
        $customer = $this->customer();
        $this->order($customer, 100, Order::STATUS_COMPLETED);
        $this->order($customer, 200, Order::STATUS_PENDING);

        $totals = $this->actingAs($this->staff())
            ->get('/staff/reports/sales?status=pending')
            ->viewData('totals');

        $this->assertSame(200.0, $totals['gross']);
    }

    public function test_date_range_filter_works(): void
    {
        $customer = $this->customer();
        $old = $this->order($customer, 100);
        $old->forceFill(['created_at' => now()->subMonth()])->save();

        $this->order($customer, 200);

        $totals = $this->actingAs($this->staff())
            ->get('/staff/reports/sales?from='.now()->toDateString())
            ->viewData('totals');

        $this->assertSame(200.0, $totals['gross']);
    }

    public function test_payments_report_sums_recorded_payments(): void
    {
        $customer = $this->customer();
        $order = $this->order($customer, 75, Order::STATUS_PICKED_UP, 'unpaid');

        Payment::create(['order_id' => $order->id, 'amount' => 75, 'method' => 'gcash', 'recorded_by' => $this->staff()->id]);

        $totals = $this->actingAs($this->staff())->get('/staff/reports/payments')->viewData('totals');

        $this->assertSame(75.0, $totals['amount']);
        $this->assertSame(1, $totals['count']);
    }

    public function test_sales_by_product_groups_quantities(): void
    {
        $customer = $this->customer();
        $product = Product::create(['name' => 'Refill X', 'price' => 25, 'unit' => 'gallon', 'is_active' => true]);

        foreach ([1, 2] as $qty) {
            $order = Order::factory()->create(['customer_id' => $customer->id, 'status' => Order::STATUS_COMPLETED]);
            $order->items()->create(['product_id' => $product->id, 'quantity' => $qty, 'price' => 25, 'subtotal' => 25 * $qty]);
        }

        $rows = $this->actingAs($this->staff())->get('/staff/reports/sales_by_product')->viewData('rows');

        $row = collect($rows)->firstWhere('name', 'Refill X');

        $this->assertNotNull($row);
        $this->assertSame(3, $row['qty']);
        $this->assertSame(75.0, $row['amount']);
    }

    public function test_inventory_report_can_filter_low_stock_only(): void
    {
        $this->seed(InventorySeeder::class);

        $rows = $this->actingAs($this->staff())
            ->get('/staff/reports/inventory?low_stock=1')
            ->viewData('rows');

        $this->assertNotEmpty($rows);
        foreach ($rows as $row) {
            $this->assertSame('Low stock', $row['flag']);
        }
    }

    public function test_inventory_report_can_filter_by_container_status(): void
    {
        $this->seed(InventorySeeder::class);

        $rows = $this->actingAs($this->staff())
            ->get('/staff/reports/inventory?container_status=at_station_empty')
            ->viewData('rows');

        $this->assertCount(1, $rows);
        $this->assertSame('at station empty', $rows[0]['name']);
    }

    public function test_customers_report_totals_spend(): void
    {
        $customer = $this->customer();
        $this->order($customer, 120);
        $this->order($customer, 80);

        $totals = $this->actingAs($this->staff())->get('/staff/reports/customers')->viewData('totals');

        $this->assertSame(200.0, $totals['spent']);
    }

    public function test_deliveries_report_filters_by_driver(): void
    {
        $customer = $this->customer();
        $order = Order::factory()->create(['customer_id' => $customer->id, 'order_type' => 'delivery']);
        $driverA = User::factory()->driver()->create();
        $driverB = User::factory()->driver()->create();

        Delivery::create(['order_id' => $order->id, 'driver_id' => $driverA->id, 'status' => Delivery::STATUS_ASSIGNED]);
        $other = Order::factory()->create(['customer_id' => $customer->id, 'order_type' => 'delivery']);
        Delivery::create(['order_id' => $other->id, 'driver_id' => $driverB->id, 'status' => Delivery::STATUS_ASSIGNED]);

        $rows = $this->actingAs($this->staff())
            ->get("/staff/reports/deliveries?driver_id={$driverA->id}")
            ->viewData('rows');

        $this->assertCount(1, $rows);
    }

    public function test_containers_report_lists_every_status(): void
    {
        $this->seed(InventorySeeder::class);

        $rows = $this->actingAs($this->staff())->get('/staff/reports/containers')->viewData('rows');

        $this->assertCount(count(ContainerInventory::STATUSES), $rows);
    }

    public function test_barangay_list_is_valid(): void
    {
        $this->assertTrue(Barangay::isValid('Malanday'));
        $this->assertFalse(Barangay::isValid('Nowhere'));
        $this->assertFalse(Barangay::isValid(null));
    }

    public function test_inventory_item_reports_low_stock_when_at_threshold(): void
    {
        $item = InventoryItem::create(['name' => 'thing', 'quantity' => 5, 'threshold' => 5]);

        $this->assertTrue($item->isLowStock());
    }

    public function test_containers_report_passes_transactions_to_the_view(): void
    {
        $response = $this->actingAs($this->staff())->get('/staff/reports/containers');

        $response->assertOk();
        $this->assertIsArray($response->viewData('transactions'));
    }

    public function test_containers_report_shows_the_transaction_ledger(): void
    {
        $customer = $this->customer();

        ContainerTransaction::create([
            'customer_id' => $customer->id,
            'order_id' => null,
            'type' => 'return',
            'quantity' => 2,
        ]);

        $this->actingAs($this->staff())
            ->get('/staff/reports/containers')
            ->assertOk()
            ->assertSee('Container transactions')
            ->assertSee('return');
    }

    /** @dataProvider reportTypes */
    public function test_every_report_uses_one_uniform_filter_width(string $type): void
    {
        $html = $this->actingAs($this->staff())->get("/staff/reports/{$type}")->assertOk()->getContent();

        // the filter form only, so the totals grid is not counted
        $form = preg_match('/<form method="GET" class="row g-2">(.*?)<\/form>/s', $html, $m) ? $m[1] : '';
        $this->assertNotSame('', $form, 'no filter form found');

        preg_match_all('/class="col-md-(\d+)"/', $form, $widths);

        $this->assertNotEmpty($widths[1], 'no filter fields found');
        $this->assertSame(
            [3],
            array_values(array_unique(array_map('intval', $widths[1]))),
            'filter fields must all share one width, otherwise the grid renders ragged'
        );
    }

    /** @dataProvider reportTypes */
    public function test_no_report_emits_the_dead_type_field(string $type): void
    {
        $html = $this->actingAs($this->staff())->get("/staff/reports/{$type}")->assertOk()->getContent();

        $this->assertStringNotContainsString('name="_type"', $html);
    }

    public function test_the_report_columns_do_not_stretch_to_the_table_height(): void
    {
        $html = $this->actingAs($this->staff())->get('/staff/reports/sales')->assertOk()->getContent();

        // .px-card is height:100%, so without this the sidebar stretches into a
        // tall empty box alongside a long report table
        $this->assertStringContainsString('row g-4 align-items-start', $html);
    }
}
