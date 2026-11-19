<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pages, home CMS, blocks, services, locations, industries, programmatic landing pages, FAQs, internal links.
 */
return new class extends Migration
{
    private function publishing(Blueprint $table): void
    {
        $table->string('status', 20)->default('draft');
        $table->timestamp('published_at')->nullable();
        $table->string('preview_token', 64)->nullable()->unique();
        $table->timestamp('preview_expires_at')->nullable();
        $table->index(['status', 'published_at']);
    }

    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('title');
            $table->string('slug', 191)->unique();
            $table->string('template', 50)->default('default'); // default, about, contact, packages, legal, landing
            $table->string('h1')->nullable();
            $table->string('hero_layout', 30)->default('split'); // split, centered, minimal, image-bg
            $table->string('hero_subheading')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->foreignId('featured_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('show_in_search')->default(true);
            $this->publishing($table);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique(); // hero, services, about, stats, industries, technologies, process, portfolio, testimonials, blogs, faq, cta
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('eyebrow')->nullable();
            $table->json('content')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->string('heading');
            $table->string('subheading')->nullable();
            $table->text('description')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('cta_url')->nullable();
            $table->string('secondary_cta_text')->nullable();
            $table->string('secondary_cta_url')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('background_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('video_url')->nullable();
            $table->string('badge')->nullable();
            $table->string('trust_text')->nullable();
            $table->boolean('show_form')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('page_blocks', function (Blueprint $table) {
            $table->id();
            $table->morphs('blockable');
            $table->string('type', 50);
            $table->json('data')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->index(['blockable_type', 'blockable_id', 'sort_order']);
        });

        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 191)->unique();
            $table->string('menu_label')->nullable();
            $table->string('h1')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->foreignId('image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('accent', 20)->default('orange');
            $this->publishing($table);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('service_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug', 191)->unique();
            $table->string('menu_label')->nullable();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->string('hero_heading')->nullable();
            $table->text('hero_description')->nullable();
            $table->string('hero_layout', 30)->default('split');
            $table->foreignId('hero_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('featured_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('icon', 50)->nullable();
            $table->longText('content')->nullable();
            $table->json('benefits')->nullable();
            $table->json('features')->nullable();
            $table->json('process')->nullable();
            $table->json('technologies')->nullable();
            $table->json('pricing')->nullable();
            $table->string('cta_heading')->nullable();
            $table->text('cta_description')->nullable();
            $table->json('landing_template')->nullable(); // default copy used by programmatic page generator
            $table->boolean('is_featured')->default(false);
            $this->publishing($table);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['service_category_id', 'status', 'sort_order']);
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('name');
            $table->string('slug', 191)->unique();
            $table->string('type', 20)->default('city'); // city, state, country, region
            $table->string('state')->nullable();
            $table->string('country')->default('India');
            $table->text('description')->nullable();
            $table->foreignId('hero_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->json('business_info')->nullable(); // LocalBusiness overrides: opening hours, area served...
            $table->json('local_facts')->nullable(); // unique local copy (business districts, landmarks) to avoid thin pages
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->string('status', 20)->default('published')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['country', 'state']);
        });

        Schema::create('industries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 191)->unique();
            $table->string('plural_name')->nullable(); // "Real Estate Companies"
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->foreignId('image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->json('challenges')->nullable();
            $table->json('solutions')->nullable();
            $table->json('benefits')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->string('status', 20)->default('published')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('page_type', 40)->index(); // service_location, service_industry, service_location_industry
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('industry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug', 191)->unique();
            $table->string('h1')->nullable();
            $table->string('hero_layout', 30)->default('split');
            $table->string('hero_subheading')->nullable();
            $table->text('introduction')->nullable();
            $table->longText('content')->nullable();
            $table->json('sections')->nullable();
            $table->json('benefits')->nullable();
            $table->json('process')->nullable();
            $table->json('why_choose_us')->nullable();
            $table->json('cta')->nullable();
            $table->boolean('show_testimonials')->default(true);
            $table->boolean('show_industries')->default(true);
            $table->foreignId('featured_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('faq_enabled')->default(true);
            $table->string('faq_title')->nullable();
            $table->string('generated_by', 20)->default('manual'); // manual, generator, bulk
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $this->publishing($table);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['service_id', 'location_id', 'industry_id'], 'landing_combo_unique');
            $table->index(['page_type', 'status']);
        });

        Schema::create('faq_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 191)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faq_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('question', 500);
            $table->text('answer');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // The brief's "page_faqs": a polymorphic pivot so any page type can own FAQs.
        Schema::create('page_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faq_id')->constrained()->cascadeOnDelete();
            $table->morphs('faqable');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique(['faq_id', 'faqable_type', 'faqable_id']);
        });

        Schema::create('internal_links', function (Blueprint $table) {
            $table->id();
            $table->morphs('source');
            $table->nullableMorphs('target');
            $table->string('url', 500)->nullable();
            $table->string('anchor');
            $table->string('group', 30)->default('related'); // related, parent, child, resource
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['internal_links', 'page_faqs', 'faqs', 'faq_categories', 'landing_pages', 'industries', 'locations',
            'services', 'service_categories', 'page_blocks', 'hero_slides', 'home_sections', 'pages'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
