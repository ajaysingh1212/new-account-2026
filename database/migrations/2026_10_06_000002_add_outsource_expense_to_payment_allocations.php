<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('party_payment_allocations', function (Blueprint $table) {
            $table->decimal('outsource_expense_amount', 15, 2)->default(0);
        });

        // Preserve the invoice distribution previously displayed for existing payments.
        DB::table('party_payments')->where('outsource_expense_amount', '>', 0)->orderBy('id')->each(function ($payment) {
            $rows = DB::table('party_payment_allocations')->where('party_payment_id', $payment->id)->orderBy('id')->get();
            $remaining = (float) $payment->outsource_expense_amount;
            foreach ($rows as $index => $row) {
                $expense = $index === $rows->count() - 1 ? $remaining : round((float) $payment->outsource_expense_amount * (float) $row->amount / max(0.01, (float) $payment->amount), 2);
                DB::table('party_payment_allocations')->where('id', $row->id)->update(['outsource_expense_amount' => $expense]);
                $remaining = round($remaining - $expense, 2);
            }
        });
    }

    public function down(): void
    {
        Schema::table('party_payment_allocations', function (Blueprint $table) {
            $table->dropColumn('outsource_expense_amount');
        });
    }
};
