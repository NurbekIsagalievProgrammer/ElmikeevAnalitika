<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->date('last_change_date')->nullable();
            $table->string('supplier_article')->nullable();
            $table->string('tech_size')->nullable();
            $table->bigInteger('barcode')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->boolean('is_supply')->default(false);
            $table->boolean('is_realization')->default(false);
            $table->unsignedInteger('quantity_full')->default(0);
            $table->string('warehouse_name');
            $table->unsignedInteger('in_way_to_client')->default(0);
            $table->unsignedInteger('in_way_from_client')->default(0);
            $table->bigInteger('nm_id');
            $table->string('subject')->nullable();
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->bigInteger('sc_code')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('discount')->nullable();
            $table->timestamps();

            $table->unique(['date', 'nm_id', 'warehouse_name', 'barcode'], 'stocks_unique_row');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
