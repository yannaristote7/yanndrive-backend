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
    Schema::table('document_shares', function (Blueprint $table) {
        $table->string('token')->unique()->nullable()->after('document_id');
        $table->timestamp('expires_at')->nullable()->after('token');
        $table->integer('download_count')->default(0)->after('expires_at');
        $table->integer('max_downloads')->nullable()->after('download_count');
    });
}

public function down(): void
{
    Schema::table('document_shares', function (Blueprint $table) {
        $table->dropColumn(['token', 'expires_at', 'download_count', 'max_downloads']);
    });
}
};
