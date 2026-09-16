<?php

use App\Http\Controllers\Api\PacienteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas do módulo de Pacientes — FacilMed
|--------------------------------------------------------------------------
| Adicione este bloco ao seu routes/api.php.
*/

Route::prefix('pacientes')->group(function () {
    Route::get('/', [PacienteController::class, 'index']);
    Route::post('/', [PacienteController::class, 'store']);
    Route::get('/{paciente}', [PacienteController::class, 'show']);
    Route::put('/{paciente}', [PacienteController::class, 'update']);
    Route::patch('/{paciente}', [PacienteController::class, 'update']);
    Route::delete('/{paciente}', [PacienteController::class, 'destroy']);
});
