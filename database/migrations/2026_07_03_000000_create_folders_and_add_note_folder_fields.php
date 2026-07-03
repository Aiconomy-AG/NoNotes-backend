<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('folders')->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'parent_id', 'sort_order']);
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->foreignId('folder_id')->nullable()->after('user_id')->constrained('folders')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0)->after('folder_id');
            $table->index(['user_id', 'folder_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('folder_id');
            $table->dropColumn('sort_order');
        });

        Schema::dropIfExists('folders');
    }
};
