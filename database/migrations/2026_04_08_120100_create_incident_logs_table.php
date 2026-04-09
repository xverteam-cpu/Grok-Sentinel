<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('event_type', 64);
            $table->string('source_ip', 45)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('city', 128)->nullable();
            $table->string('region', 128)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('metadata');
            $table->unsignedTinyInteger('risk_score')->default(0);
            $table->boolean('is_reviewed')->default(false);
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'country_code', 'detected_at']);
            $table->index(['user_id', 'detected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_logs');
    }
};
