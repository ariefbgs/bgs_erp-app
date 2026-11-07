<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'expense_no',
        'expense_date',
        'expense_doc_no',
        'expense_doc_date',
        'period_month',
        'description',
        'project_name',
        'frequency',
        'expense_type',
        'amount',
        'attachment',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('expenses')) {
            throw new RuntimeException('Expenses table missing.');
        }

        $present = array_filter(
            self::COLUMNS,
            fn ($name) => Schema::hasColumn('expenses', $name)
        );

        if (count($present) === count(self::COLUMNS)) {
            if (!Schema::hasIndex('expenses', 'expense_no')) {
                if (DB::table('expenses')->exists()) {
                    throw new RuntimeException(
                        'Review existing expense data before adding unique index.'
                    );
                }

                Schema::table('expenses', function (Blueprint $table) {
                    $table->unique(
                        ['expense_no', 'expense_date'],
                        'expense_no'
                    );
                });
            }

            return;
        }

        if (count($present) !== 0) {
            throw new RuntimeException(
                'Partial expenses schema detected; manual review required.'
            );
        }

        if (DB::table('expenses')->exists()) {
            throw new RuntimeException(
                'Existing expense rows require controlled migration mapping.'
            );
        }

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('expense_no', 50);
            $table->date('expense_date');
            $table->string('expense_doc_no', 50)->nullable();
            $table->date('expense_doc_date')->nullable();
            $table->string('period_month', 7)->nullable();
            $table->text('description')->nullable();
            $table->string('project_name')->nullable();

            $table->enum('frequency', [
                'Daily',
                'Weekly',
                'Monthly',
                'Quarterly',
                'Semesterly',
                'Annual',
                'One Time',
            ]);

            $table->enum('expense_type', [
                'Operational',
                'Maintenance',
                'Project',
                'Emergency',
                'Subscription',
                'CAPEX',
                'OPEX',
            ]);

            $table->decimal('amount', 15, 2);
            $table->string('attachment')->nullable();

            $table->unique(
                ['expense_no', 'expense_date'],
                'expense_no'
            );
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('expenses')) {
            return;
        }

        if (DB::table('expenses')->exists()) {
            throw new RuntimeException(
                'Rollback blocked: expenses contains data.'
            );
        }

        if (Schema::hasIndex('expenses', 'expense_no')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropUnique('expense_no');
            });
        }

        $columns = array_values(array_filter(
            self::COLUMNS,
            fn ($name) => Schema::hasColumn('expenses', $name)
        ));

        if ($columns !== []) {
            Schema::table('expenses', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};