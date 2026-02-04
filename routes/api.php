<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RutaTecnicaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


/*
|--------------------------------------------------------------------------
| NOTA IMPORTANTE
|--------------------------------------------------------------------------
|
| Estas rutas deben agregarse al archivo routes/api.php de tu proyecto Laravel
| O puedes crear un archivo separado y registrarlo en el RouteServiceProvider
|
| Si usas autenticación diferente a Sanctum, ajusta el middleware correspondiente
|
*/