# Old-to-New Website Image Mapping

**Generated:** 2026-11-28T06:37:42.373Z  
**Architecture:** Dynamic CMS media library (`techalphonic.media`) served via Laravel `/api/v1/site/*` and Next.js `<Img>` component (`/storage/*` proxy + WebP/AVIF responsive `<picture>` sources).  
**UI/UX & Content Preservation:** Zero Next.js layout, JSX, CSS, or text modifications were made. Note that **Media IDs 15, 16, 17, 19, 20** (user-uploaded navbar/footer logos, favicon, hero portrait & hero MP4 video) were preserved intact.

## 1. Global Brand, SEO & Popup Settings

| Setting Key | CMS Media ID | Original Old Asset | Local Archival File | Storage URL | Target Usage |
| --- | ---: | --- | --- | --- | --- |
| `logo` | 15 | User-uploaded | Preserved upload | `/storage/uploads/2026/11/icon-abutwoj7.webp` | Navbar brand logo |
| `logo_dark` | 16 | User-uploaded | Preserved upload | `/storage/uploads/2026/11/icon-zk4hkkdy.webp` | Footer brand logo |
| `favicon` | 17 | User-uploaded | Preserved upload | `/storage/uploads/2026/11/favicon-gja6nedz.png` | Site favicon |
| `default_og_image` | 27 | `ASSET-001` (`tech-new-logo.png`) | `public/assets/migrated-old-site/logos/tech-new-logo.png` | `/storage/uploads/2026/11/tech-new-logo-hgnhasml.png` | Global OpenGraph fallback |
| `popup_image` | 25 | `ASSET-038` (`Ready_To_Grow_your_business1.webp`) | `public/assets/migrated-old-site/homepage/Ready_To_Grow_your_business1.webp` | `/storage/uploads/2026/11/ready-to-grow-your-business1-qiketzl0.webp` | Global quote/lead modal |

## 2. Homepage (`/`) Section-by-Section Mapping

| Homepage Section | Slot / Key | CMS Media ID | Old Asset | Dimensions | Storage URL | Notes |
| --- | --- | ---: | --- | --- | --- | --- |
| Hero Visual | `hero_slides[0].image_id` | 19 | User-uploaded | 980x551 | `/storage/uploads/2026/11/image-zunzoabq.jpg` | Preserved user image |
| Hero Fallback Banner | `hero_slides[0].background_id` | 21 | `ASSET-105` | 1600x646 | `/storage/uploads/2026/11/futuristic-ai-marketing-growth-banner-ydbgwkdf.png` | `Futuristic-AI-Marketing-Growth-Banner.png` |
| Hero & Showreel Video | `background_video_id` / `video_id` | 20 | User-uploaded MP4 | — | `/storage/uploads/2026/11/7504621-uhd-3840-2160-25fps-1-lrlezs75.mp4` | Real MP4 replacing old `.jpg` bug |
| Services Bento Card | `home_sections[services].image_id` | 22 | `ASSET-029` | 978x1609 | `/storage/uploads/2026/11/our-result-oriented-featuirs1-zqmbwaju.webp` | Replaced small 195x73 logo |
| Promo Card (SEO Starter) | `home_sections[promo].image_id` | 5 | `ASSET-013` | 1448x1086 | `/storage/uploads/2026/11/live-marketing-dashboard-miioebd4.webp` | Restored missing disk file & variants |
| About Us Section | `home_sections[about].image_id` | 23 | `ASSET-017` | 1524x1032 | `/storage/uploads/2026/11/ai-powered-digital-marketing-company1-yjgeumu7.webp` | Replaced Pexels stock `about-team.jpg` |
| Showreel Poster | `home_sections[showreel].image_id` | 24 | `ASSET-015` | 1600x600 | `/storage/uploads/2026/11/collaborative-analytics-team-workspace-7hhmpia3.png` | Collaborative team workspace |
| Valued Clients Marquee (8) | `home_sections[clients].items[*].logo_id` | 7–14 | `ASSET-003,004,005,006,016,057,059,060` | Various | `/storage/uploads/2026/11/*` | All 8 original logos restored on disk |

## 3. Standalone Pages, Case Studies & Categories

| Page / Record | Slug | CMS Media ID | Old Asset | Storage URL | Notes |
| --- | --- | ---: | --- | --- | --- |
| About Us | `/about-us/` | 25 | `ASSET-038` | `/storage/uploads/2026/11/ready-to-grow-your-business1-qiketzl0.webp` | Rendered in `PageHero` `aspect-[4/3]` |
| SEO by Industry | `/seo-by-industry/` | 26 | `ASSET-111` | `/storage/uploads/2026/11/prove-results1-mtfzx2hh.webp` | Rendered in `PageHero` `aspect-[4/3]` |
| Case Study #1 | `ayurveda-clinic-software-seo` | 26 | `ASSET-111` | `/storage/uploads/2026/11/prove-results1-mtfzx2hh.webp` | Replaced stretched 195x73 logo on `/` & `/case-studies/` |
| Category: Digital Marketing | `digital-marketing` | 29 | `ASSET-042` | `/storage/uploads/2026/11/25225467-7016019-2048x1575-1-p5cssgdm.webp` | Visual workflow illustration |
| Category: Development | `development` | 30 | `ASSET-141` | `/storage/uploads/2026/11/e7557188-2a1c-40c3-beb1-31ef1e8c4c85-pgvyurfm.png` | Intelligent IT & Dev illustration |

