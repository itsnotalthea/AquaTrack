<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('admin.products.index', [
            'products' => Product::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', ['product' => new Product(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Product::create($request->validate($this->rules()));

        return redirect()->route('admin.products.index')->with('status', 'Product created.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', ['product' => $product]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($request->validate($this->rules()));

        return redirect()->route('admin.products.index')->with('status', 'Product updated.');
    }

    // products used in an order can only be deactivated, never removed
    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orderItems()->exists()) {
            return back()->withErrors([
                'delete' => 'This product appears in past orders and cannot be deleted. Deactivate it instead.',
            ]);
        }

        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Product deleted.');
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'unit' => ['required', 'string', 'in:gallon,piece,month'],
            'is_active' => ['boolean'],
        ];
    }
}
