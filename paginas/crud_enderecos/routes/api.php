<?php

use App\Http\Controllers\Api\EnderecoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas do módulo de Endereços — FacilMed
|--------------------------------------------------------------------------
| Adicione este bloco ao seu routes/api.php.
*/

Route::prefix('enderecos')->group(function () {
    Route::get('/', [EnderecoController::class, 'index']);
    Route::post('/', [EnderecoController::class, 'store']);
    Route::get('/{endereco}', [EnderecoController::class, 'show']);
    Route::put('/{endereco}', [EnderecoController::class, 'update']);
    Route::patch('/{endereco}', [EnderecoController::class, 'update']);
    Route::delete('/{endereco}', [EnderecoController::class, 'destroy']);
});
