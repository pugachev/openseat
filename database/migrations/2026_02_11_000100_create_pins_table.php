<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pins', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('type'); // 1:待ち合わせ 2:お店 3:今の風景 4:注意
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('store_name')->nullable();
            $table->unsignedTinyInteger('status')->nullable(); // 0:空き 1:待ち 2:満席
            $table->text('comment')->nullable();
            $table->json('tags')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->timestamps();

            $table->index(['type']);
            $table->index(['latitude', 'longitude']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pins');
    }
};
