<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('income_id');
            $table->string('number')->nullable();
            $table->date('date')->nullable();
            $table->date('last_change_date')->nullable();
            $table->string('supplier_article')->nullable();
            $table->string('tech_size')->nullable();
            $table->bigInteger('barcode')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('total_price', 12, 2)->nullable();
            $table->date('date_close')->nullable();
            $table->string('warehouse_name')->nullable();
            $table->bigInteger('nm_id')->nullable();
            $table->timestamps();

            $table->unique(['income_id', 'barcode', 'supplier_article'], 'incomes_unique_row');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
