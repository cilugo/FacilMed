<?php

namespace App\Support;

use App\Models\Foto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Foto de perfil (01/10/2026): usuário, clínica, admin e o médico
 * (este, enviado pela clínica).
 *
 * 07/10/2026: a imagem fica NO BANCO (tabela `fotos`), não mais em
 * public/uploads/fotos — o Render apaga os arquivos enviados a cada deploy.
 * Ver a migration fotos_no_banco.
 *
 * O que vai em `users.foto` / `medicos.foto`:
 *  - "foto:<chave>"  foto enviada pelo site (linha da tabela `fotos`);
 *  - "imgs/..."      foto de demonstração do seeder, arquivo em public/.
 * Só esta classe sabe a diferença; as telas usam sempre `foto_url`.
 *
 * Segurança: o FormRequest só aceita imagem (jpg, png ou webp, até 2 MB) e o
 * tipo gravado é o tipo REAL do arquivo (fileinfo do PHP), nunca o nome que
 * veio do computador de quem enviou.
 */
final class FotoDePerfil
{
    /** Marca da foto que está no banco. */
    public const PREFIXO = 'foto:';

    /** Regras de validação, iguais em todo formulário com foto. */
    public const REGRAS = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];

    /** Grava a foto no banco, apaga a anterior e devolve o texto que vai em `foto`. */
    public static function salvar(UploadedFile $arquivo, ?string $anterior = null): string
    {
        $mime = $arquivo->getMimeType();

        $foto = Foto::create([
            'chave'    => Str::random(40),
            'mime'     => in_array($mime, Foto::TIPOS, true) ? $mime : 'image/jpeg',
            'conteudo' => base64_encode((string) file_get_contents($arquivo->getRealPath())),
        ]);

        self::apagar($anterior);

        return self::PREFIXO . $foto->chave;
    }

    /**
     * Apaga SÓ foto enviada pelo site (a que está no banco).
     * As fotos de demonstração do seeder (imgs/medicos) nunca são apagadas.
     */
    public static function apagar(?string $foto): void
    {
        if ($chave = self::chave($foto)) {
            Foto::where('chave', $chave)->delete();
        }
    }

    public static function url(?string $foto): ?string
    {
        if (! $foto) {
            return null;
        }

        $chave = self::chave($foto);

        return $chave ? route('foto.mostrar', $chave) : asset($foto);
    }

    /** "foto:abc..." → "abc..."; caminho de arquivo → null. */
    public static function chave(?string $foto): ?string
    {
        return $foto && str_starts_with($foto, self::PREFIXO) ? substr($foto, strlen(self::PREFIXO)) : null;
    }
}
