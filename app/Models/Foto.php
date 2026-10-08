<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Foto de perfil guardada no banco (07/10/2026) — o porquê está na migration
 * fotos_no_banco. Ninguém usa este model direto: tudo passa por
 * App\Support\FotoDePerfil (salvar, apagar, endereço da imagem).
 *
 * A coluna `conteudo` é pesada (a imagem inteira em base64), por isso fica em
 * $hidden: nunca vai junto num toArray/JSON.
 */
class Foto extends Model
{
    public const TIPOS = ['image/jpeg', 'image/png', 'image/webp'];

    protected $table = 'fotos';

    protected $fillable = ['chave', 'mime', 'conteudo'];

    protected $hidden = ['conteudo'];
}
