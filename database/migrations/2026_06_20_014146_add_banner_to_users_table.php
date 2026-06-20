<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('banner_text')->default('管理人は開拓中！');
            $table->string('banner_text_color')->default('#166534');
            $table->string('banner_bg_color')->default('#dcfce7');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['banner_text', 'banner_text_color', 'banner_bg_color']);
        });
    }
};
