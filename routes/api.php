<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FolderController;
use App\Http\Controllers\Api\McpOAuthStorageController;
use App\Http\Controllers\Api\McpTokenController;
use App\Http\Controllers\Api\NoteController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Issues a Sanctum personal access token from credentials for the MCP server.
// Throttled since it accepts raw credentials without a session.
Route::post('/mcp/token', [McpTokenController::class, 'issue'])
    ->middleware('throttle:10,1');

Route::prefix('/mcp/oauth-storage')->group(function () {
    Route::get('/clients/{clientId}', [McpOAuthStorageController::class, 'getClient']);
    Route::post('/clients', [McpOAuthStorageController::class, 'storeClient']);
    Route::get('/codes/{code}', [McpOAuthStorageController::class, 'getCode']);
    Route::post('/codes', [McpOAuthStorageController::class, 'storeCode']);
    Route::delete('/codes/{code}', [McpOAuthStorageController::class, 'deleteCode']);
    Route::get('/tokens/refresh/{refreshToken}', [McpOAuthStorageController::class, 'getTokenByRefresh']);
    Route::get('/tokens/{token}', [McpOAuthStorageController::class, 'getToken']);
    Route::post('/tokens', [McpOAuthStorageController::class, 'storeToken']);
    Route::patch('/tokens/{token}/revoke', [McpOAuthStorageController::class, 'revokeToken']);
    Route::delete('/expired', [McpOAuthStorageController::class, 'cleanupExpired']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::apiResource('folders', FolderController::class)->except(['show']);
    Route::apiResource('notes', NoteController::class)->except(['show']);

});
Route::get('/test', function(){
    return response()->json([
        'message' => 'Backend working, hopefully...'
    ]);
});
