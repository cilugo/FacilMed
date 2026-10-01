<?php

namespace App\Http\Controllers;

use App\Models\Foto;
use Illuminate\Http\Request;

/**
 * Devolve uma foto guardada no banco (01/10/2026).
 *
 * Foto de local e de médico é pública (aparece na busca e nas páginas
 * públicas). Foto de PACIENTE só o próprio paciente vê - para qualquer outra
 * pessoa a resposta é 404, como se não existisse (nem confirma que existe).
 */
class FotoController extends Controller
{
    public function mostrar(Request $request, Foto $foto)
    {
        if ($foto->paciente_id !== null) {
            abort_unless($request->user()?->paciente?->id === $foto->paciente_id, 404);
        }

        return response(base64_decode($foto->conteudo), 200, [
            'Content-Type'           => $foto->mime,
            'X-Content-Type-Options' => 'nosniff',
            // Foto de paciente: só o navegador dele guarda. As outras: qualquer cache, por um dia.
            'Cache-Control'          => ($foto->paciente_id ? 'private' : 'public') . ', max-age=86400',
        ]);
    }
}
