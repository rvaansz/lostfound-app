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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('item_categories');
            $table->foreignId('location_id')->constrained('locations');
            $table->enum('type', ['lost', 'found']);
            $table->string('name');
            $table->text('description');
            $table->string('photo')->nullable();
            $table->date('event_date');
            $table->time('event_time')->nullable();
            $table->enum('status', ['open', 'in_verification', 'completed'])->default('open');
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('event_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