## 4. Services (All 20)

> Note: `ServicePage` passes `form={true}` to `PageHero`, so the split hero right column displays the conversion `<LeadForm>` while `hero_image_id` / `featured_image_id` populate CMS API payloads, OpenGraph `og:image` tags, and service cards.

| Service Name | Route | CMS Media ID | Old Asset | Confidence |
| --- | --- | ---: | --- | --- |
| Search Engine Optimization | `/search-engine-optimization/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | semantic_match |
| Local SEO | `/local-seo/` | 29 | `25225467_7016019-2048x1575-1.webp` (ASSET-042) | semantic_match |
| Ecommerce SEO | `/ecommerce-seo/` | 38 | `shot-of-a-young-businesswoman-using-a-digital-tabl-2022-10-06-03-37-11-utc.webp` (ASSET-039) | semantic_match |
| Technical SEO | `/technical-seo/` | 33 | `Untitled-3-1.webp` (ASSET-048) | exact |
| AI SEO Services | `/ai-seo-services/` | 31 | `Gemini_Generated_Image_wt21orwt21orwt21.webp` (ASSET-047) | exact |
| On-Page SEO | `/on-page-seo/` | 34 | `Untitled-6.webp` (ASSET-049) | exact |
| Off-Page SEO | `/off-page-seo-services/` | 35 | `Untitled-5-1-1.webp` (ASSET-040) | exact |
| App Store Optimization | `/app-store-seo/` | 36 | `Untitled-5.webp` (ASSET-025) | semantic_match |
| Content Marketing | `/content-marketing/` | 37 | `pexels-pixabay-262508-scaled.jpg` (ASSET-054) | exact |
| Social Media Marketing | `/social-media/` | 38 | `shot-of-a-young-businesswoman-using-a-digital-tabl-2022-10-06-03-37-11-utc.webp` (ASSET-039) | semantic_match |
| Pay Per Click Advertising | `/pay-per-click/` | 38 | `shot-of-a-young-businesswoman-using-a-digital-tabl-2022-10-06-03-37-11-utc.webp` (ASSET-039) | semantic_match |
| Branding & Identity | `/branding/` | 38 | `shot-of-a-young-businesswoman-using-a-digital-tabl-2022-10-06-03-37-11-utc.webp` (ASSET-039) | semantic_match |
| Website Development | `/website-development/` | 30 | `e7557188-2a1c-40c3-beb1-31ef1e8c4c85.png` (ASSET-141) | exact |
| Custom Web Development | `/custom-web-development/` | 30 | `e7557188-2a1c-40c3-beb1-31ef1e8c4c85.png` (ASSET-141) | exact |
| Laravel Development | `/laravel-development/` | 30 | `e7557188-2a1c-40c3-beb1-31ef1e8c4c85.png` (ASSET-141) | exact |
| WordPress Development | `/wordpress-development/` | 30 | `e7557188-2a1c-40c3-beb1-31ef1e8c4c85.png` (ASSET-141) | exact |
| React.js Development | `/react-js-development/` | 30 | `e7557188-2a1c-40c3-beb1-31ef1e8c4c85.png` (ASSET-141) | exact |
| Shopify Development | `/shopify-development/` | 30 | `e7557188-2a1c-40c3-beb1-31ef1e8c4c85.png` (ASSET-141) | exact |
| Mobile App Development | `/app-development/` | 30 | `e7557188-2a1c-40c3-beb1-31ef1e8c4c85.png` (ASSET-141) | exact |
| UI/UX Design | `/ui-ux/` | 30 | `e7557188-2a1c-40c3-beb1-31ef1e8c4c85.png` (ASSET-141) | exact |

## 5. Location & Industry Landing Pages (All 28)

| Landing Page | Route | CMS Media ID | Old Asset | Rationale |
| --- | --- | ---: | --- | --- |
| SEO Company in India | `/seo-company-in-india/` | 39 | `untitled-design-2-33cgdne3.png` (ASSET-147) | Dedicated India location graphic |
| SEO Company in Noida | `/seo-company-in-noida/` | 40 | `digital-marketing-growth-in-noida-wdripeqo.png` (ASSET-146) | Dedicated Noida location graphic |
| SEO for Moving Companies | `/seo-for-moving-company/` | 29 | `25225467_7016019-2048x1575-1.webp` (ASSET-042) | Industry SEO workflow graphic |
| SEO for Pest Control Companies | `/seo-for-pest-control-companies/` | 29 | `25225467_7016019-2048x1575-1.webp` (ASSET-042) | Industry SEO workflow graphic |
| Plumbing SEO Services | `/plumbing-seo-services/` | 29 | `25225467_7016019-2048x1575-1.webp` (ASSET-042) | Industry SEO workflow graphic |
| Hospitality SEO Services | `/hospitality-seo-services/` | 29 | `25225467_7016019-2048x1575-1.webp` (ASSET-042) | Industry SEO workflow graphic |
| SEO for Transport & Logistics Companies | `/seo-for-transport-and-logistics/` | 29 | `25225467_7016019-2048x1575-1.webp` (ASSET-042) | Industry SEO workflow graphic |
| Search Engine Optimization Company in Delhi | `/search-engine-optimization-company-in-delhi/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | Clean AI Overview & Entity SEO graphic |
| Search Engine Optimization Company in Greater Noida | `/search-engine-optimization-company-in-greater-noida/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | Clean AI Overview & Entity SEO graphic |
| Search Engine Optimization Company in Ghaziabad | `/search-engine-optimization-company-in-ghaziabad/` | 41 | `digital-marketing-company-in-ghaziabad-hamoktcr.png` (ASSET-148) | Dedicated Ghaziabad location graphic |
| Search Engine Optimization Company in Gurgaon | `/search-engine-optimization-company-in-gurgaon/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | Clean AI Overview & Entity SEO graphic |
| Search Engine Optimization Company in Faridabad | `/search-engine-optimization-company-in-faridabad/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | Clean AI Overview & Entity SEO graphic |
| Search Engine Optimization Company in Mumbai | `/search-engine-optimization-company-in-mumbai/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | Clean AI Overview & Entity SEO graphic |
| Search Engine Optimization Company in Bangalore | `/search-engine-optimization-company-in-bangalore/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | Clean AI Overview & Entity SEO graphic |
| Search Engine Optimization Company in Pune | `/search-engine-optimization-company-in-pune/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | Clean AI Overview & Entity SEO graphic |
| Search Engine Optimization Company in Hyderabad | `/search-engine-optimization-company-in-hyderabad/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | Clean AI Overview & Entity SEO graphic |
| Search Engine Optimization Company in Chennai | `/search-engine-optimization-company-in-chennai/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | Clean AI Overview & Entity SEO graphic |
| Search Engine Optimization Company in Kolkata | `/search-engine-optimization-company-in-kolkata/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | Clean AI Overview & Entity SEO graphic |
| Search Engine Optimization Company in Ahmedabad | `/search-engine-optimization-company-in-ahmedabad/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | Clean AI Overview & Entity SEO graphic |
| Search Engine Optimization Company in Jaipur | `/search-engine-optimization-company-in-jaipur/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | Clean AI Overview & Entity SEO graphic |
| Search Engine Optimization Company in Lucknow | `/search-engine-optimization-company-in-lucknow/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | Clean AI Overview & Entity SEO graphic |
| Search Engine Optimization Company in Chandigarh | `/search-engine-optimization-company-in-chandigarh/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | Clean AI Overview & Entity SEO graphic |
| Search Engine Optimization Company in Los Angeles | `/search-engine-optimization-company-in-los-angeles/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | Clean AI Overview & Entity SEO graphic |
| Search Engine Optimization Company in San Diego | `/search-engine-optimization-company-in-san-diego/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | Clean AI Overview & Entity SEO graphic |
| Search Engine Optimization Company in Chicago | `/search-engine-optimization-company-in-chicago/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | Clean AI Overview & Entity SEO graphic |
| Search Engine Optimization Company in Dallas | `/search-engine-optimization-company-in-dallas/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | Clean AI Overview & Entity SEO graphic |
| Search Engine Optimization Company in Australia | `/search-engine-optimization-company-in-australia/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | Clean AI Overview & Entity SEO graphic |
| Search Engine Optimization for Real Estate Companies in Noida | `/search-engine-optimization-for-real-estate-in-noida/` | 40 | `digital-marketing-growth-in-noida-wdripeqo.png` (ASSET-146) | Dedicated Noida location graphic |

## 6. Blogs (All 3) & Author Avatar

| Blog / Author | Route | CMS Media ID | Old Asset | Rationale |
| --- | --- | ---: | --- | --- |
| Local SEO Checklist for Small Businesses (2026 Edition) | `/blog/local-seo-checklist-for-small-businesses/` | 29 | `25225467_7016019-2048x1575-1.webp` (ASSET-042) | Clean text-free visual matching topic & 16:9 card crop |
| How to Choose a Web Development Company: 10 Questions to Ask | `/blog/how-to-choose-a-web-development-company/` | 30 | `e7557188-2a1c-40c3-beb1-31ef1e8c4c85.png` (ASSET-141) | Clean text-free visual matching topic & 16:9 card crop |
| Programmatic SEO Without Thin Content: A Practical Guide | `/blog/programmatic-seo-without-thin-content/` | 32 | `Gemini_Generated_Image_w6va0mw6va0mw6va.webp` (ASSET-028) | Clean text-free visual matching topic & 16:9 card crop |
| Author: Tech Alphonic Team | `/blog/*` | 28 | `cropped-tech-new-logo.png` (ASSET-014) | Official square brand emblem |
