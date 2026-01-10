# EPUB/PDF Import Feature Proposal

## Overview

Add support for importing EPUB and PDF files as readable texts, with proper handling of long documents through chapter-aware chunking and book-level metadata.

## Problem Statement

Current LWT constraints:
- **65KB max text size** (MySQL TEXT column limit)
- **No pagination within texts** - entire content loaded client-side
- **Existing splitting is crude** - splits by byte count at paragraph boundaries, loses document structure

A typical novel (300-500KB) would create 5-8 disconnected text entries with no awareness of chapters or navigation between parts.

## Proposed Solution

### Phase 1: Book Entity + Smart Chunking

#### Database Schema

```sql
CREATE TABLE books (
    BkID INT AUTO_INCREMENT PRIMARY KEY,
    BkUsID INT NULL,                          -- User ID (multi-user support)
    BkLgID TINYINT UNSIGNED NOT NULL,         -- Language
    BkTitle VARCHAR(500) NOT NULL,
    BkAuthor VARCHAR(255) NULL,
    BkDescription TEXT NULL,
    BkCoverImage MEDIUMBLOB NULL,             -- Cover thumbnail
    BkSourceType ENUM('epub', 'pdf', 'manual') NOT NULL,
    BkSourceHash CHAR(64) NULL,               -- SHA-256 for deduplication
    BkTotalChapters SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    BkCurrentChapter SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    BkCreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    BkUpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (BkLgID) REFERENCES languages(LgID) ON DELETE RESTRICT,
    INDEX idx_books_user (BkUsID),
    INDEX idx_books_language (BkLgID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Link texts to books
ALTER TABLE texts
    ADD COLUMN TxBkID INT NULL AFTER TxUsID,
    ADD COLUMN TxChapterNum SMALLINT UNSIGNED NULL AFTER TxBkID,
    ADD COLUMN TxChapterTitle VARCHAR(255) NULL AFTER TxChapterNum,
    ADD FOREIGN KEY (TxBkID) REFERENCES books(BkID) ON DELETE CASCADE,
    ADD INDEX idx_texts_book (TxBkID, TxChapterNum);
```

#### Import Flow

```
EPUB File
    ↓
[Parse EPUB structure (PHP epub-parser library)]
    ↓
[Extract metadata: title, author, cover, TOC]
    ↓
[Create book record]
    ↓
[For each chapter in TOC:]
    ├── Extract chapter HTML
    ├── Strip HTML tags, normalize whitespace
    ├── If chapter > 60KB, split at paragraph boundaries
    ├── Create text record with TxBkID, TxChapterNum
    └── Parse text (existing TextParsing::parseAndSave)
    ↓
[Update BkTotalChapters count]
```

#### EPUB Parsing

Recommended library: `kiwilan/php-ebook` (MIT license, PHP 8.1+)

```php
use Kiwilan\Ebook\Ebook;

$ebook = Ebook::read('/path/to/book.epub');
$title = $ebook->getTitle();
$author = $ebook->getAuthorMain();
$chapters = $ebook->getChapters(); // Returns chapter content
```

#### PDF Handling

PDFs are more complex due to layout-based structure. Options:

1. **pdftotext (poppler-utils)** - Command-line, good for simple PDFs
2. **Smalot/PdfParser** - Pure PHP, handles most PDFs
3. **User-assisted** - Let users paste text, auto-detect chapter markers

Recommendation: Start with pdftotext, fall back to manual paste.

### Phase 2: Book Navigation UI

#### Book Library View

New route: `/books`

- Grid/list view of imported books with covers
- Progress indicator per book (chapters read / total)
- Filter by language, sort by recently read
- "Continue reading" button

#### Reading Interface Changes

When reading a text that belongs to a book:

- Show chapter title in header
- Previous/Next chapter navigation
- Chapter dropdown selector
- Book progress bar
- "Back to book" link

#### API Endpoints

```
GET    /api/v1/books                    List books (paginated)
POST   /api/v1/books                    Import book (multipart form)
GET    /api/v1/books/{id}               Book details + chapter list
DELETE /api/v1/books/{id}               Delete book and all chapters
GET    /api/v1/books/{id}/chapters      List chapters with progress
PATCH  /api/v1/books/{id}/position      Update current chapter
```

### Phase 3: Lazy Loading (Future)

For very long chapters, implement virtualized reading:

```typescript
// Load sentences in viewport + buffer
const visibleRange = calculateVisibleSentences();
const buffer = 50; // sentences before/after
await loadSentences(visibleRange.start - buffer, visibleRange.end + buffer);
```

This requires:
- Change `TxText` to LONGTEXT
- New API: `GET /texts/{id}/sentences?from={start}&to={end}`
- Frontend: Intersection Observer for scroll-based loading
- Sentence-level position tracking

## File Structure

```
src/Modules/Book/
├── Application/
│   ├── Services/
│   │   ├── BookService.php
│   │   └── EpubParserService.php
│   └── UseCases/
│       ├── ImportBook.php
│       └── DeleteBook.php
├── Domain/
│   ├── Book.php
│   └── BookRepositoryInterface.php
├── Http/
│   ├── BookController.php
│   └── BookApiHandler.php
├── Infrastructure/
│   └── BookRepository.php
├── Views/
│   ├── book_library.php
│   └── book_detail.php
└── BookServiceProvider.php
```

## Dependencies

Add to `composer.json`:

```json
{
    "require": {
        "kiwilan/php-ebook": "^2.0"
    }
}
```

System dependency for PDF (optional):
```bash
apt install poppler-utils  # provides pdftotext
```

## Migration

```sql
-- db/migrations/YYYYMMDD_HHMMSS_create_books_table.sql

CREATE TABLE IF NOT EXISTS books (
    -- schema from above
);

ALTER TABLE texts
    ADD COLUMN IF NOT EXISTS TxBkID INT NULL,
    ADD COLUMN IF NOT EXISTS TxChapterNum SMALLINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS TxChapterTitle VARCHAR(255) NULL;

-- Add foreign key only if not exists (MariaDB 10.5+)
-- For older versions, check and add manually
```

## Open Questions

1. **Large chapter handling**: Split at 60KB or implement lazy loading from the start?
2. **PDF quality**: How to handle scanned PDFs (OCR needed)?
3. **DRM**: Reject DRM-protected EPUBs with clear error message?
4. **Storage**: Store original file, or just extracted text?
5. **Updates**: Allow re-importing updated EPUB to sync changes?

## Related Features

Once books are implemented, these become easier:

- **Reading statistics**: Time per chapter, words read per session
- **Bookmarks**: Save positions within chapters
- **Highlights**: Mark important passages
- **Book recommendations**: Based on language and difficulty
- **Shared libraries**: Community-uploaded public domain books

## References

- [EPUB 3 Specification](https://www.w3.org/TR/epub-33/)
- [kiwilan/php-ebook](https://github.com/kiwilan/php-ebook)
- [Smalot/PdfParser](https://github.com/smalot/pdfparser)
- Current text handling: `src/Modules/Text/Application/UseCases/ImportText.php`
