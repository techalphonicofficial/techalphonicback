<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Settings, media, slug registry, redirects, SEO meta, schema templates, activity log.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->default('general');
            $table->string('key', 100);
            $table->longText('value')->nullable();
            $table->string('type', 20)->default('string'); // string, text, image, boolean, json, color
            $table->timestamps();
            $table->unique(['group', 'key']);
        });

        Schema::create('social_links', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 50);
            $table->string('url');
            $table->string('icon', 50)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 30)->default('public');
            $table->string('path');
            $table->string('filename');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt')->nullable();
            $table->string('title')->nullable();
            $table->string('caption')->nullable();
            $table->text('description')->nullable();
            $table->json('variants')->nullable(); // {"webp": "...", "sm": "...", "md": "...", "lg": "..."}
            $table->string('folder', 100)->nullable()->index();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('filename');
            $table->index('mime_type');
        });

        Schema::create('url_slugs', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 191)->unique();
            $table->string('routable_type', 100);
            $table->unsignedBigInteger('routable_id');
            $table->timestamps();
            $table->unique(['routable_type', 'routable_id']);
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('old_url', 191)->unique();
            $table->string('new_url', 500)->nullable();
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->string('source', 30)->default('manual'); // manual, slug_change, import
            $table->timestamps();
        });

        Schema::create('seo_meta', function (Blueprint $table) {
            $table->id();
            $table->morphs('seoable');
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('meta_keywords', 500)->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->boolean('robots_index')->default(true);
            $table->boolean('robots_follow')->default(true);
            $table->string('og_title')->nullable();
            $table->string('og_description', 500)->nullable();
            $table->foreignId('og_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('twitter_title')->nullable();
            $table->string('twitter_description', 500)->nullable();
            $table->foreignId('twitter_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('schema_type', 50)->nullable();
            $table->longText('schema_json')->nullable();
            $table->boolean('schema_enabled')->default(true);
            $table->string('focus_keyword')->nullable();
            $table->string('secondary_keywords', 500)->nullable();
            $table->boolean('include_in_sitemap')->default(true);
            $table->decimal('sitemap_priority', 2, 1)->nullable();
            $table->string('sitemap_changefreq', 10)->nullable();
            $table->timestamps();
            $table->unique(['seoable_type', 'seoable_id']);
            $table->index('robots_index');
        });

        Schema::create('schemas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 50);
            $table->longText('json_template');
            $table->string('applies_to', 50)->default('global'); // global, service, landing_page, blog, page, location
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['applies_to', 'is_active']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 50);
            $table->nullableMorphs('subject');
            $table->string('description')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('schemas');
        Schema::dropIfExists('seo_meta');
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('url_slugs');
        Schema::dropIfExists('media');
        Schema::dropIfExists('social_links');
        Schema::dropIfExists('settings');
    }
};
