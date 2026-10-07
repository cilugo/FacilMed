<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Foto de perfil (01/10/2026): usuário, clínica, admin e o médico
 * (este, enviado pela clínica).
 *
 * POR QUE public/uploads/fotos E NÃO storage/: o projeto roda no XAMPP
 * sem `php artisan storage:link`. Arquivo em public/ é servido direto
 * pelo .htaccess da raiz (regra 2), sem link simbólico.
 *
 * Segurança: o FormRequest só aceita imagem (jpg, png ou webp, até 2 MB)
 * e o nome do arquivo é sorteado aqui, com a extensão do TIPO REAL do
 * arquivo — nunca o nome que veio do computador de quem enviou.
 */
final class FotoDePerfil
{
    public const PASTA = 'uploads/fotos';

    /** Nos testes, outra pasta: o teste apaga ela inteira no fim sem tocar nas fotos de verdade. */
    public const PASTA_TESTES = 'uploads/fotos-testes';

    public static function pasta(): string
    {
        return app()->runningUnitTests() ? self::PASTA_TESTES : self::PASTA;
    }

    /** Regras de validação, iguais em todo formulário com foto. */
    public const REGRAS = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];

    /** Grava a foto e devolve o caminho relativo a public/ (vai no banco). */
    public static function salvar(UploadedFile $arquivo, ?string $anterior = null): string
    {
        $nome = Str::random(32) . '.' . ($arquivo->guessExtension() ?: 'jpg');
        $arquivo->move(public_path(self::pasta()), $nome);

        self::apagar($anterior);

        return self::pasta() . '/' . $nome;
    }

    /**
     * Apaga o arquivo SÓ se ele foi enviado pelo site (uploads/fotos).
     * As fotos de demonstração do seeder (imgs/medicos) nunca são apagadas.
     */
    public static function apagar(?string $caminho): void
    {
        if ($caminho && str_starts_with($caminho, self::pasta() . '/') && ! str_contains($caminho, '..')) {
            @unlink(public_path($caminho));
        }
    }

    public static function url(?string $caminho): ?string
    {
        return $caminho ? asset($caminho) : null;
    }
}
