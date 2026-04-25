# UI/UX Refactor Instructions — ITSM Ticketing System

> These instructions are authoritative for all UI/UX work. Follow them exactly.
> Stack: Laravel 12 + Livewire 3 + Alpine.js 3 + Tailwind CSS 4
> Direction: RTL (Arabic primary) / LTR (English) — layout must flip correctly for both.

---

## 1. Design Personality

**Style**: Enterprise-formal. Clean, structured, trustworthy. Think Saudi government portals done well — not bureaucratic and ugly, but authoritative and clear. No gradients. No decorative noise. Every element earns its place.

**Tone words**: Precise. Structured. Legible. Calm authority.

**What to avoid**:
- Rounded pill buttons everywhere
- Pastel colors or playful accents
- Dense shadows or glassmorphism
- Anything that looks like a startup SaaS landing page
- Generic AI-generated layouts (uniform card grids, purple accents, Inter font)

---

## 2. Color System

All colors are defined via Tailwind 4's `@theme` block in your main CSS file (e.g. `app.css`). This extends Tailwind's token system so you use standard utility classes everywhere — `bg-primary-500`, `text-status-resolved`, `border-danger` — no arbitrary values, no inline styles.

### Step zero — add this to your `app.css` before any refactor begins

```css
@import "tailwindcss";

@theme {
  /* Primary — deep governmental blue */
  --color-primary-50:  #EBF0F8;
  --color-primary-100: #C8D6EE;
  --color-primary-200: #A5BCE3;
  --color-primary-300: #7EA0D5;
  --color-primary-400: #4D7ABF;
  --color-primary-500: #1B3A6B;  /* ← main brand color */
  --color-primary-600: #153060;
  --color-primary-700: #0F2550;
  --color-primary-800: #0A1A3A;
  --color-primary-900: #050E22;

  /* Surface / background tokens */
  --color-surface-base: #F5F6F8;   /* page background */
  --color-surface:      #FFFFFF;   /* card / panel */
  --color-surface-alt:  #F0F2F5;   /* table header, alternate rows */

  /* Border tokens */
  --color-border:       #D4D8DF;
  --color-border-strong:#B0B7C3;

  /* Text tokens */
  --color-text-base:      #1A1F2E;
  --color-text-secondary: #4A5568;
  --color-text-muted:     #718096;

  /* Semantic */
  --color-success: #16A34A;
  --color-warning: #D97706;
  --color-danger:  #DC2626;
  --color-info:    #1D4ED8;

  /* Ticket status — one token per status, used for badges + indicators */
  --color-status-awaiting:   #6B7280;
  --color-status-inprogress: #1D4ED8;
  --color-status-onhold:     #D97706;
  --color-status-review:     #7C3AED;
  --color-status-approval:   #0891B2;
  --color-status-action:     #DC2626;
  --color-status-resolved:   #16A34A;
  --color-status-closed:     #374151;
  --color-status-cancelled:  #9CA3AF;

  /* Priority */
  --color-priority-low:      #6B7280;
  --color-priority-medium:   #D97706;
  --color-priority-high:     #DC2626;
  --color-priority-critical: #7F1D1D;
}
```

### How to use in Blade (standard Tailwind classes, nothing special)

```html
<!-- Primary button -->
<button class="bg-primary-500 hover:bg-primary-600 text-white">...</button>

<!-- Sidebar active item -->
<a class="border-s-4 border-primary-500 bg-primary-50 text-primary-500">...</a>

<!-- Page background -->
<main class="bg-surface-base">...</main>

<!-- Card -->
<div class="bg-surface border border-border shadow-sm rounded">...</div>

<!-- Table header -->
<thead class="bg-surface-alt text-text-secondary">...</thead>

<!-- Danger button -->
<button class="bg-danger text-white hover:opacity-90">...</button>

<!-- Focus ring on inputs -->
<input class="focus:ring-2 focus:ring-primary-500 focus:border-primary-500">

<!-- Status badge (see Section 8 for full badge pattern) -->
<span class="bg-status-resolved/10 text-status-resolved">Resolved</span>
```

