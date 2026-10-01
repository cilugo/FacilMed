<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;

/**
 * Foto guardada no banco (01/10/2026) - ver a migration create_fotos_table
 * para o porquê. Dono: um local (galeria), um médico ou um paciente.
 *
 * A coluna `conteudo` é pesada (a imagem inteira em base64). Por isso:
 *  - ela está em $hidden (nunca vai junto num toArray/JSON);
 *  - as relações (Local::fotos, Paciente::foto...) NÃO a carregam - usam
 *    Foto::COLUNAS_LEVES. Só o FotoController lê a imagem, uma de cada vez.
 *
 * Na tela, use $foto->url(): a rota /foto/{id} devolve a imagem.
 */
class Foto extends Model
{
    public const TIPOS = ['image/jpeg', 'image/png', 'image/webp'];

    /** Em KB, para a regra "max" do Laravel: 2 MB. */
    public const TAMANHO_MAXIMO_KB = 2048;

    /** Fotos por local (galeria da página do local). */
    public const MAXIMO_POR_LOCAL = 6;

    /** Tudo menos a imagem - para listas e relações. */
    public const COLUNAS_LEVES = ['id', 'local_id', 'medico_id', 'paciente_id', 'mime', 'ordem', 'updated_at'];

    protected $fillable = ['local_id', 'medico_id', 'paciente_id', 'mime', 'conteudo', 'ordem'];

    protected $hidden = ['conteudo'];

    protected $casts = ['ordem' => 'integer'];

    /**
     * Regras de validação do campo de upload, iguais em todas as telas.
     * 'image' + 'mimes' conferem o CONTEÚDO do arquivo (extensão fileinfo do
     * PHP), não só o nome - um .exe renomeado para .jpg é recusado.
     */
    public static function regras(): array
    {
        return ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . self::TAMANHO_MAXIMO_KB];
    }

    public static function mensagens(string $campo = 'foto'): array
    {
        return [
            "{$campo}.required" => 'Escolha uma foto.',
            "{$campo}.image"    => 'O arquivo precisa ser uma imagem (JPG, PNG ou WebP).',
            "{$campo}.mimes"    => 'O arquivo precisa ser uma imagem (JPG, PNG ou WebP).',
            "{$campo}.max"      => 'A foto pode ter no máximo 2 MB.',
            "{$campo}.uploaded" => 'A foto não chegou inteira (talvez seja grande demais). Tente uma menor.',
        ];
    }

    /** Monta os campos da foto a partir do arquivo enviado. */
    public static function dadosDoArquivo(UploadedFile $arquivo): array
    {
        $mime = $arquivo->getMimeType();

        return [
            'mime'     => in_array($mime, self::TIPOS, true) ? $mime : 'image/jpeg',
            'conteudo' => base64_encode((string) file_get_contents($arquivo->getRealPath())),
        ];
    }

    /**
     * Endereço da imagem. O ?v= muda quando a foto muda, para o navegador não
     * mostrar a antiga guardada no cache dele.
     */
    public function url(): string
    {
        return route('fotos.mostrar', ['foto' => $this->id, 'v' => $this->updated_at?->timestamp]);
    }

    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class);
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }
}
