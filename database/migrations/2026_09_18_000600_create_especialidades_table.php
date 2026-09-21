<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('especialidades', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique();
            $table->string('slug', 120)->unique();
            $table->string('icone', 60)->nullable();

            // Alimenta os cards de "especialidades em destaque" da home.
            // A home faz foreach nisto; nunca com a lista escrita no Blade.
            $table->boolean('destaque')->default(false);
            $table->boolean('ativo')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('especialidades');
    }
};