### Rules
- **Never hardcode a hex color** in a Blade file — if the token doesn't exist, add it to `@theme` first
- **Never use arbitrary values** like `bg-[#1B3A6B]` — that defeats the whole system
- `primary-500` is the default brand color; use `primary-600` for hover, `primary-50` for light backgrounds
- Status and priority tokens are single-value (no scale) — use `/10` opacity modifier for light badge backgrounds: `bg-status-onhold/10 text-status-onhold`

---

## 3. Typography

**Arabic**: `Noto Kufi Arabic` or `IBM Plex Arabic` — formal, legible, not decorative.
**English**: `IBM Plex Sans` — pairs well, maintains enterprise feel.

Load via Google Fonts or a self-hosted font stack. Never use Inter, Roboto, or system-ui as the primary choice.

```css
[dir="rtl"] { font-family: 'IBM Plex Arabic', 'Noto Kufi Arabic', serif; }
[dir="ltr"] { font-family: 'IBM Plex Sans', sans-serif; }
```

**Scale** (Tailwind classes mapped):
- Page title: `text-2xl font-semibold`
- Section heading: `text-lg font-semibold`
- Label: `text-sm font-medium text-text-secondary`
- Body: `text-sm text-text-base`
- Meta/muted: `text-xs text-text-muted`

Line height: generous — `leading-relaxed` for body, `leading-snug` for headings.

---

## 4. Layout & Spacing

### Shell
```
┌─────────────────────────────────────────┐
│  Top Nav (64px fixed)                   │
├──────────┬──────────────────────────────┤
│          │                              │
│ Sidebar  │   Main Content Area          │
│ (240px)  │   max-w: 1280px, px-6 py-8  │
│          │                              │
└──────────┴──────────────────────────────┘
```

- Sidebar: white background, `border-e` (RTL-aware border), items with icon + label
- Active sidebar item: `border-s-4 border-primary-500 bg-primary-50 text-primary-500`
- Content area: `bg-surface-base`, never white directly
- Cards/panels: `bg-surface rounded border border-border shadow-sm`

### Spacing discipline
- Section gap: `gap-6` between major blocks
- Card padding: `p-6`
- Form field gap: `gap-4`
- Table cell padding: `px-4 py-3`
- Never mix spacing scales randomly — pick `4 / 6 / 8` and stay consistent

### RTL rule
Use logical properties everywhere:
- `ms-` / `me-` instead of `ml-` / `mr-`
- `ps-` / `pe-` instead of `pl-` / `pr-`
- `border-s` / `border-e` instead of `border-l` / `border-r`
- `rounded-s` / `rounded-e` for directional rounding
- Tailwind 4 supports these — use them, not direction-specific hacks

---

## 5. Forms

Forms are the most-used interface in this app. Every field must be impeccable.

### Field structure
```html
<div class="flex flex-col gap-1.5">
  <label class="text-sm font-medium text-text-secondary">
    Field Label <span class="text-danger">*</span>
  </label>
  <input
    class="w-full px-3 py-2.5 text-sm border border-border rounded
           bg-white focus:outline-none focus:ring-2 focus:ring-primary-500
           focus:border-primary-500 transition-colors"
  />
  <p class="text-xs text-text-muted">Helper text if needed</p>
  <!-- OR -->
  <p class="text-xs text-danger">Error message</p>
</div>
```

### Rules
- Always show the label — never placeholder-only fields
- Required fields: asterisk in danger color, not "(required)" text
- Error state: `border-danger focus:ring-danger` + error message below
- Disabled state: `bg-surface-alt text-text-muted cursor-not-allowed`
- Select elements: custom arrow, never browser default
- Textarea: `resize-y` only, `min-h-[100px]`
- File upload: styled drop zone — never a bare `<input type="file">`

### Form layout
- Two-column grid for short fields: `grid grid-cols-2 gap-4`
- Single column for long fields (description, notes, rich text)
- Section dividers with labels for long forms: `<fieldset>` with a `<legend>`
- Submit button: always `type="submit"`, right-aligned in LTR / left-aligned in RTL, primary color

