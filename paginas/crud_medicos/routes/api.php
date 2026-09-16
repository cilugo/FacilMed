<?php

use App\Http\Controllers\Api\MedicoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas do módulo de Médicos — FacilMed
|--------------------------------------------------------------------------
| Adicione este bloco ao seu routes/api.php.
| Suporta pesquisa por nome, especialidade, estabelecimento e convênio
| via query string em GET /medicos (ex: /medicos?especialidade_id=2).
*/

Route::prefix('medicos')->group(function () {
    Route::get('/', [MedicoController::class, 'index']);
    Route::post('/', [MedicoController::class, 'store']);
    Route::get('/{medico}', [MedicoController::class, 'show']);
    Route::put('/{medico}', [MedicoController::class, 'update']);
    Route::patch('/{medico}', [MedicoController::class, 'update']);
    Route::delete('/{medico}', [MedicoController::class, 'destroy']);
    Route::patch('/{id}/reativar', [MedicoController::class, 'reativar']);
});
