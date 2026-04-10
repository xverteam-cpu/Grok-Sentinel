<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('country_code', 2)->nullable();
            $table->text('bank_name');
            $table->text('branch_code')->nullable();
            $table->text('account_number');
            $table->text('routing_number')->nullable();
            $table->text('account_holder');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_profiles');
    }
};