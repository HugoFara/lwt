# Icon Migration Guide: Fugue Icons to Lucide

This document maps the legacy Fugue PNG icons in `assets/icons/` to their modern Lucide SVG equivalents.

## Overview

- **Current**: 97 PNG/GIF icons (16x16 pixels, Fugue icon set style)
- **Target**: Lucide SVG icons (scalable, CSS-customizable)
- **Location**: `assets/icons/` → Lucide via npm/CDN

## Icon Mapping Table

### Navigation & Controls

| Old Icon | Lucide Icon | Usage |
|----------|-------------|-------|
| `arrow-000-medium.png` | `arrow-right` | Forward navigation |
| `arrow-180-medium.png` | `arrow-left` | Back navigation |
| `arrow-circle-135.png` | `refresh-cw` | Refresh/reload |
| `arrow-circle-225-left.png` | `rewind` | Rewind media |
| `arrow-circle-315.png` | `fast-forward` | Forward media |
| `arrow-repeat.png` | `repeat` | Toggle repeat ON |
| `arrow-norepeat.png` | `repeat-off` *(custom)* | Toggle repeat OFF |
| `arrow-stop.png` | `square` | Stop playback |
| `control.png` | `chevron-right` | Next page |
| `control-180.png` | `chevron-left` | Previous page |
| `control-stop.png` | `chevrons-right` | Last page |
| `control-stop-180.png` | `chevrons-left` | First page |
| `navigation-000-button.png` | `circle-chevron-right` | Next text |
| `navigation-000-button-light.png` | `circle-chevron-right` (muted) | Next text (disabled) |
| `navigation-180-button.png` | `circle-chevron-left` | Previous text |
| `navigation-180-button-light.png` | `circle-chevron-left` (muted) | Previous text (disabled) |

### Actions - CRUD

| Old Icon | Lucide Icon | Usage |
|----------|-------------|-------|
| `plus.png` | `plus` | Add/increase |
| `plus-button.png` | `plus-circle` | New item button |
| `minus.png` | `minus` | Remove/decrease |
| `minus-button.png` | `minus-circle` or `trash-2` | Delete button |
| `cross.png` | `x` | Close/cancel/remove |
| `cross-button.png` | `x-circle` | Close button |
| `cross-big.png` | `x` (larger) | Large close |
| `tick.png` | `check` | Confirm/yes/done |
| `tick-button.png` | `check-circle` | Confirm button |
| `tick-button-small.png` | `check-circle` (small) | Small confirm |
| `pencil.png` | `pencil` | Edit |
| `document--pencil.png` | `file-pen` | Edit document |
| `eraser.png` | `eraser` | Clear/erase |
| `broom.png` | `brush` *(or trash-2)* | Clean up |

### Documents & Text

| Old Icon | Lucide Icon | Usage |
|----------|-------------|-------|
| `book-open-bookmark.png` | `book-open` | Read text |
| `book-open-text.png` | `book-open-text` | Open text content |
| `book--pencil.png` | `book-open-check` *(or pen-line)* | Edit book |
| `notebook.png` | `notebook` | Notebook |
| `notebook--plus.png` | `notebook-pen` | Add to notebook |
| `notebook--minus.png` | `notebook` + `minus` | Remove from notebook |
| `notebook--pencil.png` | `notebook-pen` | Edit notebook |
| `sticky-note.png` | `sticky-note` | Note |
| `sticky-note--plus.png` | `notepad-text-dashed` | Add note |
| `sticky-note--minus.png` | `sticky-note` + `minus` | Remove note |
| `sticky-note--pencil.png` | `file-pen-line` | Edit note/term |
| `sticky-note-text.png` | `notepad-text` | Note with text |
| `sticky-notes.png` | `layers` | Multiple notes |
| `sticky-notes-stack.png` | `layers` | Stacked notes |
| `sticky-notes-text.png` | `file-stack` | Multiple notes with text |
| `new_line.png` | `wrap-text` | New line indicator |
| `script-import.png` | `file-down` | Import script/file |

### Cards & Flashcards

| Old Icon | Lucide Icon | Usage |
|----------|-------------|-------|
| `card--plus.png` | `square-plus` | Add card |
| `card--minus.png` | `square-minus` | Remove card |
| `card--pencil.png` | `square-pen` | Edit card |
| `cards-stack.png` | `layers` | Card stack |

### Feeds & RSS

| Old Icon | Lucide Icon | Usage |
|----------|-------------|-------|
| `feed--plus.png` | `rss` + `plus` *(or circle-plus)* | New feed |
| `feed--pencil.png` | `rss` | Edit feed |

### Storage & Archive

| Old Icon | Lucide Icon | Usage |
|----------|-------------|-------|
| `drawer--plus.png` | `archive` | Add to archive |
| `drawer--minus.png` | `archive-x` | Remove from archive |
| `inbox-download.png` | `download` | Download/import |
| `inbox-upload.png` | `upload` | Upload/unarchive |

### Status Indicators

| Old Icon | Lucide Icon | Usage |
|----------|-------------|-------|
| `status.png` | `circle-check` (green) | OK/success/yes |
| `status-busy.png` | `circle-x` (red) | Error/no |
| `status-away.png` | `circle-dot` (yellow) | Warning/pending |
| `exclamation-red.png` | `circle-alert` | Current/active warning |
| `exclamation-button.png` | `alert-circle` | Alert button |

### Form Validation

| Old Icon | Lucide Icon | Usage |
|----------|-------------|-------|
| *(new)* | `asterisk` (red) | Required field indicator |

