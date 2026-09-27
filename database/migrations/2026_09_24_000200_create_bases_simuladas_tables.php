<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BASES SIMULADAS — decisão do grupo em 24/09/2026.
 *
 * Como o FacilMed é todo fictício, o CFM, a Receita Federal e as
 * operadoras de plano também são simulados: estas três tabelas fazem o
 * papel desses sistemas externos. O cadastro consulta aqui e aprova ou
 * recusa NA HORA:
 *
 *   base_crms         → "CFM simulado":     o CRM existe e está ativo?
 *   base_cnpjs        → "Receita simulada": o CNPJ existe e está ativo?
 *   base_carteirinhas → "operadora simulada": a carteirinha existe, é
 *                        dessa pessoa, está ativa e dentro da validade?
 *
 * São tabelas SÓ DE LEITURA para o sistema: quem preenche é o seeder
 * (BaseSimuladaSeeder). Nenhuma tela escreve nelas — senão qualquer um
 * "validaria" o próprio dado.
 *
 * Documentos guardados SÓ COM DÍGITOS, a mesma convenção dos
 * FormRequests de cadastro (formatar só na exibição).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('base_crms', function (Blueprint $table) {
            $table->id();
            $table->string('crm', 10);
            $table->char('uf', 2);
            $table->string('nome', 150);
            $table->enum('situacao', ['ativo', 'suspenso', 'cassado'])->default('ativo');
            $table->timestamps();

            $table->unique(['crm', 'uf']);
        });

        Schema::create('base_cnpjs', function (Blueprint $table) {
            $table->id();
            $table->string('cnpj', 14)->unique();
            $table->string('razao_social', 150);
            $table->string('nome_fantasia', 150)->nullable();
            $table->enum('situacao', ['ativa', 'suspensa', 'baixada'])->default('ativa');
            $table->timestamps();
        });

        Schema::create('base_carteirinhas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plano_id')->constrained('planos')->cascadeOnDelete();
            $table->string('numero_carteirinha', 40);
            $table->string('beneficiario_nome', 150);
            $table->string('beneficiario_cpf', 11);
            $table->date('validade');
            $table->enum('situacao', ['ativa', 'cancelada'])->default('ativa');
            $table->timestamps();

            $table->unique(['plano_id', 'numero_carteirinha']);
            $table->index('beneficiario_cpf');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('base_carteirinhas');
        Schema::dropIfExists('base_cnpjs');
        Schema::dropIfExists('base_crms');
    }
};
