<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_links', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('title', 160);
            $table->text('target_url');
            $table->string('utm_source', 80)->nullable();
            $table->string('utm_medium', 80)->nullable();
            $table->text('webhook_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('clicks_count')->default(0);
            $table->timestamps();
        });

        Schema::create('link_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('short_link_id')->constrained('short_links')->cascadeOnDelete();
            $table->string('visitor_hash', 64)->index();
            $table->string('device_type', 32)->index();
            $table->string('referer_host', 190)->nullable()->index();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('clicked_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_clicks');
        Schema::dropIfExists('short_links');
    }
};