### Validation feedback
- Inline, below the field — not toast-only, not alert-box-only
- Livewire `wire:model.live` for real-time validation on blur

---

## 6. Tables

All data tables in this app follow one standard. No variation.

### Structure
```
┌─────────────────────────────────────────────┐
│ Table Header Bar                             │
│ [Title + count]        [Search] [Filter btn] │
├──────────────────────────────────────────────┤
│ Column A  │ Column B  │ Column C  │ Actions  │
├──────────────────────────────────────────────┤
│ row data  │           │           │  [btn]   │
│ row data  │           │           │  [btn]   │
├──────────────────────────────────────────────┤
│ Pagination: Prev  1 2 3 ... 10  Next         │
└──────────────────────────────────────────────┘
```

### Rules
- Header: `bg-surface-alt text-xs font-semibold uppercase tracking-wide text-text-secondary`
- Rows: `bg-white`, hover: `hover:bg-primary-50`
- Striping: subtle — `even:bg-surface-alt` only if table is dense
- Status badges: small pill `px-2 py-0.5 rounded text-xs font-medium` using status tokens (see Section 8)
- Priority badges: same pill pattern using priority tokens
- Actions column: always last, right-aligned in LTR / left-aligned in RTL, icon buttons with tooltips
- Empty state: centered illustration + message + primary action button — never a blank table
- Loading state: skeleton rows, not spinner overlay

### Column rules
- ID/ULID: truncated with tooltip showing full value
- Dates: always show relative (`منذ 3 أيام` / `3 days ago`) + full date on hover
- User: avatar initial + name, never email alone
- Long text: truncate with `truncate max-w-[200px]` + tooltip

---

## 7. Buttons

Three variants only. No exceptions.

```
Primary:   bg-primary-500 text-white hover:bg-primary-600
Secondary: bg-white text-text-base border border-border hover:bg-surface-alt
Danger:    bg-danger text-white hover:opacity-90
```

All buttons:
- `px-4 py-2.5 text-sm font-medium rounded transition-colors`
- `disabled:opacity-50 disabled:cursor-not-allowed`
- Icon + label: icon `me-2` (RTL-aware)
- Loading state: spinner replaces icon, text stays, button disabled
- Destructive actions (Close ticket, Cancel): always require confirmation modal first

---

## 8. Status Badges

Used everywhere — must be consistent across tickets, tables, and detail views.

Use the `/10` opacity modifier for the background so you only need one token per status:

```html
<!-- Resolved -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold
             bg-status-resolved/10 text-status-resolved">
  <span class="w-1.5 h-1.5 rounded-full bg-status-resolved me-1.5"></span>
  Resolved / محلول
</span>

<!-- On Hold -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold
             bg-status-onhold/10 text-status-onhold">
  <span class="w-1.5 h-1.5 rounded-full bg-status-onhold me-1.5"></span>
  On Hold / معلّق
</span>
```

Apply the same pattern for all statuses using their token:
`status-awaiting` / `status-inprogress` / `status-onhold` / `status-review` /
`status-approval` / `status-action` / `status-resolved` / `status-closed` / `status-cancelled`

Same pattern for priority: `priority-low` / `priority-medium` / `priority-high` / `priority-critical`

**Never use ad-hoc colors for status or priority — always the token.**

---

## 9. Navigation

### Top Nav (64px)
- Logo + system name (right in RTL, left in LTR)
- Breadcrumb center (current location)
- Right side (LTR): notifications bell + user avatar dropdown
- Left side (RTL): reversed
- Notification bell: badge count, dropdown panel with grouped notifications

### Sidebar
- Group items under section headers (`text-xs uppercase tracking-widest text-text-muted px-3 mb-1`)
- Icon must be meaningful — not decorative
- Active state: `border-s-4 border-primary-500 bg-primary-50 text-primary-500`
- Collapsed state on mobile (hidden, toggle via Alpine)

