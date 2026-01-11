# LWT Modernization Plan

**Last Updated:** 2026-01-11
**Current Version:** 3.0.0-fork
**Target PHP Version:** 8.1-8.4

## Executive Summary

LWT carried significant technical debt from its 2007 origins. **Major architectural transformation has been achieved** with the v3 release.

**Overall Technical Debt Score:** 2.0/10 (Low) - *Down from 6.75/10*

## Progress Overview

| Phase | Status | Completion |
|-------|--------|------------|
| Quick Wins | **COMPLETE** | 100% |
| Phase 1: Security & Safety | **COMPLETE** | ~95% |
| Phase 2: Refactoring | **COMPLETE** | ~95% |
| Phase 3: Modernization | **COMPLETE** | ~98% |
| Phase 4: Modular Monolith | **COMPLETE** | 100% |

## Architecture Summary (2026-01-07)

| Component | Count | Lines |
|-----------|-------|-------|
| Controllers | 17 | ~9,000 |
| Services | 36 | 18,571 |
| Repositories | 4 | ~3,000 |
| Views | 92 | 8,499 |
| Core Files | 37 | - |
| API Handlers (Modules) | 14 | - |
| Entity Classes | 5 | - |
| Value Objects | 5 | - |
| Custom Exceptions | 6 | - |
| **Total PHP Files** | **204** | - |

### Directory Structure

```text
src/
├── Shared/                              # Cross-cutting infrastructure
│   ├── Infrastructure/
│   │   ├── Database/                    # Connection, QueryBuilder, DB facade
│   │   ├── Http/                        # InputValidator, SecurityHeaders
│   │   └── Container/                   # DI container
│   ├── Domain/
│   │   └── ValueObjects/UserId.php      # Cross-module user identity
│   └── UI/
│       ├── Helpers/                     # FormHelper, PageLayoutHelper, etc.
│       └── Assets/ViteHelper
│
├── Modules/                             # Feature modules (bounded contexts)
│   ├── Admin/                           # Settings, backup, statistics
│   ├── Dictionary/                      # Local dictionaries, translation
│   ├── Feed/                            # RSS feed management
│   ├── Home/                            # Dashboard
│   ├── Language/                        # Language configuration
│   ├── Review/                          # Spaced repetition testing
│   ├── Tags/                            # Term and text tagging
│   ├── Text/                            # Text reading/import
│   ├── User/                            # Authentication
│   └── Vocabulary/                      # Terms/words management
│
└── backend/                             # Legacy structure (minimal)
    ├── Controllers/                     # Abstract bases only
    ├── Services/                        # Gradually migrating to modules
    ├── Core/                            # Bootstrap, entities, utilities
    └── Api/V1/                          # API router (delegates to modules)
```

### Module Structure

Each module follows hexagonal architecture:

```text
Modules/[Module]/
├── Application/                     # Use cases and services
├── Domain/                          # Entities, value objects, interfaces
├── Http/                            # Controllers, API handlers
├── Infrastructure/                  # Repository implementations
├── Views/                           # Templates
└── [Module]ServiceProvider.php      # DI registration
```

### Value Object Locations

| Value Object | Location |
|--------------|----------|
| `LanguageId` | `Modules/Language/Domain/ValueObject/LanguageId.php` |
| `TextId` | `Modules/Text/Domain/ValueObject/TextId.php` |
| `TermId` | `Modules/Vocabulary/Domain/ValueObject/TermId.php` |
| `TermStatus` | `Modules/Vocabulary/Domain/ValueObject/TermStatus.php` |
| `UserId` | `Shared/Domain/ValueObjects/UserId.php` |

## Quality Metrics

| Metric | Status | Details |
|--------|--------|---------|
| Psalm Level 1 | PASS | 0 errors |
| PHPUnit Tests | PASS | 3259 tests, 6865 assertions |
| TypeScript | PASS | No errors |
| ESLint | PASS | No errors |
| Deprecated Code | PASS | 0 items remaining |
| TODO Comments | PASS | 0 items |
| strict_types | 100% | All 204 PHP files |

## Completed Milestones

### Security (Phase 1)
- Prepared statements: 233 calls across 26 files (~95% adoption)
- InputValidator: 16 files using type-safe validation
- Session cookies: HttpOnly, Secure, SameSite flags
- Security headers: CSP, X-Frame-Options, HSTS, etc.

### Architecture (Phases 2-3)
- All legacy monolithic files eliminated
- MVC architecture fully implemented
- DI container integrated with auto-wiring
- Repository pattern for core entities (Language, Text, Term, User)
- 6 custom exception classes with global handler
- InnoDB for all permanent tables with full FK constraints (user ownership + inter-table)

### Modular Monolith (Phase 4)
All 10 modules complete with ServiceProviders:
- Admin, Dictionary, Feed, Home, Language
- Review, Tags, Text, User, Vocabulary

## Remaining Work

### v3.0.0 Release Checklist

**Must Complete:**
- [x] Update API version from 0.1.1 to 3.0.0

**Nice to Have:**
- [x] Achieve Psalm level 1 compliance
- [x] 100% test coverage for module domain layer (value objects, entities, enums)

### Future Improvements
- [x] Migrate away from `unsafe-inline` in CSP script-src (style-src still needs it)
- [x] Inter-table foreign keys (texts→languages, words→languages, etc.)
- [ ] Transaction usage in multi-query operations

## Test Coverage

| Layer | Total | Tested | Coverage |
|-------|-------|--------|----------|
| Module Facades | 10 | 10 | 100% |
| Module Domain Value Objects | 7 | 7 | 100% |
| Module Domain Entities | 2 | 2 | 100% |
| Module Domain Enums | 1 | 1 | 100% |
| Module Use Cases | 65 | 5 | 8% |
| Module Application Services | 7 | 1 | 14% |
| Module HTTP Handlers | 7 | 4 | 57% |
| Module HTTP Controllers | 11 | 2 | 18% |

Key test files added (2026-01-06):
- All 10 facade tests (AdminFacade, DictionaryFacade, FeedFacade, HomeFacade, LanguageFacade, ReviewFacade, TagsFacade, TextFacade, UserFacade, VocabularyFacade)
- Use case tests: ImportText, ParseText, LoadFeed, ImportArticles, ReviewSession
- Service tests: ExportService, SimilarityCalculator, RssParser
- Domain Value Object tests: TermStatus, TermId, TextId, LanguageId, TagId, UserId, TestConfiguration
- Domain Entity tests: Tag
- Domain Enum tests: TagType

---

**Document Owner:** LWT Maintainers
**Review Cycle:** Quarterly
**Last Review:** 2026-01-11
**Next Review:** 2026-04-11
