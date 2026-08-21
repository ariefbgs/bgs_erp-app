<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('expense_no', 'LIKE', "%{$search}%");
            });
        }

        $expense = $query->latest()->paginate(10);
        return view('expense.index', compact('expense'));
    }

    public function create()
    {
        $defaultExpenseNumber = Expense::generateExpenseNumber(now()->format('Y-m-d'));
        return view('expense.create', compact('defaultExpenseNumber'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'project_name'     => 'nullable|string|max:255',
                'frequency'        => 'required|in:' . implode(',', Expense::frequencies()),
                'expense_type'     => 'required|in:' . implode(',', Expense::expenseTypes()),
                'amount'           => 'required|numeric|min:0',
                'expense_date'     => 'required|date',
                'expense_doc_no'   => 'nullable|string|max:50',
                'expense_doc_date' => 'nullable|date',
                'period_month'     => 'nullable|date_format:Y-m',
                'description'      => 'nullable|string',
                'attachment'       => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
            ]);

            // Generate nomor otomatis
            $validated['expense_no'] = Expense::generateExpenseNumber($validated['expense_date']);

            // Handle attachment
            if ($request->hasFile('attachment')) {
                $path = $request->file('attachment')->store('attachments', 'public');
                $validated['attachment'] = $path;
            }

            Expense::create($validated);

            return redirect()->route('expense.index')
                             ->with('success', 'Expense created successfully.');

        } catch (ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            Log::error('Expense store error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    public function show(Expense $expense)
    {
        return view('expense.show', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        return view('expense.edit', compact('expense'));
    }

    public function update(Request $request, Expense $expense)
    {
        try {
            $validated = $request->validate([
                'project_name'     => 'nullable|string|max:255',
                'frequency'        => 'required|in:' . implode(',', Expense::frequencies()),
                'expense_type'     => 'required|in:' . implode(',', Expense::expenseTypes()),
                'amount'           => 'required|numeric|min:0',
                'expense_date'     => 'required|date',
                'expense_doc_no'   => 'nullable|string|max:50',
                'expense_doc_date' => 'nullable|date',
                'period_month'     => 'nullable|date_format:Y-m',
                'description'      => 'nullable|string',
                'attachment'       => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
            ]);

            // Handle attachment: jika ada file baru, simpan dan hapus yang lama
            if ($request->hasFile('attachment')) {
                // Hapus file lama jika ada
                if ($expense->attachment && Storage::disk('public')->exists($expense->attachment)) {
                    Storage::disk('public')->delete($expense->attachment);
                }
                $path = $request->file('attachment')->store('attachments', 'public');
                $validated['attachment'] = $path;
            } else {
                // Jika tidak ada file baru, pertahankan attachment lama
                unset($validated['attachment']);
            }

            $expense->update($validated);

            return redirect()->route('expense.index')
                             ->with('success', 'Expense updated successfully.');

        } catch (ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            Log::error('Expense update error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal update data: ' . $e->getMessage());
        }
    }

    public function destroy(Expense $expense)
    {
        try {
            if ($expense->attachment && Storage::disk('public')->exists($expense->attachment)) {
                Storage::disk('public')->delete($expense->attachment);
            }
            $expense->delete();

            return redirect()->route('expense.index')
                             ->with('success', 'Expense deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Expense delete error: ' . $e->getMessage());
            return back()->with('error', 'Gagal hapus data: ' . $e->getMessage());
        }
    }

    public function viewImage(Expense $expense)
    {
        if ($expense->attachment && Storage::disk('public')->exists($expense->attachment)) {
            $path = Storage::disk('public')->path($expense->attachment);
            return response()->file($path);
        }
        abort(404, 'File not found.');
    }
}