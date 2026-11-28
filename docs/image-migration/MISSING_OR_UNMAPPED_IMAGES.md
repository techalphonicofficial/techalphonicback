# Missing, Unmapped & Archived Old Website Images

**Generated:** 2026-11-28T06:37:42.375Z

Because this migration strictly preserves the new website's layout, UI components, and existing text content (**no new blog posts, portfolio rows, or UI sections added**), assets without an active matching content slot—or images with baked-in mismatched article headlines—were downloaded, validated, and archived locally under `public/assets/migrated-old-site/`.

## 1. Source HTTP 404 URLs (11 Total)

### 1.1 Recoverable Stale URL References (8 — 100% Recovered & Integrated)
The old WordPress homepage referenced stale `/wp-content/uploads/2026/09/` paths for 8 client logos, all of which returned HTTP 404 on the source server. However, all 8 identical logos existed at valid `/2026/05/` and `/2026/08/` URLs on the same WordPress media library and were downloaded and integrated as **Media IDs 7–14**:

- `ASSET-030` `https://seashell-stork-114708.hostingersite.com/wp-content/uploads/2026/09/Enlive.webp` -> **Recovered & Integrated** (see `AyurGrid`, `Lawgical`, `Kitchen Fix Solution`, `Desi Guru`, `Chardham Yatra`, `Services Plus`, `Com-Paint`, `Enlive`)
- `ASSET-031` `https://seashell-stork-114708.hostingersite.com/wp-content/uploads/2026/09/Com-Paint_Round_Logo.webp` -> **Recovered & Integrated** (see `AyurGrid`, `Lawgical`, `Kitchen Fix Solution`, `Desi Guru`, `Chardham Yatra`, `Services Plus`, `Com-Paint`, `Enlive`)
- `ASSET-032` `https://seashell-stork-114708.hostingersite.com/wp-content/uploads/2026/09/Services_Plus.webp` -> **Recovered & Integrated** (see `AyurGrid`, `Lawgical`, `Kitchen Fix Solution`, `Desi Guru`, `Chardham Yatra`, `Services Plus`, `Com-Paint`, `Enlive`)
- `ASSET-033` `https://seashell-stork-114708.hostingersite.com/wp-content/uploads/2026/09/ayurgrid.jpg` -> **Recovered & Integrated** (see `AyurGrid`, `Lawgical`, `Kitchen Fix Solution`, `Desi Guru`, `Chardham Yatra`, `Services Plus`, `Com-Paint`, `Enlive`)
- `ASSET-034` `https://seashell-stork-114708.hostingersite.com/wp-content/uploads/2026/09/kitchen-fix-solution-logo.png` -> **Recovered & Integrated** (see `AyurGrid`, `Lawgical`, `Kitchen Fix Solution`, `Desi Guru`, `Chardham Yatra`, `Services Plus`, `Com-Paint`, `Enlive`)
- `ASSET-035` `https://seashell-stork-114708.hostingersite.com/wp-content/uploads/2026/09/desi-guru.png` -> **Recovered & Integrated** (see `AyurGrid`, `Lawgical`, `Kitchen Fix Solution`, `Desi Guru`, `Chardham Yatra`, `Services Plus`, `Com-Paint`, `Enlive`)
- `ASSET-036` `https://seashell-stork-114708.hostingersite.com/wp-content/uploads/2026/09/logo-main.png` -> **Recovered & Integrated** (see `AyurGrid`, `Lawgical`, `Kitchen Fix Solution`, `Desi Guru`, `Chardham Yatra`, `Services Plus`, `Com-Paint`, `Enlive`)
- `ASSET-037` `https://seashell-stork-114708.hostingersite.com/wp-content/uploads/2026/09/lawgical.png` -> **Recovered & Integrated** (see `AyurGrid`, `Lawgical`, `Kitchen Fix Solution`, `Desi Guru`, `Chardham Yatra`, `Services Plus`, `Com-Paint`, `Enlive`)

### 1.2 Permanently Deleted Demo Theme Files (3 — Absent on Origin & Wayback Machine)
These 3 legacy WordPress URLs returned HTTP 404 on Hostinger and had no WayBack Machine archive:

- `ASSET-024` (`about-4.jpg`): `https://seashell-stork-114708.hostingersite.com/wp-content/uploads/2026/05/about-4.jpg`
- `ASSET-026` (`agency-img01-1.png`): `https://seashell-stork-114708.hostingersite.com/wp-content/uploads/2026/05/agency-img01-1.png`
- `ASSET-056` (`about-5.jpg`): `https://seashell-stork-114708.hostingersite.com/wp-content/uploads/2026/05/about-5.jpg`

## 2. Downloaded Images Intentionally Skipped After Visual Quality Inspection (2)

- `ASSET-046` (`AI_SEO_Services.jpg`, archived in `public/assets/migrated-old-site/services/`): Contained AI-garbled unreadable laptop screen text; replaced by clean `ASSET-047` (`Gemini_Generated_Image_wt21orwt21orwt21.webp`, Media ID 31).
- `ASSET-045` (`unnamed-16.png`, archived in `public/assets/migrated-old-site/case-studies/`): Contained garbled dashboard chart labels; replaced by `ASSET-111` (`Prove_Results1.webp`, Media ID 26).

## 3. Archived Old WordPress Blog Featured Banners (41)

The old WordPress site contains 45 blog posts whose featured images (`1600x900`) have specific 2025/2026 article headlines baked into the banner artwork (e.g., *"Top SEO Services in Noida 2025"*, *"Shopify Development Trends 2026"*). Assigning headline-baked banners to unrelated articles in the 3-post new CMS database would misrepresent content, and importing 45 old posts is outside image-migration scope. All **41** banners are preserved locally in `public/assets/migrated-old-site/blogs/`.

## 4. Archived Portfolio Captures & Screenshots (17)

In the new CMS database, `portfolios` currently has **0** rows (`/portfolio/` uses clean structural fallback cards without inventing portfolio case copy). All **17** old-site screenshots (`screencapture-*.webp`, `ta-project-*.jpg`, `kape-couture.png`, etc.) are preserved locally in `public/assets/migrated-old-site/portfolio/` ready for immediate CMS attachment when portfolio items are populated.

## 5. Archived Standalone Tech & Service SVG Icons (25)

The redesigned Next.js frontend uses its inline vector `<Icon>` component (`src/components/ui/Icon.tsx`) for UI and tech-stack badges. Replacing inline icon styling with raster/external SVG embeds would alter the design system, so all **25** SVG icons (`ASSET-115..134, 143`, etc.) are archived in `public/assets/migrated-old-site/icons/`.

## 6. Other Validated Unmapped Assets (22) & Excluded Plugin Placeholder (1)

- **Other archived service/background/404/logo variants (22):** Includes alternate banner crops, `404.webp` / `error.png`, `GLobal.png`, and alternate logos archived in `public/assets/migrated-old-site/{services,backgrounds,shared,logos,about}/`.
- **Excluded third-party Elementor placeholder (1):** `wp-content/plugins/elementor/assets/images/placeholder.png`.
