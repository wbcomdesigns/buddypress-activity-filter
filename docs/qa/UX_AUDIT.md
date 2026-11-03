# BuddyPress Activity Filter — UX Audit

> **Per-surface check.** Every view × every persona × every viewport × every theme mode.
> Run when a release touches UI, or at least once per minor version.

The goal: catch silent surface regressions (broken spacing, wrong colour token, hover/focus state stripped by the theme, dark-mode bleed, mobile overflow) before a customer notices.

**The pass/fail gate is what the screen looks like.** Render each surface, take the screenshot, and look at it. HTTP 200, a DOM node being present, a clean grep, and a well-shaped JSON response are none of them visual verification. Accessibility is secondary polish audited after the render is right.

## Axes

| Axis | Values |
|------|--------|
| **Persona** | Anonymous, Member, Admin (no moderator surface in this plugin) |
| **Viewport** | Desktop 1440px, Mobile 390px (tablet 1024px spot-check) |
| **Theme mode** | Light, Dark |
| **Theme** | BuddyX, Reign, one default block theme (TT4/TT5) |
| **Browser** | Chromium primary; Firefox + Safari iOS go in `manual_required[]` |

Don't re-audit identical cells every release — audit what changed, plus anything a regression guard flagged last time.

## Dark mode is explicit-only

Dark is scoped to `[data-bx-mode="dark"]` and `body.buddyx-dark-theme` — **never** `prefers-color-scheme` / OS / system. That is an owner decision, not an oversight. Verifying dark by emulating the OS colour scheme tests something this plugin deliberately does not implement.

Two traps that have cost bounces:

- The selector is `[data-bx-mode="dark"]`, **not** `buddyx-dark-mode`.
- Reign 8 sets `data-bx-mode` but emits **no** `--bx-color-*` tokens. Any dark-scoped `var()` must fall back to a dark literal, or Reign renders light-on-light.

---

## Surfaces

This plugin has four admin surfaces and one front-end surface. That is the complete list.

### 1. Settings shell (`includes/admin/views/shell.php`)

The card panel that wraps every tab: header card, sidebar nav, body slot, save bar.

- [ ] Renders at 1440px — no horizontal scrollbar
- [ ] Renders at 390px — no horizontal scrollbar, sidebar collapses rather than overflowing the card
- [ ] Typography inherits the admin font stack, not a hardcoded family
- [ ] Spacing via `--bpaf-admin-*` tokens; no raw px in new rules
- [ ] Colours via tokens; no raw hex
- [ ] Logical properties (`margin-inline-*`), not `margin-left/right` — this is what makes RTL work
- [ ] Sidebar nav marks the current tab, and only the current tab, as selected
- [ ] The docs link in the sidebar resolves (`doc_category/bp-activity-filter/`)

### 2. Default Filters tab (`settings-default.php`)

- [ ] Both selects (site-wide, profile) are full-width and legible at 390px — not squeezed to the browser's default ~180px
- [ ] The stored value is preselected on load, including a value BuddyPress does not itself list
- [ ] Save bar reachable without horizontal scroll at 390px
- [ ] Save round-trips, and does not wipe Hidden Activities

### 3. Hidden Activities tab (`settings-hidden.php`)

- [ ] Row layout stacks under 480px; the label does not squeeze into a narrow column
- [ ] Tap targets ≥ 40px
- [ ] `activity_update` / `activity_comment` render as protected, with the reason visible — not silently missing
- [ ] Empty state (no hideable types) is the canonical empty-state primitive with guidance, not a bare sentence or blank table
- [ ] Checked/unchecked state is distinguishable without relying on colour alone

### 4. FAQ + Discover tabs (`faq.php`, `discover.php`)

- [ ] No save bar on either (neither owns a setting)
- [ ] All nine Discover links resolve; each card renders its icon and description at both viewports
- [ ] External links carry `target="_blank" rel="noopener noreferrer"`

### 5. Front-end filter dropdown (`#activity-filter-by`)

The only front-end surface. It is BuddyPress's own control; this plugin edits its options and its selected value.

- [ ] Selected option matches the stream that actually rendered. A filtered stream under a dropdown reading "Everything" is a failure, not cosmetic
- [ ] Hidden types are absent from the options on **both** Legacy and Nouveau
- [ ] Friendships resolve under the combined key `friendship_accepted,friendship_created`
- [ ] Renders correctly on the group activity tab, where the site-wide default must NOT apply
- [ ] Usable at 390px

---

## Interactive states

Every `<a>`, `<button>`, and form input on the surfaces above:

- [ ] **default** — visible, legible, correct colour
- [ ] **hover** — discoverable change
- [ ] **focus-visible** — clear ring, sufficient contrast, not suppressed by the theme
- [ ] **active** — visual feedback on click
- [ ] **disabled** — clearly distinguishable, `cursor: not-allowed`
- [ ] **visited** (links) — checked explicitly; themes override this and it is easy to miss

**Two specificity traps, both of which have shipped bugs:**

1. `.buddypress .buddypress-wrap button` is specificity (0,2,1) and restyles every plugin button flat/white. BuddyPress's sheet loads later, so a plugin rule that merely **ties** that specificity loses. It must exceed it.
2. A generic `.wrapper a { color }` rule beats `.btn-primary` and paints a button's label in its own background colour, which reads as "button text missing". Compare computed `color` against computed `backgroundColor` before blaming the theme.

## Accessibility (after the render is right)

- [ ] Tab order logical across the sidebar and into the body
- [ ] Icon-only controls carry `aria-label`
- [ ] Every input has a `<label>` (or `aria-label` / `aria-labelledby`)
- [ ] Hidden Activities is semantic list or table markup, not styled divs
- [ ] Body text contrast ≥ 4.5:1, large text ≥ 3:1, in light AND dark
- [ ] `prefers-reduced-motion` respected
- [ ] A JS-toggled `hidden` attribute is not the only mechanism hiding anything — `[hidden]` loses to any rule that sets `display`, and WordPress core's `forms.css` re-shows selects at ≤ 782px, which makes the bug mobile-only and invisible to grep

## RTL

- [ ] Every surface renders right-to-left without overflow at both viewports
- [ ] Icons mirror where directional; brand glyphs stay untransformed

## Recording a cell

One row per audited cell: surface, persona, viewport, theme, mode, screenshot path, verdict, note. File findings that are visual regressions as Basecamp cards on project `37595485`; file design-system drift against the `ux-audit` skill's cleanup playbook.
