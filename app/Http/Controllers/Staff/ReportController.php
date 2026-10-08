<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\ReportBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public const TYPES = [
        'sales' => 'Sales',
        'sales_by_product' => 'Sales by product',
        'payments' => 'Payments',
        'orders' => 'Orders',
        'deliveries' => 'Deliveries',
        'containers' => 'Containers',
        'inventory' => 'Inventory',
        'customers' => 'Customers',
    ];

    public function index(Request $request, string $type): View
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $builder = new ReportBuilder($type, $request);
        $result = $builder->build();

        return view('staff.reports.index', [
            'type' => $type,
            'title' => self::TYPES[$type],
            'allTypes' => self::TYPES,
            'filters' => $builder->filters(),
            'options' => $builder->options(),
            'rows' => $result['rows'],
            'totals' => $result['totals'],
            'columns' => $result['columns'],
            'transactions' => $result['transactions'] ?? [],
        ]);
    }
}
