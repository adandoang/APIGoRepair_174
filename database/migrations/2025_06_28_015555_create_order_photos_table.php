<?php

// File: database/migrations/xxxx_xx_xx_xxxxxx_create_order_photos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('photo_url');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_photos');
    }
};