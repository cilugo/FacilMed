<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas do módulo de Usuários — FacilMed
|--------------------------------------------------------------------------
| Adicione este bloco ao seu routes/api.php.
| Recomenda-se proteger essas rotas com autenticação (ex: middleware
| 'auth:sanctum') e, futuramente, com policies por tipo de usuário.
*/

Route::prefix('usuarios')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/{user}', [UserController::class, 'show']);
    Route::put('/{user}', [UserController::class, 'update']);
    Route::patch('/{user}', [UserController::class, 'update']);
    Route::delete('/{user}', [UserController::class, 'destroy']);
    Route::patch('/{id}/reativar', [UserController::class, 'reativar']);
});
