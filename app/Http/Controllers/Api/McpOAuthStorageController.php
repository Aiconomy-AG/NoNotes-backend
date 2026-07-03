<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OAuthClient;
use App\Models\OAuthCode;
use App\Models\OAuthToken;
use Illuminate\Http\Request;

class McpOAuthStorageController extends Controller
{
    private function authorizeStorageRequest(Request $request): void
    {
        $secret = (string) config('services.mcp.storage_secret');

        abort_if($secret === '', 503, 'MCP storage secret is not configured.');
        abort_unless(hash_equals($secret, (string) $request->header('X-MCP-Storage-Secret')), 403);
    }

    public function getClient(Request $request, string $clientId)
    {
        $this->authorizeStorageRequest($request);

        return OAuthClient::find($clientId) ?? response()->json(null, 404);
    }

    public function storeClient(Request $request)
    {
        $this->authorizeStorageRequest($request);

        $data = $request->validate([
            'client_id' => ['required', 'string'],
            'client_secret' => ['nullable', 'string'],
            'redirect_uris' => ['required', 'string'],
            'client_name' => ['nullable', 'string'],
            'grant_types' => ['required', 'string'],
            'response_types' => ['required', 'string'],
            'scope' => ['nullable', 'string'],
            'token_endpoint_auth_method' => ['nullable', 'string'],
            'client_id_issued_at' => ['nullable', 'integer'],
        ]);

        OAuthClient::query()->updateOrCreate(['client_id' => $data['client_id']], $data);

        return response()->noContent(201);
    }

    public function getCode(Request $request, string $code)
    {
        $this->authorizeStorageRequest($request);

        return OAuthCode::find($code) ?? response()->json(null, 404);
    }

    public function storeCode(Request $request)
    {
        $this->authorizeStorageRequest($request);

        $data = $request->validate([
            'code' => ['required', 'string'],
            'client_id' => ['required', 'string'],
            'redirect_uri' => ['required', 'string'],
            'code_challenge' => ['required', 'string'],
            'scopes' => ['required', 'string'],
            'resource' => ['nullable', 'string'],
            'laravel_token' => ['required', 'string'],
            'username' => ['required', 'string'],
            'expires_at' => ['required', 'integer'],
        ]);

        OAuthCode::query()->updateOrCreate(['code' => $data['code']], $data);

        return response()->noContent(201);
    }

    public function deleteCode(Request $request, string $code)
    {
        $this->authorizeStorageRequest($request);

        OAuthCode::query()->whereKey($code)->delete();

        return response()->noContent();
    }

    public function getToken(Request $request, string $token)
    {
        $this->authorizeStorageRequest($request);

        return OAuthToken::find($token) ?? response()->json(null, 404);
    }

    public function getTokenByRefresh(Request $request, string $refreshToken)
    {
        $this->authorizeStorageRequest($request);

        return OAuthToken::query()
            ->where('token', $refreshToken)
            ->where('token_type', 'refresh')
            ->first() ?? response()->json(null, 404);
    }

    public function storeToken(Request $request)
    {
        $this->authorizeStorageRequest($request);

        $data = $request->validate([
            'token' => ['required', 'string'],
            'token_type' => ['required', 'in:access,refresh'],
            'client_id' => ['required', 'string'],
            'scopes' => ['required', 'string'],
            'resource' => ['nullable', 'string'],
            'laravel_token' => ['required', 'string'],
            'username' => ['required', 'string'],
            'refresh_token' => ['nullable', 'string'],
            'expires_at' => ['required', 'integer'],
            'revoked' => ['required', 'boolean'],
        ]);

        OAuthToken::query()->updateOrCreate(['token' => $data['token']], $data);

        return response()->noContent(201);
    }

    public function revokeToken(Request $request, string $token)
    {
        $this->authorizeStorageRequest($request);

        OAuthToken::query()->whereKey($token)->update(['revoked' => true]);

        return response()->noContent();
    }

    public function cleanupExpired(Request $request)
    {
        $this->authorizeStorageRequest($request);

        $data = $request->validate([
            'now' => ['required', 'integer'],
        ]);

        OAuthCode::query()->where('expires_at', '<', $data['now'])->delete();
        OAuthToken::query()->where('expires_at', '<', $data['now'])->delete();

        return response()->noContent();
    }
}
