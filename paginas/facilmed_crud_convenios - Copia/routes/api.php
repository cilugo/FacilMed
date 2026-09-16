<?php

use App\Http\Controllers\Api\ConvenioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas do módulo de Convênios — FacilMed
|--------------------------------------------------------------------------
| Adicione este bloco ao seu routes/api.php.
*/

Route::prefix('convenios')->group(function () {
    Route::get('/', [ConvenioController::class, 'index']);
    Route::post('/', [ConvenioController::class, 'store']);
    Route::get('/{convenio}', [ConvenioController::class, 'show']);
    Route::put('/{convenio}', [ConvenioController::class, 'update']);
    Route::patch('/{convenio}', [ConvenioController::class, 'update']);
    Route::delete('/{convenio}', [ConvenioController::class, 'destroy']);
    Route::patch('/{id}/reativar', [ConvenioController::class, 'reativar']);
});
