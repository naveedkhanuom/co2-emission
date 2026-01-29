<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factor_organizations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique(); // e.g. DEFRA, EPA, IPCC
            $table->string('name', 100);
            $table->string('url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factor_organizations');
    }
};

