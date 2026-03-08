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
        Schema::create('activity_logs', function (Blueprint $table) {
    $table->id();

    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

    $table->string('action');
    $table->text('description')->nullable();

    $table->ipAddress('ip_address')->nullable();
    $table->text('user_agent')->nullable();

    $table->boolean('success')->default(true);

    $table->nullableMorphs('loggable');

    $table->timestamps();

});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
