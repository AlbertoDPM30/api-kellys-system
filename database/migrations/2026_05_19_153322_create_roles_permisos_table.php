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
        Schema::create('roles_permisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('roles')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('permiso_id')->constrained('permisos')->onUpdate('cascade')->onDelete('cascade');
            $table->unique(['rol_id', 'permiso_id']);
            $table->timestamps();
            // $table->timestamps('created_at');
            // $table->timestamps('updated_at')->default('CURRENT_TIMESTAMP');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles_permisos');
    }
};
