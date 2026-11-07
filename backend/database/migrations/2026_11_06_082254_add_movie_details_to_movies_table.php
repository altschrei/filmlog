<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->json('genres')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->string('duration')->nullable();
            $table->text('synopsis')->nullable();
            $table->json('cast')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn([
                'genres',
                'rating',
                'duration',
                'synopsis',
                'cast',
            ]);
        });
    }
};
