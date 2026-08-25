<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderDetail;
use App\Models\PoCustomer;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class DeliveryOrderController extends Controller
{
    public function index()
    {
        $dos = DeliveryOrder::with('poCustomer.customer')->orderBy('created_at', 'desc')->paginate(10);
        return view('delivery_orders.index', compact('dos'));
    }

    public function create()
    {
        $poCustomers = PoCustomer::with('customer')
            ->where('status', 'proceed')
            ->whereIn('invoice_status', ['partial', 'completed'])
            ->orderBy('po_date', 'desc')
            ->get();

        \Log::info('Jumlah PO Customer: ' . $poCustomers->count());
        
        return view('delivery_orders.create', compact('poCustomers'));
    }

    public function getPoCustomerDetails($id)
    {
        $poCustomer = PoCustomer::with(['details.product', 'customer'])->findOrFail($id);
        $items = [];
        foreach ($poCustomer->details as $detail) {
            $items[] = [
                'product_id' => $detail->product_id,
                'product_name' => $detail->product->name,
                'product_code' => $detail->product->product_code,
                'brand' => $detail->product->brand,
                'unit' => $detail->product->unit,
                'quantity' => $detail->quantity,
            ];
        }
        return response()->json([
            'po_customer' => $poCustomer,
            'items' => $items,
            'customer' => $poCustomer->customer,
            'shipping_address' => $poCustomer->customer->shipping_address ?? $poCustomer->customer->address ?? '',
            'receiver_name' => $poCustomer->customer->pic_do_name ?? $poCustomer->customer->pic_quotation_name ?? $poCustomer->customer->name ?? '',
            'payment_terms' => $poCustomer->payment_terms,
            'delivery_time' => $poCustomer->delivery_time,
        ]);
    }

    public function store(Request $request)
    {
        \Log::info('Delivery Order Store Request:', $request->all());

        // Validasi â€“ do_number TIDAK wajib diisi karena otomatis digenerate
        $request->validate([ 
            'po_customer_id' => 'required|exists:po_customers,id',
            'delivery_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:0',
            'shipping_address' => 'nullable|string',
            'receiver_name' => 'nullable|string',
            'notes' => 'nullable|string',
            'invoice_number' => 'nullable|string',
            'tax_invoice_number' => 'nullable|string',
            'delivery_by' => 'nullable|string|in:Wisnu,JNE,Courier Online',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
        ]);

        // Proses upload file
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $extension = $file->getClientOriginalExtension();
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $originalName) . '.' . $extension;
            
            $folder = 'delivery_orders';
            if (!Storage::disk('public')->exists($folder)) {
                Storage::disk('public')->makeDirectory($folder);
            }

            try {
                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    $image = ImageManager::gd()->read($file->getRealPath());
                    $image->resize(800, 800, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                    $image->toJpeg(80)->save(storage_path('app/public/' . $folder . '/' . $safeName));
                } else {
                    Storage::disk('public')->putFileAs($folder, $file, $safeName);
                }
                $attachmentPath = $folder . '/' . $safeName;
            } catch (\Exception $e) {
                \Log::warning('Gagal memproses attachment: ' . $e->getMessage());
                // Simpan asli jika gagal kompresi
                Storage::disk('public')->putFileAs($folder, $file, $safeName);
                $attachmentPath = $folder . '/' . $safeName;
            }
        }

        DB::beginTransaction();
        try {
            $poCustomer = PoCustomer::findOrFail($request->po_customer_id);

            // Generate DO Number
            $doDate = $request->delivery_date;
            $currentYear = date('Y', strtotime($doDate));
            $lastDo = DeliveryOrder::whereYear('delivery_date', $currentYear)
                ->orderBy('id', 'desc')
                ->first();
            if ($lastDo) {
                $lastNumber = (int) explode('/', $lastDo->do_number)[0];
                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '0001';
            }
            $romanMonth = $this->romanMonth(date('n', strtotime($doDate)));
            $doNumber = $newNumber . '/DO/BGS/' . $romanMonth . '/' . $currentYear;

            // Cegah duplicate entry dengan loop (fallback)
            $attempt = 0;
            while ($attempt < 5) {
                try {
                    $do = DeliveryOrder::create([
                        'do_number' => $doNumber,
                        'po_customer_id' => $request->po_customer_id,
                        'delivery_date' => $request->delivery_date,
                        'shipping_address' => $request->shipping_address,
                        'receiver_name' => $request->receiver_name,
                        'status' => 'pending',
                        'notes' => $request->notes,
                        'created_by' => optional(auth()->user())->name ?? 'system',
                        'invoice_number' => $request->invoice_number,
                        'tax_invoice_number' => $request->tax_invoice_number,
                        'delivery_by' => $request->delivery_by,
                        'attachment' => $attachmentPath,
                    ]);
                    break; // Berhasil, keluar dari loop
                } catch (QueryException $e) {
                    if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'do_number')) {
                        // Duplicate entry, increment nomor
                        $attempt++;
                        $lastNumber = (int) explode('/', $doNumber)[0] + 1;
                        $newNumber = str_pad($lastNumber, 4, '0', STR_PAD_LEFT);
                        $doNumber = $newNumber . '/DO/BGS/' . $romanMonth . '/' . $currentYear;
                        \Log::warning('Duplicate DO number, retrying with: ' . $doNumber);
                    } else {
                        throw $e;
                    }
                }
            }

            foreach ($request->items as $item) {
                if ($item['quantity'] > 0) {
                    DeliveryOrderDetail::create([
                        'delivery_order_id' => $do->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }

            DB::commit();

            $action = $request->input('action', 'save');
            if ($action == 'print_do') {
                return redirect()->route('delivery-orders.print', $do->id);
            } elseif ($action == 'print_receipt') {
                return redirect()->route('delivery-orders.print-receipt', $do->id);
            } else {
                return redirect()->route('delivery-orders.index')
                    ->with('success', 'Delivery Order successfully saved.');
            }
        } catch (QueryException $e) {
            DB::rollBack();
            \Log::error('Database error saat menyimpan DO: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', 'Gagal menyimpan Delivery Order: Terjadi duplikasi nomor atau kesalahan database.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error saat menyimpan DO: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', 'Gagal menyimpan Delivery Order: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $do = DeliveryOrder::with(['poCustomer.customer', 'details.product'])->findOrFail($id);
        return view('delivery_orders.show', compact('do'));
    }

    public function edit($id)
    {
        $do = DeliveryOrder::with('details')->findOrFail($id);
        
        if (!in_array($do->status, ['pending', 'partial', 'shipped'])) {
            return redirect()->route('delivery-orders.index')
                ->with('error', 'Only DO with status Pending, Partial or Shipped can be edited.');
        }
        
        $poCustomers = PoCustomer::with('customer')->where('status', 'proceed')->get();
        $products = Product::orderBy('name')->get();
        
        return view('delivery_orders.edit', compact('do', 'poCustomers', 'products'));
    }

    public function update(Request $request, $id)
    {
        $do = DeliveryOrder::findOrFail($id);
        
        if (!in_array($do->status, ['pending', 'partial', 'shipped'])) {
            return back()->with('error', 'Only DO with status Pending, Partial or Shipped can be updated.');
        }

        $request->validate([
            'do_number' => 'required|string|unique:delivery_orders,do_number,' . $do->id,
            'delivery_date' => 'required|date',
            'status' => 'required|in:pending,partial,shipped,delivered',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:0',
            'shipping_address' => 'nullable|string',
            'receiver_name' => 'nullable|string',
            'notes' => 'nullable|string',
            'invoice_number' => 'nullable|string',
            'tax_invoice_number' => 'nullable|string',
            'delivery_by' => 'nullable|string|in:Wisnu,JNE,Courier Online',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
        ]);

        DB::beginTransaction();
        try {
            $attachmentPath = $do->attachment;
            if ($request->hasFile('attachment')) {
                if ($attachmentPath && Storage::disk('public')->exists($attachmentPath)) {
                    Storage::disk('public')->delete($attachmentPath);
                }

                $file = $request->file('attachment');
                $extension = $file->getClientOriginalExtension();
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $originalName) . '.' . $extension;

                $folder = 'delivery_orders';
                if (!Storage::disk('public')->exists($folder)) {
                    Storage::disk('public')->makeDirectory($folder);
                }

                try {
                    if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                        $image = ImageManager::gd()->read($file->getRealPath());
                        $image->resize(800, 800, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                        $image->toJpeg(80)->save(storage_path('app/public/' . $folder . '/' . $safeName));
                    } else {
                        Storage::disk('public')->putFileAs($folder, $file, $safeName);
                    }
                    $attachmentPath = $folder . '/' . $safeName;
                } catch (\Exception $e) {
                    \Log::warning('Gagal memproses attachment: ' . $e->getMessage());
                    Storage::disk('public')->putFileAs($folder, $file, $safeName);
                    $attachmentPath = $folder . '/' . $safeName;
                }
            }

            $do->update([
                'do_number' => $request->do_number,
                'delivery_date' => $request->delivery_date,
                'status' => $request->status,
                'shipping_address' => $request->shipping_address,
                'receiver_name' => $request->receiver_name,
                'notes' => $request->notes,
                'invoice_number' => $request->invoice_number,
                'tax_invoice_number' => $request->tax_invoice_number,
                'delivery_by' => $request->delivery_by,
                'attachment' => $attachmentPath,
                'updated_by' => optional(auth()->user())->name ?? 'system',
            ]);

            $do->details()->delete();
            foreach ($request->items as $item) {
                if ($item['quantity'] > 0) {
                    DeliveryOrderDetail::create([
                        'delivery_order_id' => $do->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('delivery-orders.index')
                ->with('success', 'Delivery Order successfully updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error saat update DO: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', 'Failed to update Delivery Order: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $do = DeliveryOrder::findOrFail($id);
        if ($do->status != 'pending') {
            return back()->with('error', 'Only DO with status Pending can be deleted.');
        }
        try {
            $do->details()->delete();
            $do->delete();
            if ($do->attachment && Storage::disk('public')->exists($do->attachment)) {
                Storage::disk('public')->delete($do->attachment);
            }
            return redirect()->route('delivery-orders.index')->with('success', 'Delivery Order successfully deleted.');
        } catch (\Exception $e) {
            \Log::error('Error saat delete DO: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete Delivery Order: ' . $e->getMessage());
        }
    }

    public function getInvoicesByPo($po_customer_id)
    {
        // Hanya ambil invoice yang statusnya bukan cancelled dan belum digunakan di DO
        $invoices = \App\Models\InvoiceCustomer::where('po_customer_id', $po_customer_id)
            ->where('status', '!=', 'cancelled')
            ->whereNotIn('invoice_number', function ($query) {
                $query->select('invoice_number')->from('delivery_orders')->whereNotNull('invoice_number');
            })
            ->select('invoice_number', 'type', 'tax_invoice_number')
            ->get();

        foreach ($invoices as $invoice) {
            if (!isset($invoice->tax_invoice_number)) {
                $invoice->tax_invoice_number = ''; 
            }
        }

        return response()->json($invoices);
    }

    public function print($id)
    {
        try {
            $do = DeliveryOrder::with(['poCustomer.customer', 'details.product'])->findOrFail($id);
            $company = Company::where('is_active', true)->first();
            $pdf = Pdf::loadView('delivery_orders.print', compact('do', 'company'));
            $pdf->setPaper('a4', 'portrait');
            
            $safeNumber = str_replace('/', '_', $do->do_number);
            $filename = 'DeliveryOrder_' . $safeNumber . '.pdf';
            
            return $pdf->stream($filename);
        } catch (\Exception $e) {
            \Log::error('Error saat print DO: ' . $e->getMessage());
            return back()->with('error', 'Failed to print Delivery Order: ' . $e->getMessage());
        }
    }

    public function printReceipt($id)
    {
        try {
            $do = DeliveryOrder::with(['poCustomer.customer'])->findOrFail($id);
            $company = Company::where('is_active', true)->first();
            $pdf = Pdf::loadView('delivery_orders.receipt_print', compact('do', 'company'));
            $pdf->setPaper('a4', 'portrait');
            
            $safeNumber = str_replace('/', '_', $do->do_number);
            $filename = 'TandaTerima_' . $safeNumber . '.pdf';
            
            return $pdf->stream($filename);
        } catch (\Exception $e) {
            \Log::error('Error saat print receipt: ' . $e->getMessage());
            return back()->with('error', 'Failed to print Receipt: ' . $e->getMessage());
        }
    }

    // =====================================================
    // PRIVATE HELPER: ROMAN MONTH
    // =====================================================
    private function romanMonth($monthNum)
    {
        $month = (int) $monthNum;
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        return $romans[$month] ?? 'I';
    }
}