### Breadcrumb
- Always show: Home > Section > Current Page
- Separator: `›` in LTR, `‹` in RTL
- Last item: not a link, bold

---

## 10. Ticket Detail Page

This is the most important page in the app. It must be excellent.

### Layout
```
┌─────────────────────────────────────┬────────────────┐
│ Ticket Header                        │                │
│ #ID  Title                Status    │  Sidebar Panel │
│ Meta: created, requester, category  │  ─────────     │
├─────────────────────────────────────┤  Assignee      │
│ Tabs: Activity │ Details │ Files    │  Priority      │
│                                     │  SLA           │
│ [Tab content area]                  │  Actions       │
│                                     │                │
└─────────────────────────────────────┴────────────────┘
```

### Activity feed
- Timeline format: icon + actor + action + timestamp
- System events (status changes): muted style
- Human comments: card style with avatar
- Newest at bottom (chronological)

### Sidebar panel
- Grouped into sections: Assignment, Classification, Dates, SLA
- Inline edit where permitted (Livewire)
- SLA: show progress bar (red if breached, amber if < 20% remaining, green otherwise)

---

## 11. Empty States

Every empty list/table must have one. Never show a blank area.

```
[Icon — 48px, muted color]
No tickets found
[Sub-text explaining why or what to do]
[Primary action button if applicable]
```

---

## 12. Notifications & Feedback

### Flash messages (post-action)
- Top of content area, auto-dismiss after 4s
- Success: green left border + check icon
- Error: red left border + x icon
- Warning: amber left border + warning icon
- Never use `alert()` or browser dialogs

### Confirmation modals
Required for: Close ticket, Cancel ticket, Delete anything, Reject & Close escalation
- Modal with clear title, consequence explanation, and two buttons: confirm (danger) + cancel (secondary)
- Alpine.js `x-show` + `x-transition` for open/close

### Inline validation
- Trigger on blur, not on every keystroke
- Clear error when user starts typing again

---

## 13. Escalation Form Pages

These pages handle the two-stage escalation. Treat them as formal document workflows.

- Use a stepped progress indicator at the top (Step 1 of 3, etc.)
- Each step: full-width, clean form, no sidebar distractions
- DOCX download buttons: large, clearly labeled in both AR and EN
- Upload zone: styled drag-and-drop, show file name + remove option after upload
- Status-aware: show what step is complete, what's pending, who's responsible for next action

---

## 14. Responsive Behavior

V1 is web-only but must not break on smaller screens.

- Sidebar: collapses to icon-only below `lg`, hidden below `md` (hamburger toggle)
- Tables: horizontal scroll container on mobile — never break layout
- Forms: single column below `md`
- Ticket detail: stack sidebar below main content on mobile

---

## 15. Claude Code Execution Rules

When implementing any UI/UX change, follow this order:

1. **Add `@theme` block to `app.css` first** — nothing else until this is done and compiled
2. **Blade layout first** — get the shell (nav, sidebar, content area) correct before touching components
3. **Component by component** — one component type at a time (all forms, then all tables, then badges, etc.)
4. **No inline styles** — Tailwind token classes only (`bg-primary-500`, not `style="background: #1B3A6B"`)
5. **No arbitrary color values** — `bg-[#1B3A6B]` is forbidden; add to `@theme` instead
6. **Test RTL + LTR** after every component — flip `dir` attribute and verify nothing breaks
7. **No JS where Alpine suffices** — no jQuery, no vanilla DOM manipulation for UI state
8. **Commit after each component type is complete** — not after each file

---

## 16. Files to Refactor (Priority Order)

1. `layouts/app.blade.php` — shell, nav, sidebar
2. `components/` — buttons, badges, inputs, select, textarea, file-upload
3. All `*-index.blade.php` — tables + empty states
4. All `*-create.blade.php` / `*-edit.blade.php` — forms
5. `tickets/show.blade.php` — ticket detail page
6. `escalation/*.blade.php` — escalation workflow pages
7. `dashboard.blade.php` — data tables, summary cards
8. `reports/*.blade.php` — report tables
