<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            // Nullable: se genera a partir del id (AF-000001) justo después del insert.
            $table->string('affiliation_code', 20)->nullable()->unique();
            $table->enum('document_type', ['DNI', 'CE', 'PASAPORTE']);
            $table->string('document_number', 20);
            $table->string('first_names', 100);
            $table->string('last_names', 100);
            $table->date('birth_date');
            $table->date('affiliation_date');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->enum('status', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
            $table->timestamps();

            // RN-04: no se repite tipo + número de documento.
            $table->unique(['document_type', 'document_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