### Test Results

| Old Icon | Lucide Icon | Usage |
|----------|-------------|-------|
| `test_correct.png` | `circle-check` (green) | Correct answer |
| `test_wrong.png` | `circle-x` (red) | Wrong answer |
| `test_notyet.png` | `circle-help` | Not answered yet |
| `smiley.png` | `smile` | Happy/success |
| `smiley-sad.png` | `frown` | Sad/failure |
| `thumb.png` | `thumbs-up` | Thumbs up |
| `thumb-up.png` | `thumbs-up` | Thumbs up |

### UI Elements

| Old Icon | Lucide Icon | Usage |
|----------|-------------|-------|
| `funnel.png` | `filter` | Filter |
| `funnel--minus.png` | `filter-x` | Clear filter |
| `lightning.png` | `zap` | Multi actions/quick |
| `wizard.png` | `wand-2` | Wizard/setup |
| `wrench-screwdriver.png` | `settings` | Settings |
| `calculator.png` | `calculator` | Calculator/stats |
| `clock.png` | `clock` | Time/elapsed |
| `chain.png` | `link` | External link/source |
| `external.png` | `external-link` | Open external |
| `printer.png` | `printer` | Print |
| `eye.png` | `eye` | View/show |
| `star.png` | `star` | Favorite/set to term |
| `photo-album.png` | `image` | Image/photo |

### Light Bulb (Show/Hide Translations)

| Old Icon | Lucide Icon | Usage |
|----------|-------------|-------|
| `light-bulb.png` | `lightbulb` | Show hints |
| `light-bulb-off.png` | `lightbulb-off` | Hide hints |
| `light-bulb-A.png` | `lightbulb` | Show all translations |
| `light-bulb-off-A.png` | `lightbulb-off` | Hide all translations |
| `light-bulb-T.png` | `lightbulb` | Show term translations |
| `light-bulb-off-T.png` | `lightbulb-off` | Hide term translations |

### Audio & Media

| Old Icon | Lucide Icon | Usage |
|----------|-------------|-------|
| `speaker-volume.png` | `volume-2` | Audio/with sound |
| `speaker-volume-none.png` | `volume-x` | Muted |

### Help & Info

| Old Icon | Lucide Icon | Usage |
|----------|-------------|-------|
| `question-balloon.png` | `circle-help` | Test/help |
| `question-frame.png` | `help-circle` | Help tooltip |

### Animated/Loading

| Old Icon | Lucide Icon | Usage |
|----------|-------------|-------|
| `waiting.gif` | `loader-2` (animated CSS) | Loading spinner |
| `waiting2.gif` | `loader-2` (animated CSS) | Loading spinner alt |
| `indicator.gif` | `loader` (animated CSS) | Progress indicator |

### Placeholder/Empty

| Old Icon | Lucide Icon | Usage |
|----------|-------------|-------|
| `placeholder.png` | `circle` (muted) | Disabled/placeholder |
| `empty.gif` | *(empty span)* | Empty spacer |

## Implementation Strategy

### Phase 1: Create Icon Helper

Create a PHP helper function to render Lucide icons:

```php
// src/backend/View/Helper/IconHelper.php
function render_icon(string $name, array $attrs = []): string {
    $class = $attrs['class'] ?? '';
    $title = $attrs['title'] ?? '';
    $size = $attrs['size'] ?? 16;

    return sprintf(
        '<i data-lucide="%s" class="icon %s" title="%s" style="width:%dpx;height:%dpx"></i>',
        htmlspecialchars($name),
        htmlspecialchars($class),
        htmlspecialchars($title),
        $size,
        $size
    );
}
```

### Phase 2: Include Lucide in Layout

Add to the HTML head (already installed via npm):

```html
<script src="/node_modules/lucide/dist/umd/lucide.min.js"></script>
<script>lucide.createIcons();</script>
```

Or import in your Vite entry point:

```typescript
// src/frontend/js/main.ts
import { createIcons, icons } from 'lucide';
createIcons({ icons });
```

### Phase 3: CSS Styling

```css
.icon {
    display: inline-block;
    vertical-align: middle;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
    fill: none;
}

.icon.muted {
    opacity: 0.4;
}

/* Animated spinner */
.icon[data-lucide="loader-2"] {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
```

### Phase 4: Gradual Migration

1. Start with high-impact pages (home, text list, word list)
2. Replace icons file by file
3. Keep old PNG files until migration complete
4. Remove `assets/icons/` directory when done

## Icons Not in Lucide (Need Custom or Alternative)

| Old Icon | Suggestion |
|----------|------------|
| `arrow-norepeat.png` | Use `repeat` with strikethrough CSS or custom SVG |
| `light-bulb-A.png` / `light-bulb-T.png` | Use `lightbulb` with text label "A" or "T" |

## Files to Update

Major files with icon references:

- `src/backend/Views/` - All view templates
- `src/backend/Controllers/` - Controller HTML output
- `src/backend/Services/` - Service HTML generation
- `src/backend/View/Helper/PageLayoutHelper.php` - Pagination
- `src/backend/View/Helper/StatusHelper.php` - Status icons
- `src/backend/Api/V1/Handlers/ImprovedTextHandler.php` - Text display

## Testing Checklist

After migration, verify icons display correctly on:

- [ ] Home page
- [ ] Language list
- [ ] Text list (active and archived)
- [ ] Word/term list
- [ ] Text reading view
- [ ] Test/review mode
- [ ] Print view
- [ ] Feed management
- [ ] Settings page
- [ ] Tag management
- [ ] Media player controls
