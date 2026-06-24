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
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->date('view_date');
            $table->string('path', 512);
            $table->string('route_name')->nullable();
            $table->unsignedBigInteger('count')->default(1);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();

            $table->unique(['view_date', 'path']);
            $table->index('view_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
