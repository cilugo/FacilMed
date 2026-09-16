<?php

use App\Http\Controllers\Api\DisponibilidadeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas do módulo de Disponibilidade/Horários — FacilMed
|--------------------------------------------------------------------------
| Adicione este bloco ao seu routes/api.php.
| Use ?apenas_disponiveis=true em GET /disponibilidades para retornar
| somente os horários realmente livres (não bloqueados e ativos) —
| é o que a tela de agendamento do paciente deve consumir.
*/

Route::prefix('disponibilidades')->group(function () {
    Route::get('/', [DisponibilidadeController::class, 'index']);
    Route::post('/', [DisponibilidadeController::class, 'store']);
    Route::get('/{disponibilidade}', [DisponibilidadeController::class, 'show']);
    Route::put('/{disponibilidade}', [DisponibilidadeController::class, 'update']);
    Route::patch('/{disponibilidade}', [DisponibilidadeController::class, 'update']);
    Route::patch('/{disponibilidade}/bloquear', [DisponibilidadeController::class, 'bloquear']);
    Route::delete('/{disponibilidade}', [DisponibilidadeController::class, 'destroy']);
});
