<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('statement_id')->constrained()->onDelete('cascade');
            $table->dateTime('transaction_date')->nullable();
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->decimal('charge', 15, 2)->default(0); // detected charge
            $table->string('type'); // debit, credit
            $table->string('category')->nullable(); // category: transfer, bill, airtime, etc.
            $table->boolean('is_charge_row')->default(false); // is this row a charge row?
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
