<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Miembros de cada familia. Un usuario pertenece a una sola familia.
     */
    public function up(): void
    {
        Schema::create('familia_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('familia_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('familia_user');
    }
};
