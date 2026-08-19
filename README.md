# News Portal 2

A full-featured, multilingual news portal built with Laravel 13 and Vue.js 3 (Inertia.js). Includes a complete back-office admin panel, public-facing news site with localization (English/Bangla), RSS/Atom feeds, XML sitemaps, quizzes, surveys, Google Ad Manager integration, and a comprehensive caching system.

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [Technology Stack](#technology-stack)
- [System Requirements](#system-requirements)
- [Architecture](#architecture)
- [Project Structure](#project-structure)
- [Application Workflow](#application-workflow)
- [Core Models](#core-models)
- [Middleware](#middleware)
- [Form Requests & Validation](#form-requests--validation)
- [Jobs & Queue System](#jobs--queue-system)
- [Events & Listeners](#events--listeners)
- [Observers](#observers)
- [Policies & Authorization](#policies--authorization)
- [Services](#services)
- [Helpers](#helpers)
- [Database](#database)
- [Backend Dependencies](#backend-dependencies)
- [Frontend Dependencies](#frontend-dependencies)
- [Environment Configuration](#environment-configuration)
- [Installation](#installation)
  - [Local Development](#local-development)
  - [cPanel Deployment](#cpanel-deployment)
  - [Nginx Deployment](#nginx-deployment)
- [Database Commands](#database-commands)
- [Frontend Commands](#frontend-commands)
- [Artisan Commands](#artisan-commands)
- [Queue Worker](#queue-worker)
- [Production Optimization](#production-optimization)
- [Troubleshooting](#troubleshooting)

## Overview

News Portal 2 is a production-grade news content management system. It provides:

- A **public-facing news portal** with full localization support (English and Bangla), serving pages via Inertia.js + Vue.js 3
- A **back-office admin panel** for managing all content entities (news, categories, tags, locations, events, contributors, pages, menus, quizzes, surveys, advertisements, themes, and users)
- **RSS/Atom feeds** and **XML sitemaps** for SEO
- A **quiz and survey system** with participant tracking
- **Google Ad Manager** integration with configurable ad placements
- **Activity logging** for audit trails
- **Queue-based background processing** for media sync, cache invalidation, and cascade deletes
- A **multi-tier caching system** with tag-based invalidation

The frontend is a Vue.js 3 SPA served through Inertia.js, styled with Tailwind CSS v4, and using Ziggy for Laravel route integration in JavaScript.

## Key Features

- **Multilingual content** -- English and Bangla with language switching on the public site
- **News management** -- Story, Video, and Image Gallery news types with rich text editing (TinyMCE)
- **Hierarchical categories and locations** -- Nested tree structures with breadcrumb navigation
- **Tag and trend management** -- Tag news articles and manage trending topics
- **Contributor management** -- Author profiles with media support
- **Event management** -- Date-range events with banner images
- **Breaking news** -- Time-sensitive breaking news ticker
- **Page management** -- CMS pages with configurable default pages (Home, Latest, Search)
- **Menu system** -- Configurable menus (Header, Topbar, Off-canvas, Footer) with polymorphic menu items
- **Theme system** -- Runtime-configurable themes with dynamic option types
- **Quiz system** -- Multi-question quizzes with single/multiple answers, scoring, participants, and results
- **Survey system** -- Survey questions with yes/no/no-comment voting and result tracking
- **Google Ad Manager** -- Configurable ad slots with page, type, and placement settings
- **Media library** -- Image uploads with Spatie Media Library, gallery management, content media sync
- **News placement** -- Control which news articles appear on specific page sections
- **Activity logging** -- Spatie Activity Log for audit trails on all major entities
- **Queue monitoring** -- Web-based queue monitor dashboard
- **Log viewer** -- Web-based application log viewer
- **SEO** -- XML sitemaps (entity-specific and paginated), RSS/Atom feeds, SEO metadata on all entities
- **Role-based permissions** -- Module-level permission system with super admin override
- **Response caching** -- HTTP cache-control middleware with ETag support and stale-while-revalidate
- **API caching** -- Two-tier frontend cache (in-memory + localStorage) with TTL
- **Slug-based routing** -- All public URLs use human-readable slugs
- **Social sharing** -- Social media sharing buttons on news articles
- **Leaflet maps** -- Map integration for location-based content

## Technology Stack

### Backend

| Technology | Version | Purpose |
|---|---|---|
| Laravel | 13.x | Backend framework |
| PHP | 8.3+ | Runtime |
| SQLite | -- | Default database (MySQL supported) |
| Composer | -- | PHP dependency management |

### Frontend

| Technology | Version | Purpose |
|---|---|---|
| Vue.js | 3.5 | Frontend framework (Composition API) |
| Inertia.js | 3.x | SPA router (server-driven) |
| Vite | 8.0 | Build tool and dev server |
| Tailwind CSS | 4.2 | Utility-first CSS |
| TinyMCE | 8.4 | Rich text editor |
| Ziggy | 2.6 | Laravel named routes in JavaScript |
| vue-i18n | 11.4 | Internationalization (en/bn) |
| FontAwesome | 7.2 | Icons |
| Swiper | 12.2 | Carousels and sliders |
| Leaflet | 1.9 | Interactive maps |
| Axios | 1.15 | HTTP client |

## System Requirements

- **PHP** >= 8.3
- **Composer** (latest)
- **Node.js** >= 18.x
- **npm** (latest)
- **SQLite** (default) or **MySQL** 5.7+ / **MariaDB** 10.3+
- **Redis** (optional, for cache/queue in production)
- **Web server**: Apache with mod_rewrite, Nginx, or PHP built-in server (development)

### Required PHP Extensions

- BCMath
- Ctype
- cURL
- DOM
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PCRE
- PDO
- Session
- Tokenizer
- XML

## Architecture

The application follows a layered architecture:

```
Route
  |
  +--> Middleware (auth, verified, response.cache, etc.)
  |
  +--> Controller (thin, delegates to services)
  |
  +--> Form Request (validation)
  |
  +--> Service (business logic)
  |
  +--> Model (Eloquent ORM)
  |
  +--> Database
```

### Key Architectural Patterns

- **Service Layer**: Every controller delegates to a dedicated service class. Back-office CRUD operations use `BackOffice\*Service` classes. Public pages use `PageService`, `SiteService`, `FeedService`, `SitemapService`, `SearchService`.
- **Observer Pattern**: Eloquent models use Observers for lifecycle hooks (creating, updating, deleting). Observers manage tree data updates and cascade delete job dispatching.
- **Queue-based Processing**: Delete cascades and media synchronization run through queued jobs. News sync jobs (sitemap/feed) run asynchronously; delete jobs run synchronously via `dispatchSync()`.
- **Policy-based Authorization**: All back-office operations are gated through Laravel Policies that check module-level permissions. Super admins bypass all checks.
- **Multi-tier Caching**: Backend uses `CacheHelper` + `CacheServerHelper` for deterministic cache key generation and tag-based invalidation. Frontend uses a custom `useApiCache` composable with in-memory + localStorage caching.
- **Localization**: Every public-facing controller action has a `localized*` counterpart. Routes support both default-language and localized URL patterns (`/{languageCode}/...`).
- **Inertia.js**: The frontend is a Vue.js 3 SPA without a client-side router. Inertia.js handles server-driven page rendering, with controllers returning Inertia responses or JSON for AJAX requests.

## Project Structure

```
news-portal-2/
|-- app/
|   |-- Actions/
|   |   `-- Sluggable/                    # Custom Unicode slug generation
|   |-- Events/
|   |   `-- MediaUpdatedEvent.php         # Media update event
|   |-- Helpers/                          # 20 helper classes
|   |   |-- ActivityLogHelper.php
|   |   |-- ArtisanCommandHelper.php
|   |   |-- CacheHelper.php               # Cache key generation (core)
|   |   |-- CacheServerHelper.php         # Cache operations abstraction
|   |   |-- DatatableHelper.php
|   |   |-- EventHelper.php
|   |   |-- GoogleAdHelper.php
|   |   |-- MediaHelper.php
|   |   |-- MenuHelper.php
|   |   |-- NewsHelper.php
|   |   |-- PageHelper.php
|   |   |-- QuizHelper.php
|   |   |-- ReportHelper.php
|   |   |-- SeederHelper.php
|   |   |-- SessionHelper.php
|   |   |-- SystemHelper.php
|   |   |-- TagifyHelper.php
|   |   |-- ThemeHelper.php
|   |   |-- UserHelper.php
|   |   `-- UserPermissionHelper.php
|   |-- Http/
|   |   |-- Controllers/
|   |   |   |-- AuthController.php
|   |   |   |-- FeedController.php
|   |   |   |-- PageController.php
|   |   |   |-- SearchController.php
|   |   |   |-- SiteController.php
|   |   |   |-- SitemapController.php
|   |   |   `-- BackOffice/               # 19 admin controllers
|   |   |-- Middleware/
|   |   |   |-- FeedResponse.php
|   |   |   |-- HandleInertiaRequests.php
|   |   |   |-- IsSuperAdmin.php
|   |   |   |-- ResponseCache.php
|   |   |   `-- XmlResponse.php
|   |   `-- Requests/                     # 29 form request classes
|   |-- Jobs/                             # 30 queued jobs
|   |-- Listeners/                        # 3 event listeners
|   |-- Models/                           # 28 Eloquent models
|   |-- Observers/                        # 21 model observers
|   |-- Policies/                         # 20 authorization policies
|   |-- Providers/
|   |   |-- AppServiceProvider.php
|   |   |-- EventServiceProvider.php
|   |   `-- ThemeProvider.php
|   |-- Services/
|   |   |-- AuthService.php
|   |   |-- DashboardService.php
|   |   |-- FeedService.php
|   |   |-- PageService.php
|   |   |-- SearchService.php
|   |   |-- SitemapService.php
|   |   |-- SiteService.php
|   |   |-- BackOffice/                   # 23 back-office services
|   |   `-- Cache/                        # 14 cache services
|   `-- View/
|       `-- Components/                   # Blade view components (Feeds, Sitemaps)
|-- config/                               # 16 configuration files
|-- database/
|   |-- migrations/                       # 48 migrations
|   |-- seeders/                          # 35 seeders
|   `-- factories/                        # 26 model factories
|-- public/
|-- resources/
|   |-- css/app.css                       # Tailwind CSS + design tokens
|   |-- js/
|   |   |-- app.js                        # Inertia + Vue 3 bootstrap
|   |   |-- bootstrap.js                  # Axios setup
|   |   |-- i18n/                         # vue-i18n (en, bn locales)
|   |   |-- components/                   # ~70 reusable Vue components
|   |   |-- composables/                  # 15 Vue composables
|   |   `-- pages/                        # ~71 Inertia page components
|   `-- views/                            # Blade templates (feeds, sitemaps, app shell)
|-- routes/
|   |-- web.php
|   `-- console.php
|-- composer.json
|-- package.json
`-- vite.config.js
```

## Application Workflow

### Public Site Request Flow

```
Browser Request
  |
  v
Laravel Route (web.php)
  |
  v
Middleware Stack
  |-- HandleInertiaRequests (share auth data, flash messages)
  |-- ResponseCache (HTTP cache headers, ETag)
  |-- FeedResponse / XmlResponse (content-type for feeds/sitemaps)
  |
  v
Controller (PageController / FeedController / SitemapController)
  |
  v
Service (PageService / FeedService / SitemapService)
  |
  v
CacheService (check/generate cached data)
  |-- CacheHelper (key generation)
  |-- CacheServerHelper (cache read/write)
  |
  v
Eloquent Model (query database)
  |
  v
Inertia Response -> Vue.js Page Component
  (or JSON response for AJAX requests)
```

### Back-Office Request Flow

```
Admin Request
  |
  v
Laravel Route (web.php, back-office prefix)
  |
  v
Middleware: auth + verified
  |
  v
Controller (BackOffice\*Controller)
  |-- Gate::authorize() (Policy check)
  |
  v
Form Request (validation)
  |
  v
BackOffice Service (business logic)
  |
  v
Eloquent Model
  |
  v
Observer (lifecycle hooks)
  |-- creating/updating: tree data management
  |-- deleting: dispatch Delete*RelationsJob (sync)
  |-- created/updated: dispatch sync jobs (async)
  |
  v
Inertia Response -> Vue.js Back-Office Page
```

### Asynchronous Job Flow

```
Controller / Observer
  |
  v
dispatch() or dispatchSync()
  |
  v
Queue (database driver)
  |
  v
Queue Worker (php artisan queue:work)
  |
  v
Job Execution
  |-- Sync jobs: NewsSyncSitemapJob, NewsSyncFeedJob, etc.
  |-- Delete jobs: Delete*RelationsJob (cascade cleanup)
  |-- Media jobs: NewsContentMediaSyncJob, NewsGalleryImagesSyncJob
  |
  v
Database / Cache / External Service
```

## Core Models

### News

The central content model. Represents news articles in Story, Video, or Image Gallery formats.

**Table:** `news`

**Relationships:**
- `category()` -- BelongsTo Category
- `language()` -- BelongsTo Language
- `newsType()` -- BelongsTo NewsType
- `event()` -- BelongsTo Event
- `location()` -- BelongsTo Location
- `contributors()` -- BelongsToMany Contributor (pivot: `contributor_news`)
- `tags()` -- BelongsToMany Tag (pivot: `news_tag`)
- `relatedNews()` -- BelongsToMany News (self-referential, pivot: `news_related_news`)
- `relevantNews()` -- BelongsToMany News (self-referential, pivot: `news_relevant_news`)
- `newsPlacements()` -- HasMany NewsPlacement
- `breakingNews()` -- HasOne BreakingNews
- `featureImage()` -- MorphOne Media (role: `news_feature_image`)
- `featureImageMobile()` -- MorphOne Media (role: `news_feature_image_mobile`)
- `galleryImages()` -- MorphMany Media (role: `news_gallery_image`)
- `activityLogs()` -- MorphMany Activity

**Notable:** Implements `HasMedia` (Spatie Media Library). Supports soft-deletes. Uses `Hit count` tracking.

### Category

Hierarchical content categorization with nested tree support.

**Table:** `categories`

**Relationships:**
- `parent()` -- BelongsTo Category (self-referential)
- `children()` -- via `HasRecursiveRelationships` (staudenmeir/laravel-adjacency-list)
- `language()` -- BelongsTo Language
- `news()` -- HasMany News
- `locations()` -- HasMany Location

**Notable:** Maintains `slug_tree` and `name_tree` columns for hierarchical path resolution.

### Location

Geospatial content tagging with hierarchical tree support and map boundary data.

**Table:** `locations`

**Relationships:**
- `parent()` -- BelongsTo Location (self-referential)
- `category()` -- BelongsTo Category
- `language()` -- BelongsTo Language
- `news()` -- HasMany News

**Notable:** Stores `latitude`, `longitude`, `boundary_geojson`, and boundary coordinate fields for map integration.

### Tag

Content tagging with SEO metadata.

**Table:** `tags`

**Relationships:**
- `news()` -- HasMany News (pivot: `news_tag`)
- `trend()` -- HasOne Trend
- `language()` -- BelongsTo Language

### Event

Date-range events with banner images.

**Table:** `events`

**Relationships:**
- `news()` -- HasMany News
- `language()` -- BelongsTo Language
- `desktopBannerImage()` -- MorphOne Media
- `mobileBannerImage()` -- MorphOne Media

**Notable:** Supports `start_date`, `end_date`, `is_active`, and `position` (top/bottom).

### Contributor

Author/journalist profiles with media support.

**Table:** `contributors`

**Relationships:**
- `news()` -- HasMany News (pivot: `contributor_news`)
- `profileImage()` -- MorphOne Media
- `language()` -- BelongsTo Language

### Language

Localization configuration.

**Table:** `languages`

**Relationships:**
- `categories()`, `contributors()`, `events()`, `locations()`, `news()`, `tags()` -- HasMany respective models

**Notable:** One language is marked as `is_default`.

### NewsPlacement

Controls which news articles appear on specific page sections.

**Table:** `news_placements`

**Relationships:**
- `news()` -- BelongsTo News
- `category()` -- BelongsTo Category

**Notable:** Links news to page sections (Home Lead News, Home Category News, Category Lead News).

### Page

CMS pages with hierarchical support and configurable default page mapping.

**Table:** `pages`

**Relationships:**
- `parent()` -- BelongsTo Page (self-referential)
- `language()` -- BelongsTo Language

**Notable:** `default_use_as` field maps pages to Home, Latest, or Search. Maintains `slug_tree` and `title_tree`.

### Menu / MenuItem / MenuType

Configurable navigation menus with polymorphic menu items.

**Menu table:** `menus`
**MenuItem table:** `menu_items`
**MenuType table:** `menu_types`

**MenuItem Relationships:**
- `menu()` -- BelongsTo Menu
- `model()` -- MorphTo (can link to Category, Tag, Page, etc.)
- `parent()` -- BelongsTo MenuItem (self-referential)

**Notable:** MenuItem supports both custom URLs and model-backed URLs via polymorphic `model_type`/`model_id`.

### Theme

Runtime-configurable themes with dynamic option types.

**Table:** `themes`

**Notable:** `options` field is JSON-encoded, storing key-value pairs with typed values (text, boolean, integer, color, URL, image, etc.).

### BreakingNews

Time-sensitive breaking news entries linked to news articles.

**Table:** `breaking_news`

**Relationships:**
- `news()` -- BelongsTo News
- `language()` -- BelongsTo Language

### Quiz / QuizQuestion / QuizQuestionOption / QuizParticipant / QuizResult

Complete quiz system with questions, options, scoring, and participant tracking.

**Quiz table:** `quizzes`
**QuizQuestion table:** `quiz_questions`
**QuizQuestionOption table:** `quiz_question_options`
**QuizParticipant table:** `quiz_participants`
**QuizResult table:** `quiz_results`

**Notable:** Supports single/multiple answer types, weighted scoring, date-range availability, result display control, and max winner limits.

### Survey / SurveyQuestion / SurveyQuestionResult

Survey system with yes/no/no-comment voting.

**Survey table:** `surveys`
**SurveyQuestion table:** `survey_questions`
**SurveyQuestionResult table:** `survey_question_results`

### User / UserPermission

User management with module-level permission system.

**Users table:** `users`
**UserPermissions table:** `user_permissions`
**Pivot:** `user_user_permission`

**Notable:** Users use `SoftDeletes`. The `is_super_admin` flag bypasses all permission checks. Email verification is required. Permissions are organized by module (20 modules) and access level (View Any, View, Create, Update, Delete, Restore, Force Delete).

### Other Models

- **NewsType** -- Lookup model for news content types (Story, Video, Image Gallery)
- **GoogleAd** -- Google Ad Manager slot configuration
- **Trend** -- Trending topic linked to a Tag
- **MediaUpload** -- Simple media storage entity

## Middleware

| Middleware | Purpose |
|---|---|
| `HandleInertiaRequests` | Inertia.js root middleware. Shares auth user data and flash messages with all pages. Manages asset versioning. |
| `IsSuperAdmin` | Authorization middleware. Restricts access to super admin users only. Used for activity logs, queue monitor, and settings. |
| `ResponseCache` | HTTP cache-control middleware. Adds `Cache-Control` headers with `stale-while-revalidate`, optional ETag generation, and force-refresh cookie support. Automatically sets `private` for authenticated users. |
| `FeedResponse` | Sets correct XML Content-Type header for RSS (`application/rss+xml`) or Atom (`application/atom+xml`) feeds. |
| `XmlResponse` | Sets Content-Type to `application/xml; charset=UTF-8` for sitemap responses. |

## Form Requests & Validation

The application uses 29 dedicated Form Request classes for centralized validation:

| Request | Validates |
|---|---|
| `NewsRequest` | News article creation/update (title, body, category, tags, contributors, feature images, SEO, etc.) |
| `CategoryRequest` | Category with hierarchical parent validation |
| `TagRequest` | Tag with language and SEO fields |
| `LocationRequest` | Location with geospatial data (lat/lng, boundary) |
| `EventRequest` | Event with date ranges and banner images |
| `ContributorRequest` | Contributor with profile image |
| `PageRequest` | CMS page with default page mapping |
| `MenuRequest` / `MenuItemRequest` | Menu and menu item with polymorphic model validation |
| `NewsGalleryImageRequest` | Gallery image upload |
| `NewsGalleryImageSequenceUpdateRequest` | Gallery image reorder sequence |
| `BreakingNewsRequest` | Breaking news with time-based uniqueness |
| `UserRequest` | Admin user with permissions and profile image |
| `ThemeRequest` | Theme with typed option validation |
| `GoogleAdRequest` | Google Ad with page/type/placement logic |
| `QuizRequest` / `QuizQuestionRequest` / `QuizQuestionOptionRequest` | Quiz with nested questions and options |
| `QuizSubmitRequest` | Public quiz submission with answer validation |
| `SurveyRequest` / `SurveyQuestionRequest` | Survey with nested questions |
| `LoginRequest` / `RegisterRequest` | Authentication |
| `ForgotPasswordRequest` / `ResetPasswordRequest` | Password reset |
| `AuthUserProfileRequest` / `AuthUserAccountRequest` | Profile and account updates |
| `MediaQuickRequest` | Quick media upload |

## Jobs & Queue System

The application uses 30 queued jobs for background processing. All jobs implement `ShouldQueue` and `ShouldBeUnique`, and use `romanzipp\QueueMonitor` for monitoring.

### News Sync Jobs (dispatched asynchronously)

| Job | Purpose |
|---|---|
| `SyncLatestNewsSitemapJob` | Refreshes cached latest news for sitemaps |
| `SyncLatestNewsFeedJob` | Refreshes cached latest news for RSS/Atom feeds |
| `NewsTagSyncJob` | Synchronizes news-tag many-to-many relationship |
| `NewsContributorSyncJob` | Synchronizes news-contributor many-to-many relationship |
| `NewsRelatedNewsSyncJob` | Synchronizes related news self-referential pivot |
| `NewsRelevantNewsSyncJob` | Synchronizes relevant news self-referential pivot |
| `NewsGalleryImagesSyncJob` | Copies/updates gallery images to news media collection |
| `NewsContentMediaSyncJob` | Copies editor media and replaces URLs in news body HTML |
| `NewsBreakingNewsSyncJob` | Links/unlinks BreakingNews to/from News |
| `NewsNewsPlacementAfterCreateSyncJob` | Auto-creates NewsPlacement records after news creation |

### Delete Cascade Jobs (dispatched synchronously from Observers)

| Job | Cleans Up |
|---|---|
| `DeleteNewsRelationsJob` | Activity logs, media, tags, contributors, relevant/related news, breaking news |
| `DeleteCategoryRelationsJob` | Activity logs, locations, news |
| `DeleteLocationRelationsJob` | Activity logs, news |
| `DeleteTagRelationsJob` | Activity logs, trend, news |
| `DeleteEventRelationsJob` | Activity logs, media, news |
| `DeleteContributorRelationsJob` | Activity logs, news |
| `DeleteLanguageRelationsJob` | Activity logs, categories, contributors, tags, locations, events, news |
| `DeleteUserRelationsJob` | Activity logs, media |
| `DeletePageRelationsJob` | Activity logs |
| `DeleteMenuRelationsJob` | Activity logs, menu items |
| `DeleteMenuItemRelationsJob` | Activity logs |
| `DeleteThemeRelationsJob` | Activity logs |
| `DeleteTrendRelationsJob` | Activity logs |
| `DeleteBreakingNewsRelationsJob` | Activity logs |
| `DeleteGoogleAdRelationsJob` | Activity logs |
| `DeleteSurveyRelationsJob` | Activity logs, survey questions |
| `DeleteSurveyQuestionRelationsJob` | Activity logs, survey question results |
| `DeleteQuizRelationsJob` | Activity logs, quiz questions |
| `DeleteQuizQuestionRelationsJob` | Activity logs, quiz question options |
| `DeleteQuizQuestionOptionRelationsJob` | Activity logs |

### Queue Configuration

- **Default driver:** `database`
- **Queue table:** `jobs`
- **Failed jobs table:** `failed_jobs`
- **Retry after:** 60 seconds
- **Backoff:** [61, 123, 185] seconds
- **Fail limit:** 3 attempts

```
Application Action
        |
        v
Observer / Controller
        |
        v
dispatch() / dispatchSync()
        |
        v
Queue (database)
        |
        v
Queue Worker
        |
        v
Job Execution (DB transaction)
        |
        v
Database / Cache / External Service
```

## Events & Listeners

### Registered Events

| Event | Listener | Purpose |
|---|---|---|
| `MediaHasBeenAddedEvent` (Spatie) | `MediaCreatedListener` | Sets `created_by_id` and generates slug for new media, logs activity |
| `MediaUpdatedEvent` | `MediaUpdatedListener` | Logs media update activity with detailed properties |
| `CollectionHasBeenClearedEvent` (Spatie) | `MediaCollectionClearedListener` | Logs media collection clearing activity |

### Event Flow

```
Media Created --> MediaHasBeenAddedEvent --> MediaCreatedListener
                                              |-- Set created_by_id
                                              |-- Generate slug
                                              |-- Log activity

Media Updated --> MediaObserver::updating() --> MediaUpdatedEvent --> MediaUpdatedListener
                                                                       |-- Log activity

Media Collection Cleared --> CollectionHasBeenClearedEvent --> MediaCollectionClearedListener
                                                                |-- Log activity
```

## Observers

21 model observers handle lifecycle events:

| Observer | Model | Responsibilities |
|---|---|---|
| `NewsObserver` | News | Dispatches sync jobs on create/update (async), delete cascade job (sync) |
| `CategoryObserver` | Category | Manages `slug_tree`/`name_tree` on create/update, dispatches delete cascade |
| `LocationObserver` | Location | Manages `slug_tree`/`name_tree` on create/update, dispatches delete cascade |
| `PageObserver` | Page | Manages `slug_tree`/`title_tree` on create/update, dispatches delete cascade |
| `MenuItemObserver` | MenuItem | Manages `slug_tree`/`name_tree` on create/update |
| `UserObserver` | User | Dispatches delete cascade on force delete |
| `ActivityObserver` | Activity | Auto-generates unique slugs for activity log records |
| `MediaObserver` | Spatie Media | Dispatches `MediaUpdatedEvent` on update |
| `BreakingNewsObserver` | BreakingNews | Dispatches delete cascade |
| `TagObserver` | Tag | Dispatches delete cascade |
| `TrendObserver` | Trend | Dispatches delete cascade |
| `ThemeObserver` | Theme | Dispatches delete cascade |
| `MenuObserver` | Menu | Dispatches delete cascade |
| `EventObserver` | Event | Dispatches delete cascade |
| `ContributorObserver` | Contributor | Dispatches delete cascade |
| `GoogleAdObserver` | GoogleAd | Dispatches delete cascade |
| `SurveyObserver` | Survey | Dispatches delete cascade |
| `SurveyQuestionObserver` | SurveyQuestion | Dispatches delete cascade |
| `QuizObserver` | Quiz | Dispatches delete cascade |
| `QuizQuestionObserver` | QuizQuestion | Empty placeholder |
| `QuizQuestionOptionObserver` | QuizQuestionOption | Empty placeholder |

## Policies & Authorization

20 authorization policies protect all back-office operations. Every policy follows the same pattern:

1. **`before()` hook** -- Super admins (`is_super_admin = true`) bypass all checks
2. **Module-level permissions** -- Each ability checks a specific permission via `UserPermissionHelper`
3. **Business logic guards** -- Some policies add extra rules (e.g., cannot delete default pages, cannot restore published news)

### Permission Modules

| Module | Access Levels |
|---|---|
| Breaking News, News, Page, User, Survey, Quiz | View Any, View, Create, Update, Delete, Restore, Force Delete |
| Category, Contributor, Event, Google Ad, Location, Menu, Menu Item, Tag, Trend, Survey Question, Quiz Question, Quiz Question Option | View Any, View, Create, Update, Delete |
| Theme | View Any, View, Update |
| Language | View Any, Update |

## Services

### Core Services

| Service | Purpose |
|---|---|
| `PageService` | Public page data (home, category, tag, location, event, contributor pages) |
| `SiteService` | Global site data (menus, breaking news, languages, themes, Google ads) |
| `FeedService` | RSS/Atom feed generation |
| `SitemapService` | XML sitemap generation |
| `SearchService` | Admin dropdown/autocomplete search |
| `AuthService` | Authentication, password reset, email verification |
| `DashboardService` | Admin dashboard data |

### Back-Office Services (23 classes)

One service per entity: `NewsService`, `CategoryService`, `TagService`, `LocationService`, `EventService`, `ContributorService`, `PageService`, `MenuService`, `LanguageService`, `ThemeService`, `TrendService`, `BreakingNewsService`, `GoogleAdService`, `UserService`, `MediaService`, `SurveyService`, `SurveyQuestionService`, `QuizService`, `QuizQuestionService`, `QuizQuestionOptionService`, `QuizResultService`, `SettingService`, `ActivityLogService`.

### Cache Services (14 classes)

One cache service per cached entity: `NewsCacheService`, `CategoryCacheService`, `TagCacheService`, `LocationCacheService`, `EventCacheService`, `ContributorCacheService`, `LanguageCacheService`, `MenuCacheService`, `PageCacheService`, `ThemeCacheService`, `NewsTypeCacheService`, `GoogleAdCacheService`, `SurveyCacheService`, `QuizCacheService`.

All cache services use `CacheServerHelper` as the underlying cache operations abstraction, and `CacheHelper` for deterministic cache key generation.

## Helpers

| Helper | Purpose |
|---|---|
| `CacheHelper` | Centralized cache key generation with 60+ key-building methods |
| `CacheServerHelper` | Cache read/write/flush abstraction with tag support and connection testing |
| `UserPermissionHelper` | Permission module and access level constants |
| `MediaHelper` | Media naming, URL cleaning, placeholder image generation, role constants |
| `NewsHelper` | News type constants (Story, Video, Image Gallery) |
| `PageHelper` | Page section constants (Lead News, Category News) |
| `MenuHelper` | Menu type and menu item model constants |
| `GoogleAdHelper` | Ad page, type, and placement constants with placement logic |
| `ThemeHelper` | Theme option type constants |
| `EventHelper` | Event position constants (Top, Bottom) |
| `QuizHelper` | Quiz answer type constants (Single, Multiple) |
| `DatatableHelper` | Pagination per-page options |
| `ActivityLogHelper` | Activity log event and subject type constants |
| `ArtisanCommandHelper` | CLI command wrappers for queue, scheduler, and cache management |
| `SystemHelper` | General utility methods |
| `SessionHelper` | Session management utilities |
| `UserHelper` | User-related utilities |
| `TagifyHelper` | Tagify component data helpers |
| `ReportHelper` | Report generation utilities |
| `SeederHelper` | Seeder data helpers |

## Database

### Key Tables

| Table | Purpose |
|---|---|
| `users` | User accounts with profile fields, soft deletes |
| `user_permissions` | Permission definitions (module + access) |
| `user_user_permission` | User-permission pivot |
| `languages` | Language definitions with default flag |
| `categories` | Hierarchical categories with tree paths |
| `tags` | Content tags with SEO fields |
| `locations` | Geospatial locations with boundary data |
| `events` | Date-range events with banner images |
| `contributors` | Author/journalist profiles |
| `news_types` | News type lookup (Story, Video, Image Gallery) |
| `news` | News articles with full content and metadata |
| `newses_relation` | News relationship pivot |
| `news_placements` | News-to-page-section placement |
| `news_relation_extra` | Extra news relationship data |
| `pages` | CMS pages with hierarchical tree |
| `menu_types` | Menu type lookup (Header, Topbar, Off-canvas, Footer) |
| `menus` | Menu containers |
| `menu_items` | Polymorphic menu items with hierarchy |
| `themes` | Theme configurations (JSON options) |
| `breaking_news` | Breaking news entries |
| `google_ads` | Google Ad Manager slot configurations |
| `surveys` | Survey definitions |
| `survey_questions` | Survey questions |
| `survey_question_results` | Survey voting results |
| `quizzes` | Quiz definitions |
| `quiz_questions` | Quiz questions with scoring |
| `quiz_question_options` | Quiz answer options |
| `quiz_participants` | Quiz participant profiles |
| `quiz_results` | Quiz submission results |
| `media` | Spatie Media Library files |
| `media_uploads` | Media upload entities |
| `cache` | Database cache store |
| `jobs` | Queue jobs |
| `failed_jobs` | Failed queue jobs |
| `activity_log` | Spatie Activity Log entries |
| `queue_monitor` | Queue monitoring data |
| `sessions` | Session storage |

### Seeders

35 seeders provide initial data, run via `php artisan db:seed`:

`DatabaseSeeder` orchestrates: `UserPermissionSeeder`, `UserSeeder`, `ThemeSeeder`, `LanguageSeeder`, `UpdateLanguageDefaultSeeder`, `NewsTypeSeeder`, `MenuTypeSeeder`, `CategorySeeder`, `TagSeeder`, `TrendSeeder`, `LocationSeeder`, `EventSeeder`, `ContributorSeeder`, `LocationMapInfoSeeder`, `PageSeeder`, `MenuSeeder`, `GoogleAdSeeder`, `SurveySeeder`, `SurveyQuestionSeeder`, `QuizSeeder`, `QuizQuestionSeeder`, `QuizQuestionOptionSeeder`, `QuizUpdateForShowResultAndMaxWinnerSeeder`, `QuizParticipantSeeder`, `NewsSeeder`, `NewsPlacementSeeder`, `BreakingNewsSeeder`, `RelatedNewsSyncSeeder`, `RelevantNewsSyncSeeder`, `NewsTagSyncSeeder`, `NewsContributorSyncSeeder`, `NewsMediaSeeder`, `UpdateEventSeeder`.

## Backend Dependencies

| Package | Purpose |
|---|---|
| `laravel/framework` ^13.0 | Core backend framework |
| `inertiajs/inertia-laravel` ^3.0 | Inertia.js server-side adapter for Laravel |
| `spatie/laravel-activitylog` ^5.0 | Activity logging for audit trails |
| `spatie/laravel-medialibrary` ^11.21 | Media file management with collections and conversions |
| `spatie/laravel-sluggable` ^4.0 | Automatic slug generation with Unicode support |
| `staudenmeir/laravel-adjacency-list` ^1.26 | Recursive relationships for hierarchical trees |
| `tightenco/ziggy` ^2.6 | Laravel named routes in JavaScript |
| `romanzipp/laravel-queue-monitor` ^5.4 | Web-based queue monitoring dashboard |
| `opcodesio/log-viewer` ^3.24 | Web-based application log viewer |
| `laravel/tinker` ^3.0 | REPL for Laravel |

## Frontend Dependencies

| Package | Purpose |
|---|---|
| `vue` ^3.5.32 | Frontend framework (Composition API) |
| `@inertiajs/vue3` ^3.0.3 | Inertia.js Vue 3 adapter |
| `vite` ^8.0.0 | Frontend build tool and dev server |
| `tailwindcss` ^4.2.2 | Utility-first CSS framework |
| `@tailwindcss/vite` ^4.0.0 | Tailwind CSS Vite plugin |
| `laravel-vite-plugin` ^3.0.0 | Laravel Vite integration |
| `axios` ^1.15.0 | HTTP client |
| `ziggy-js` ^2.6.2 | Laravel named routes in JavaScript |
| `vue-i18n` ^11.4.5 | Internationalization (English, Bangla) |
| `tinymce` ^8.4.0 | Rich text editor |
| `@tinymce/tinymce-vue` ^6.3.0 | TinyMCE Vue component |
| `@fortawesome/fontawesome-svg-core` ^7.2.0 | FontAwesome icon core |
| `@fortawesome/free-solid-svg-icons` ^7.2.0 | FontAwesome solid icons |
| `@fortawesome/free-brands-svg-icons` ^7.2.0 | FontAwesome brand icons |
| `@fortawesome/vue-fontawesome` ^3.2.0 | FontAwesome Vue component |
| `swiper` ^12.2.0 | Touch slider/carousel |
| `@fancyapps/ui` ^6.1.14 | Lightbox and image viewer |
| `leaflet` ^1.9.4 | Interactive maps |
| `vue-multiselect` ^3.5.0 | Multi-select dropdown |
| `vue-draggable-plus` ^0.6.1 | Drag-and-drop sorting |
| `vue-sonner` ^2.0.9 | Toast notifications |
| `vue-tel-input` ^9.8.0 | Phone number input |
| `vue3-social-sharing` ^1.5.0 | Social media sharing |
| `date-fns` ^4.1.0 | Date formatting utilities |
| `moment-timezone` ^0.6.1 | Timezone support |
| `world-countries` ^5.1.0 | Country data |
| `iso-language-codes` ^2.0.0 | Language code data |
| `locale-codes` ^1.3.1 | Locale code data |
| `concurrently` ^9.0.1 | Parallel script runner (dev) |

## Environment Configuration

Key environment variables (copy from `.env.example`):

```env
APP_NAME="News Portal"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

DB_CONNECTION=sqlite

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

FILESYSTEM_DISK=local

LOG_CHANNEL=daily

TIME_ZONE=UTC
DATE_FORMAT=d-M-Y
TIME_FORMAT="g:i:s a"

MEDIA_LIBRARY_IMAGE_DRIVER=gd
MEDIA_LIBRARY_MEDIA_DISK=public

ACTIVITYLOG_ENABLED=true
ACTIVITYLOG_CLEAN_AFTER_DAYS=90

NEWS_DEFAULT_LANGUAGE="en_us"

CACHE_ENABLE=false

VITE_APP_NAME="${APP_NAME}"
VITE_APP_URL="${APP_URL}"
VITE_APP_VERSION=1.0.0
VITE_NEWS_DEFAULT_LANGUAGE="${NEWS_DEFAULT_LANGUAGE}"
```

> **Note:** Never commit your `.env` file or expose real credentials. The above shows only non-secret configuration variables.

## Installation

### Local Development

```bash
# Clone the repository
git clone <repository-url>
cd news-portal-2

# Install PHP dependencies
composer install

# Create environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create SQLite database (if using SQLite)
touch database/database.sqlite

# Run migrations
php artisan migrate

# Seed the database
php artisan db:seed

# Create storage symlink
php artisan storage:link

# Install frontend dependencies
npm install

# Build frontend assets (development)
npm run dev

# Start all development services (server, queue, logs, vite)
composer dev
```

The `composer dev` command starts four processes concurrently:
- `php artisan serve` -- Laravel development server
- `php artisan queue:listen` -- Queue worker
- `php artisan pail` -- Real-time log viewer
- `npm run dev` -- Vite dev server with HMR

Alternatively, start services individually:

```bash
php artisan serve
npm run dev
php artisan queue:work
```

### cPanel Deployment

1. **Upload project files** to your hosting directory (e.g., `public_html/news-portal-2`).

2. **Point document root** to the `/public` directory:
   ```
   public_html/news-portal-2 -> public/
   ```

3. **Configure PHP version** >= 8.3 in cPanel PHP Selector.

4. **Configure `.env`:**
   ```bash
   cp .env.example .env
   # Edit .env with production values
   php artisan key:generate
   ```

5. **Set database** configuration in `.env` (switch from SQLite to MySQL if needed):
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database
   DB_USERNAME=your_user
   DB_PASSWORD=your_password
   ```

6. **Install dependencies and build:**
   ```bash
   composer install --no-dev --optimize-autoloader
   npm install
   npm run build
   php artisan migrate --force
   php artisan db:seed
   php artisan storage:link
   ```

7. **Optimize for production:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan optimize
   ```

8. **Configure queue worker** (if cPanel provides Terminal/SSH):
   ```bash
   php artisan queue:work --sleep=3 --tries=3 --max-time=3600
   ```

   If Supervisor is unavailable, use a cron job as a queue worker alternative:
   ```
   * * * * * cd /path/to/project && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
   ```

9. **Configure cron for scheduled tasks** (if Terminal is available):
   ```
   * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
   ```

### Nginx Deployment

1. **Install server dependencies:**
   ```bash
   sudo apt update
   sudo apt install php8.3-fpm php8.3-mysql php8.3-sqlite3 php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl composer nodejs npm
   ```

2. **Deploy application:**
   ```bash
   cd /var/www
   sudo git clone <repository-url> news-portal-2
   cd news-portal-2
   composer install --no-dev --optimize-autoloader
   npm install && npm run build
   cp .env.example .env
   php artisan key:generate
   # Configure .env
   php artisan migrate --force
   php artisan db:seed
   php artisan storage:link
   ```

3. **Set permissions:**
   ```bash
   sudo chown -R www-data:www-data /var/www/news-portal-2
   sudo chmod -R 755 /var/www/news-portal-2/storage
   sudo chmod -R 755 /var/www/news-portal-2/bootstrap/cache
   ```

4. **Create Nginx configuration:**

   > Replace `php8.3-fpm.sock` with the socket matching your installed PHP version.

   ```nginx
   server {
       listen 80;
       server_name example.com;

       root /var/www/news-portal-2/public;

       index index.php index.html;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           include fastcgi_params;
           fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
       }

       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```

5. **Enable and restart:**
   ```bash
   sudo ln -s /etc/nginx/sites-available/news-portal-2 /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl restart nginx
   sudo systemctl restart php8.3-fpm
   ```

6. **Optimize:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan optimize
   ```

7. **Configure queue worker** with Supervisor:
   ```ini
   [program:news-portal-queue]
   process_name=%(program_name)s_%(process_num)02d
   command=php /var/www/news-portal-2/artisan queue:work --sleep=3 --tries=3 --max-time=3600
   autostart=true
   autorestart=true
   stopasgroup=true
   killasgroup=true
   user=www-data
   numprocs=2
   redirect_stderr=true
   stdout_logfile=/var/www/news-portal-2/storage/logs/queue.log
   ```

8. **Configure scheduler cron:**
   ```
   * * * * * cd /var/www/news-portal-2 && php artisan schedule:run >> /dev/null 2>&1
   ```

## Database Commands

### Run migrations

```bash
php artisan migrate
```

### Rollback

```bash
php artisan migrate:rollback
```

### Fresh database

```bash
php artisan migrate:fresh
```

> **Warning:** `migrate:fresh` drops all tables and deletes all existing data.

### Seed database

```bash
php artisan db:seed
```

### Specific seeder

```bash
php artisan db:seed --class=NewsSeeder
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=CategorySeeder
```

## Frontend Commands

### Install dependencies

```bash
npm install
```

### Development

```bash
npm run dev
```

### Production build

```bash
npm run build
```

## Artisan Commands

```bash
# Application setup
php artisan key:generate

# Database
php artisan migrate
php artisan migrate:rollback
php artisan db:seed

# Storage
php artisan storage:link

# Cache management
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize

# Queue
php artisan queue:work
php artisan queue:restart
php artisan queue:listen

# Logs
php artisan pail

# Activity log cleanup (runs via scheduler)
php artisan activitylog:clean
```

## Queue Worker

The application requires a running queue worker for background job processing:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

### Production Recommendations

- Use **Supervisor** to manage queue workers (ensures automatic restart on failure)
- Run multiple worker processes for higher throughput
- Use `--max-time=3600` to periodically restart workers and prevent memory leaks
- Monitor queue health via the back-office Queue Monitor dashboard (`/back-office/queue-monitor`)

### Queue Management from Admin Panel

Super admins can manage the queue from the back-office Settings page:
- Start queue worker
- Restart queue worker
- Clear pending jobs
- Flush failed jobs
- Purge stale monitor entries

## Production Optimization

### Laravel Optimization

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Frontend Build

```bash
npm run build
```

### Recommended Production `.env` Settings

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

LOG_CHANNEL=daily
LOG_LEVEL=error

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
```

## Troubleshooting

### Application key missing

```bash
php artisan key:generate
```

### Database connection errors

Ensure `.env` database configuration is correct. For SQLite, ensure `database/database.sqlite` exists:

```bash
touch database/database.sqlite
php artisan migrate
```

### Storage link not working

```bash
php artisan storage:link
```

### Queue not processing

1. Check queue connection in `.env` (`QUEUE_CONNECTION=database`)
2. Ensure the queue worker is running: `php artisan queue:work`
3. Check failed jobs: `php artisan queue:failed`
4. Retry failed jobs: `php artisan queue:retry all`

### Frontend assets not building

```bash
rm -rf node_modules
npm install
npm run build
```

### Vite dev server issues

Ensure the Vite dev server is running (`npm run dev`) and accessible at `http://127.0.0.1:5173`.

### Cache issues

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

### Permission errors (Linux)

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 755 storage bootstrap/cache
```

### Activity log cleanup

The scheduler automatically cleans activity logs older than 90 days. To run manually:

```bash
php artisan activitylog:clean
```
