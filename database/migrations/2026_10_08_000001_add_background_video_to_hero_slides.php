<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            // MP4 from the media library played muted/looped behind the hero (background_id is its poster).
            $table->foreignId('background_video_id')->nullable()->after('background_id')->constrained('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            $table->dropConstrainedForeignId('background_video_id');
        });
    }
};
