<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 07/10/2026 (decisão do grupo): a foto de perfil passa a ficar NO BANCO.
 *
 * POR QUE: o Render grátis apaga os arquivos enviados toda vez que o site
 * atualiza ou reinicia (o disco dele é temporário). Em public/uploads/fotos a
 * foto sumia a cada deploy; no banco (Aiven) ela fica. A ideia veio da main
 * de 01/10 (tabela `fotos`), adaptada para o jeito do PointMed.
 *
 * Como liga com o resto: `users.foto` e `medicos.foto` continuam sendo um
 * texto. Foto enviada pelo site guarda "foto:<chave>" (a chave desta tabela);
 * foto de demonstração do seeder continua sendo um caminho em public/
 * ("imgs/medicos/medico3.jpeg"). Quem decide é App\Support\FotoDePerfil.
 *
 * - `chave`: 40 letras sorteadas, usada no endereço /foto/{chave}. Não é o id
 *   de propósito: com id (1, 2, 3...) qualquer um percorreria as fotos de
 *   todo mundo. É o mesmo nível de proteção do nome sorteado do arquivo antigo.
 * - `conteudo`: a imagem em base64 (texto) numa coluna LONGTEXT. Funciona igual
 *   no MariaDB do XAMPP e no MySQL do Aiven, sem lidar com coluna binária. O
 *   formulário limita a 2 MB.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fotos', function (Blueprint $table) {
            $table->id();
            $table->string('chave', 40)->unique();
            $table->string('mime', 30);       // image/jpeg, image/png ou image/webp
            $table->longText('conteudo');     // a imagem em base64
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fotos');
    }
};
