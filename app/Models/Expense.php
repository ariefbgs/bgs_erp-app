<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_no',
        'expense_date',
        'expense_doc_no',
        'expense_doc_date',
        'period_month',
        'project_name',
        'frequency',
        'expense_type',
        'amount',
        'description',
        'attachment',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'expense_doc_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Opsional: aksesors untuk menampilkan label enum
    public static function frequencies(): array
    {
        return ['Daily', 'Weekly', 'Monthly', 'Quarterly', 'Semester', 'Yearly'];
    }

    public static function expenseTypes(): array
    {
        return ['Operational', 'Maintenance', 'Project', 'Emergency', 'Other'];
    }

    public static function generateExpenseNumber($expenseDate)
    {
        try {
            $year = date('Y', strtotime($expenseDate));
            $month = (int) date('n', strtotime($expenseDate)); // 1-12
            $monthRoman = self::romanNumerals($month);

            // Kode cabang (bisa diambil dari config)
            $branchCode = config('app.branch_code', 'BGS');

            // Cari nomor urut terakhir di tahun yang sama
            $lastExpense = self::whereYear('expense_date', $year)
                               ->orderBy('expense_no', 'desc')
                               ->first();

            if ($lastExpense && $lastExpense->expense_no) {
                // Ambil 4 digit pertama dari nomor terakhir
                $lastNumber = (int) substr($lastExpense->expense_no, 0, 4);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            // Format 4 digit dengan leading zero
            $formattedNumber = str_pad($newNumber, 4, '0', STR_PAD_LEFT);

            return "{$formattedNumber}/EXP/{$branchCode}/{$monthRoman}/{$year}";

        } catch (\Exception $e) {
            // Jika ada error, log dan kembalikan nomor default (misalnya berdasarkan timestamp)
            Log::error('Gagal generate expense number: ' . $e->getMessage());
            // Fallback: gunakan timestamp
            return '0001/EXP/BGS/I/' . date('Y');
        }
    }

    /**
     * Konversi angka bulan ke Romawi
     */
    private static function romanNumerals($month)
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
            5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
            9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        return $map[$month] ?? 'I';
    }
}