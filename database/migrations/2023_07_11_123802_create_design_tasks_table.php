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
        Schema::create('design_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_request_id')->constrained('design_requests')->onDelete('cascade');
            $table->longText('description');
            $table->foreignId('created_id')->constrained('users');
            $table->foreignId('updated_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('design_tasks');
    }
};
