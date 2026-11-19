<?php

namespace App\Support;

/**
 * Everything the <head> needs for one page. Built by SeoService, rendered by <x-seo-meta>.
 */
class SeoData
{
    public function __construct(
        public string $title,
        public ?string $description = null,
        public ?string $keywords = null,
        public ?string $canonical = null,
        public string $robots = 'index, follow',
        public ?string $ogTitle = null,
        public ?string $ogDescription = null,
        public ?string $ogImage = null,
        public string $ogType = 'website',
        public ?string $twitterTitle = null,
        public ?string $twitterDescription = null,
        public ?string $twitterImage = null,
        public ?string $prevUrl = null,
        public ?string $nextUrl = null,
        public ?string $publishedTime = null,
        public ?string $modifiedTime = null,
        /** @var array<int, array{name: string, url: string|null}> */
        public array $breadcrumbs = [],
        /** @var array<int, array> JSON-LD graph nodes */
        public array $schema = [],
        /** @var array<string, string> hreflang => url (architecture-ready, empty by default) */
        public array $alternates = [],
    ) {}

    public function isIndexable(): bool
    {
        return str_starts_with($this->robots, 'index');
    }
}
