<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_clients', function (Blueprint $table) {
            $table->string('client_id')->primary();
            $table->string('client_secret')->nullable();
            $table->json('redirect_uris');
            $table->string('client_name')->nullable();
            $table->json('grant_types');
            $table->json('response_types');
            $table->string('scope')->nullable();
            $table->string('token_endpoint_auth_method')->nullable();
            $table->unsignedInteger('client_id_issued_at')->nullable();
        });

        Schema::create('oauth_codes', function (Blueprint $table) {
            $table->string('code')->primary();
            $table->string('client_id')->index();
            $table->text('redirect_uri');
            $table->string('code_challenge');
            $table->json('scopes');
            $table->text('resource')->nullable();
            $table->text('laravel_token');
            $table->string('username');
            $table->unsignedInteger('expires_at')->index();
        });

        Schema::create('oauth_tokens', function (Blueprint $table) {
            $table->string('token')->primary();
            $table->string('token_type');
            $table->string('client_id')->index();
            $table->json('scopes');
            $table->text('resource')->nullable();
            $table->text('laravel_token');
            $table->string('username');
            $table->string('refresh_token')->nullable()->index();
            $table->unsignedInteger('expires_at')->index();
            $table->boolean('revoked')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_tokens');
        Schema::dropIfExists('oauth_codes');
        Schema::dropIfExists('oauth_clients');
    }
};
