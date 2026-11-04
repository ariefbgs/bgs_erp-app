<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\ImageUploadService;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $sortColumn = $request->get('sort', 'updated_at');
        $sortDirection = $request->get('direction', 'desc');

        $allowedColumns = ['brand', 'product_code', 'name', 'updated_at'];
        if (!in_array($sortColumn, $allowedColumns)) {
            $sortColumn = 'updated_at';
        }

        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $query = Product::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('product_code', 'like', '%' . $search . '%')
                    ->orWhere('product_code2', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('name2', 'like', '%' . $search . '%')
                    ->orWhere('specification', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('brand', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('unit')) {
            $query->where('unit', $request->unit);
        }

        if ($request->filled('stock')) {
            if ($request->stock == 'available') {
                $query->where('stock', '>', 0);
            } elseif ($request->stock == 'empty') {
                $query->where('stock', '<=', 0);
            } elseif ($request->stock == 'low') {
                $query->whereBetween('stock', [1, 5]);
            }
        }

        $products = $query->orderBy($sortColumn, $sortDirection)->paginate(10);

        return view('products.index', compact('products'));
    }

    private function generateAutoCode()
    {
        $lastProduct = Product::orderBy('id', 'desc')->first();
        $nextSequence = 1;

        if ($lastProduct && $lastProduct->product_code) {
            $parts = explode('-', $lastProduct->product_code);
            if (count($parts) == 2 && is_numeric($parts[1])) {
                $nextSequence = (int)$parts[1] + 1;
            }
        }

        return 'BGS-' . sprintf('%04d', $nextSequence);
    }

    public function create()
    {
        $autoCode = $this->generateAutoCode();
        return view('products.create', compact('autoCode'));
    }

    public function store(Request $request)
    {
        // AJAX quick add (tanpa gambar)
        if ($request->ajax()) {
            $validated = $request->validate([
                'name' => 'required|string',
                'name2' => 'nullable|string',
                'brand' => 'nullable|string|max:100',
                'price' => 'required|numeric|min:0',
                'product_code2' => 'nullable|unique:products,product_code2',
            ]);

            $product = new Product();
            $product->product_code = $this->generateAutoCode();
            $product->name = $validated['name'];
            $product->name2 = $validated['name2'] ?? $validated['name'];
            $product->brand = $validated['brand'] ?? '-';
            $product->price = $validated['price'];
            $product->product_code2 = $validated['product_code2'];
            $product->unit = 'Pcs';
            $product->purchase_price = 0;
            $product->stock = 0;
            $product->save();

            return response()->json([
                'success' => true,
                'product' => $product
            ]);
        }

        // Regular form store (dengan gambar)
        $validated = $request->validate([
            'product_code' => 'required|unique:products,product_code',
            'name' => 'required|string',
            'product_code2' => 'nullable|unique:products,product_code2',
            'name2' => 'required|string',
            'brand' => 'nullable|string|max:100',
            'unit' => 'required',
            'price' => 'required|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable',
            'specification' => 'nullable',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = app(ImageUploadService::class)->store(
                $request->file('image'), 'uploads/products', 1600
            );
        }

        try {
            Product::create($validated);
        } catch (\Throwable $exception) {
            if ($request->hasFile('image') && is_file(public_path($validated['image']))) {
                @unlink(public_path($validated['image']));
            }
            throw $exception;
        }

        return redirect()->route('products.index')
            ->with('success', 'Product successfully added');
    }

    public function show($id)
    {
        $product = Product::findOrFail($id);
        return view('products.show', compact('product'));
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'product_code' => 'required|unique:products,product_code,' . $id,
            'name' => 'required|string',
            'product_code2' => 'nullable|unique:products,product_code2,' . $id,
            'name2' => 'required|string',
            'brand' => 'nullable|string|max:100',
            'unit' => 'required',
            'price' => 'required|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable',
            'specification' => 'nullable',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        $oldImage = $product->image;
        if ($request->hasFile('image')) {
            $validated['image'] = app(ImageUploadService::class)->store(
                $request->file('image'), 'uploads/products', 1600
            );
        } else {
            // An empty upload must preserve the previous image.
            unset($validated['image']);
        }

        try {
            $product->update($validated);
        } catch (\Throwable $exception) {
            if ($request->hasFile('image') && is_file(public_path($validated['image']))) {
                @unlink(public_path($validated['image']));
            }
            throw $exception;
        }
        if ($request->hasFile('image') && $oldImage && is_file(public_path($oldImage))) {
            @unlink(public_path($oldImage));
        }

        return redirect()->route('products.index')
            ->with('success', 'Product successfully updated');
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return redirect()->route('products.index')->with('error', 'Product not found');
        }
        if ($product->image && file_exists(public_path($product->image))) {
            unlink(public_path($product->image));
        }
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted');
    }
}