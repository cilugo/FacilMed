<?php

use App\Http\Controllers\Api\AdministradorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas do módulo de Administradores — FacilMed
|--------------------------------------------------------------------------
| Adicione este bloco ao seu routes/api.php.
| Recomenda-se proteger este módulo com um middleware/policy que só
| permita acesso a usuários já autenticados como administradores.
*/

Route::prefix('administradores')->group(function () {
    Route::get('/', [AdministradorController::class, 'index']);
    Route::post('/', [AdministradorController::class, 'store']);
    Route::get('/{administrador}', [AdministradorController::class, 'show']);
    Route::put('/{administrador}', [AdministradorController::class, 'update']);
    Route::patch('/{administrador}', [AdministradorController::class, 'update']);
    Route::delete('/{administrador}', [AdministradorController::class, 'destroy']);
});
