# MCP Avada Builder Pro

Advanced MCP integration for Avada Fusion Builder with full shortcode parsing and element management.

## Version: 3.3.3

---

## Status & Affiliation

- This is an **independent community project**.
- It is **not affiliated with, endorsed by, or maintained by ThemeFusion/Avada**.
- All product names and trademarks belong to their respective owners.

---

## About This Fork

This is a fork of [wikiwyrhead/mcp-avada-builder-pro](https://github.com/wikiwyrhead/mcp-avada-builder-pro).
All credit for the original plugin goes to its author. This fork adds security
and correctness fixes found while testing the plugin against a real Avada site
(WordPress with Avada 7.16, Fusion Builder 3.16, official `WordPress/mcp-adapter`):

- **Security:** page-fetching abilities only fetch URLs on the site itself (prevents SSRF), and `create-page` only accepts the `page` and `post` post types.
- **Every Avada element:** reads Avada's live element registry, so `add-element` accepts every element Avada registers (142 on Avada 7.16) instead of only the 56 in the static list, with their real parameters (the original returned 0 parameters outside the editor). It also stops parsing nested elements twice.
- **Lossless round-trip:** parsing a page and generating it again returns the same `post_content`, including after structural edits.
- **list-pages:** uses Avada's real builder flag and hides posts the caller cannot edit.
- **Media:** accepts Avada's `id|size` format for `image_id`.
- **Setup:** works with the Abilities API built into WordPress 6.9+ and the official `WordPress/mcp-adapter` plugin; no forked dependency plugins are needed. The setup guide below explains every step.
- **Validation:** `add-element` rejects layout tags early, duplicate detection tells passthrough elements apart, and self-closing shortcodes count as balanced.
- **Version-aware attributes:** `add-element`, `update-element` and `bulk-update` refuse attributes the installed Avada version does not define for the element (Avada would silently drop them), so options from a newer Avada are not written to an older site. Pass `allow_unknown_attributes: true` to override.

The original license (GPL-2.0-or-later) and copyright are unchanged.

---

## Overview

MCP Avada Builder Pro provides comprehensive abilities for controlling Avada Fusion Builder programmatically via the WordPress Abilities API and MCP protocol. Features:

- **Recursive Shortcode Parser** - Handles deeply nested structures including inner rows/columns
- **Full Hierarchy Support** - Container > Row > Column > Element (including inner rows/columns)
- **Every Avada Element** - Reads Avada's live element registry (142 elements and 4,792 parameters on Avada 7.16), plus 56 curated element types with extra descriptions and validation
- **Passthrough Preservation** - Unregistered element types preserved as raw shortcode (no data loss)
- **Path-based Element Access** - Unique paths like `container_0/row_0/column_0/fusion_text_1`
- **Media Field Validation** - Strict validation for image_id, image URLs, gallery IDs
- **Structural Validation** - replace-content validates structure schema before writing
- **Write-path ID Stability** - Canonical paths returned after every write operation
- **Data Loss Guard** - Dry-run validation on all mutations prevents silent element loss
- **Audit Logging** - All mutations logged to post meta with timestamp/user/action
- **Page Structure Health Check** - Validate tag balance, duplicates, round-trip fidelity
- **Shortcode Tree Repair** - Dry-run-first repair for malformed shortcode trees

---

## Abilities (28 Total)

### Core Page + Element Management
- `avada-pro/get-info` - Get builder/theme/plugin versions and element count.
- `avada-pro/get-page-structure` - Get full parsed structure for a page.
- `avada-pro/list-all-elements` - List all page elements (optional type filter).
- `avada-pro/add-container` - Add a container (optionally with row/column).
- `avada-pro/add-element` - Add an element to container/row/column coordinates.
- `avada-pro/update-element` - Update element content/attributes by path.
- `avada-pro/delete-element` - Delete element by path.
- `avada-pro/replace-content` - Replace whole page content from structure object (with structural validation and backup).
- `avada-pro/list-element-types` - List the 56 curated element types. To browse every element in Avada's live registry, use `search-elements` or `list-element-categories`.
- `avada-pro/clean-duplicates` - Remove duplicate elements in a page.

### Layout Operations
- `avada-pro/restructure-layout` - Restructure a target row based on matching content.
- `avada-pro/duplicate-element` - Clone element in place.
- `avada-pro/move-element` - Move element across container/row/column.
- `avada-pro/find-element` - Search page elements by type/content/attribute.
- `avada-pro/bulk-update` - Update multiple elements in one save (transactional, all-or-nothing).
- `avada-pro/select-columns` - Select columns by scoped selector (container label/index, row, column indexes, attrs, contained element type).
- `avada-pro/bulk-update-columns` - Bulk patch column attributes via selector or explicit paths (dry-run supported).
- `avada-pro/clone-column-style` - Clone source column style to targets with preserve-keys (dry-run supported).
- `avada-pro/clone-element-style` - Clone source element style attributes to target elements (dry-run supported).
- `avada-pro/enforce-responsive-policy` - Apply desktop/tablet/mobile column width policy in bulk (dry-run supported).

### Page Lifecycle
- `avada-pro/create-page` - Create draft/published page/post with Avada enabled.
- `avada-pro/list-pages` - List posts/pages where Avada Builder is active.

### Element Schema Introspection
- `avada-pro/get-element-schema` - Get runtime/static parameter schema for a shortcode.
- `avada-pro/get-element-defaults` - Get runtime defaults from FusionSC/runtime params.
- `avada-pro/list-element-categories` - List categories + per-category elements.
- `avada-pro/search-elements` - Search available element types by keyword/category.

### Validation & Repair (v3.2.0)
- `avada-pro/validate-page-structure` - Health check: tag balance, duplicates, round-trip fidelity, builder status. Returns health score.
- `avada-pro/repair-shortcode-tree` - Repair malformed shortcode trees (dry-run by default). Removes duplicates, cleans empty containers, creates backup.

---

## Supported Element Types

The plugin supports **every element registered in your Avada install**. It reads
Avada's live element registry, so it picks up elements added by Avada updates or
add-ons automatically, with no code changes. On Avada 7.16 that is 142 elements
and 4,792 parameters.

- `add-element`, `get-element-schema`, `get-element-defaults`, `search-elements`
  and `list-element-categories` work with the full live registry.
- The 56 element types below also have a curated entry with extra descriptions
  and validation. All other elements use the parameter definitions that Avada
  itself provides.

### Curated Element Types (56)

#### Layout Elements
- `fusion_builder_container` - Main wrapper
- `fusion_builder_row` - Row organizer
- `fusion_builder_column` - Column layout
- `fusion_accordion` - Collapsible sections
- `fusion_toggle` - Single toggle
- `fusion_tabs` - Tabbed interface
- `fusion_tab` - Individual tab
- `fusion_tooltip` - Hover tooltips
- `fusion_popover` - Click popovers
- `fusion_modal` - Popup modals
- `fusion_modal_text_link` - Modal trigger link
- `fusion_viewport` - Visibility control

#### Content Elements
- `fusion_text` - Text blocks
- `fusion_title` - Headings (H1-H6)
- `fusion_button` - Buttons with links
- `fusion_separator` - Visual dividers
- `fusion_alert` - Notification boxes
- `fusion_code` - Code blocks
- `fusion_code_block` - Code block wrapper
- `fusion_syntax_highlighter` - Advanced code
- `fusion_table` - Data tables
- `fusion_tagline` - Callout boxes
- `fusion_checklist` - Styled lists
- `fusion_content_boxes` - Content grids
- `fusion_content_box` - Individual box
- `fusion_blog` - Blog posts
- `fusion_portfolio` - Portfolio items
- `fusion_portfolio_masonry` - Masonry layout
- `fusion_faq` - FAQ sections
- `fusion_counter` - Animated counters
- `fusion_counters` - Multiple counters
- `fusion_progress` - Progress bars
- `fusion_testimonial` - Testimonials
- `fusion_testimonials` - Testimonial slider
- `fusion_pricing_table` - Pricing columns
- `fusion_pricing_column` - Single tier
- `fusion_pricing_price` - Price display
- `fusion_pricing_button` - CTA buttons
- `fusion_person` - Team profiles

#### Media Elements
- `fusion_image` - Images
- `fusion_imageframe` - Image frames with styling and effects
- `fusion_images` - Image carousel
- `fusion_gallery` - Image galleries
- `fusion_video` - Video embeds
- `fusion_audio` - Audio players
- `fusion_map` - Google Maps
- `fusion_fontawesome` - Icons
- `fusion_slider` - Content sliders
- `fusion_post_slider` - Post sliders
- `fusion_rev_slider` - Revolution Slider
- `fusion_layerslider` - LayerSlider
- `fusion_fusion_slider` - Fusion Slider
- `fusion_post_grid` - Post grids

#### Typography Elements
- `fusion_google_fonts` - Font imports
- `fusion_highlight` - Text highlighting
- `fusion_dropcap` - Large first letters

#### Social Elements
- `fusion_social_links` - Social icons

---

## Column Types

All Avada column layouts supported:
`1_1`, `1_2`, `2_3`, `1_3`, `3_5`, `1_4`, `3_4`, `1_5`, `2_5`, `4_5`, `1_6`, `5_6`

---

## Element Paths

Each element has a unique path:
```
container_{index}/row_{index}/column_{index}/{element_type}_{index}
```
Example: `container_0/row_0/column_0/fusion_text_2`

After write operations, a `canonical_path` is returned that reflects the actual persisted path (stable across save cycles).

---

## Safety Features

- **Data Loss Guard** - Every mutation validates shortcode tag counts before/after write. Blocks operations that would lose elements.
- **Structural Validation** - `replace-content` validates container/row/column hierarchy before accepting.
- **Media Validation** - `image_id` verified against media library, URLs validated, gallery IDs checked.
- **Transactional Bulk Updates** - All-or-nothing validation: either all updates pass or none are applied.
- **Automatic Backups** - `replace-content` and `repair-shortcode-tree` store backups in post meta before destructive operations.
- **Audit Logging** - All mutations logged with timestamp, user, ability, and action description (last 100 events per post).
- **Passthrough Elements** - Unregistered shortcodes are preserved as raw text during parse/generate cycles.

---

## Requirements

| Component | Version | Notes |
|---|---|---|
| WordPress | 6.9+ | The Abilities API is built into WordPress core since 6.9. **No Abilities API plugin is needed.** |
| PHP | 7.4+ | |
| Avada theme | 7.0+ | Tested with Avada 7.16.4. |
| Avada Builder (Fusion Builder) + Fusion Core | 3.0+ | Tested with Avada Builder 3.16.4. |
| [MCP Adapter](https://github.com/WordPress/mcp-adapter) | 0.7+ | The **official** WordPress plugin that turns abilities into MCP tools. Tested with 0.7.1. |

> **Upgrading from the original setup?** The original instructions used forked
> `abilities-api` and `mcp-adapter` plugins. On WordPress 6.9+ you do not need
> them: deactivate and delete the `abilities-api` plugin, and replace the forked
> `mcp-adapter` with the official one.

## Setup

### 1. Install the MCP Adapter

1. Open https://github.com/WordPress/mcp-adapter/releases and download `mcp-adapter.zip` from the **Assets** of the latest release.
2. In WordPress, go to **Plugins → Add New Plugin → Upload Plugin**, choose the zip and click **Install Now**.
3. Click **Activate**.

### 2. Install this plugin

1. On this repository's GitHub page, click **Code → Download ZIP**.
2. Upload it the same way (**Plugins → Add New Plugin → Upload Plugin**) and activate it.

The abilities register automatically. There is nothing to configure.

### 3. Create an Application Password

The AI agent signs in to WordPress as a real user, using an Application Password
(a separate password you can revoke at any time without touching the real one).

1. Use a user that can edit pages (**Editor** or **Administrator**). A dedicated user just for the agent is recommended.
2. Go to **Users → Profile**, scroll to **Application Passwords**, type a name (for example `mcp-agent`) and click **Add New Application Password**.
3. Copy the password now. WordPress shows it only once.

> WordPress only offers Application Passwords on sites served over **HTTPS**,
> or on local sites with `define( 'WP_ENVIRONMENT_TYPE', 'local' );` in `wp-config.php`.

### 4. Check that it works

Replace the site, user and password with your own:

```bash
curl -u "USER:APPLICATION PASSWORD" "https://your-site.com/wp-json/wp-abilities/v1/abilities?per_page=100"
```

The response must include abilities whose name starts with `avada-pro/`.

### 5. Connect your AI agent

The MCP server lives at:

```
https://your-site.com/wp-json/mcp/mcp-adapter-default-server
```

It uses the **Streamable HTTP** transport with HTTP Basic authentication. The
header value is `Basic ` followed by `USER:APPLICATION PASSWORD` encoded in Base64.
To get the Base64 text:

```powershell
# Windows PowerShell
[Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes("USER:APPLICATION PASSWORD"))
```

```bash
# macOS / Linux
printf '%s' 'USER:APPLICATION PASSWORD' | base64
```

**Claude Code:**

```bash
claude mcp add --transport http avada https://your-site.com/wp-json/mcp/mcp-adapter-default-server --header "Authorization: Basic BASE64_TEXT"
```

**Other clients** that support Streamable HTTP with custom headers (JSON config):

```json
{
  "mcpServers": {
    "avada": {
      "type": "http",
      "url": "https://your-site.com/wp-json/mcp/mcp-adapter-default-server",
      "headers": { "Authorization": "Basic BASE64_TEXT" }
    }
  }
}
```

The default MCP Adapter server exposes three tools: `mcp-adapter-discover-abilities`,
`mcp-adapter-get-ability-info` and `mcp-adapter-execute-ability`. The agent uses
them to find the `avada-pro/*` abilities and run them.

> **Security:** an Application Password acts as that user. Keep it out of shared
> files and repositories, and revoke it in **Users → Profile** when you no longer need it.

### REST API (without MCP)

The abilities can also be called directly through the WordPress core REST API:

- `GET /wp-json/wp-abilities/v1/abilities` — list abilities
- `GET|POST /wp-json/wp-abilities/v1/abilities/avada-pro/{ability}/run` — run an ability (POST body: `{"input": {...}}`)

---

## Contributor Quick Start

1. Fork the repository.
2. Create a branch from `main`.
3. Make focused changes.
4. Run PHP lint:
   - `php -l mcp-avada-builder-pro.php`
   - `php -l includes/class-avada-parser.php`
   - `php -l includes/class-avada-elements.php`
5. Open a PR using the template.

See:
- [CONTRIBUTING.md](CONTRIBUTING.md)
- [SECURITY.md](SECURITY.md)
- [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)

## Structured Editing Patterns (Examples)

### 1) Source-of-truth card replication (columns)
- Use `clone-column-style` with:
  - `source_column_path`: e.g. `container_5/row_0/column_1`
  - `selector`: scoped to target section
  - `preserve_attributes`: e.g. `type`, `type_medium`, `type_small`, `first`, `last`, `class`, `id`
  - `dry_run: true` first, then `false`

### 2) Shadow synchronization across many cards
- Use `bulk-update-columns`:
  - `selector`: target cards
  - `column_attributes`: `{ "box_shadow_horizontal": "2", "box_shadow_vertical": "2" }`
  - `dry_run: true` first

### 3) Responsive stacking normalization
- Use `enforce-responsive-policy`:
  - `desktop: "keep"`
  - `tablet: "1_2"`
  - `mobile: "1_1"`
  - selector scoped to target card set

### 4) Element-level style cloning (icon/title/image)
- Use `clone-element-style`:
  - `source_element_path`: e.g. first card `fusion_title`/`fusion_fontawesome`/`fusion_image` element
  - `selector.element_type`: target same element type
  - `preserve_attributes`: keep content identity keys (e.g. image/link/text-specific attrs)
  - `dry_run: true` first

---

## Changelog

### 3.3.3
- Fixed parser behavior so `fusion_image` shortcodes inside `fusion_images` are not parsed as standalone elements.
- Prevented duplicate large image blocks from being reintroduced below image carousels after structured page updates.
- Hardened carousel edit workflows for repeatable builder-safe mutations.

### 3.3.1
- Added `avada-pro/clone-element-style` for source-of-truth element attribute cloning with preserve-key controls.
- Added `avada-pro/enforce-responsive-policy` for bulk desktop/tablet/mobile width policy enforcement.
- Added selector helper for structured element targeting in style-clone workflows.
- Added dry-run-first workflow examples for bulk styling and responsive normalization.
- Added OSS baseline docs and GitHub templates (`LICENSE`, `CONTRIBUTING`, `SECURITY`, issue/PR templates, CI PHP lint workflow).

### 3.3.0
- Added `avada-pro/select-columns` for scoped column querying by container label/index, row/index, attrs, and element presence.
- Added `avada-pro/bulk-update-columns` with dry-run before/after diffs.
- Added `avada-pro/clone-column-style` with preserve-key controls and dry-run preview.

### 3.2.0
- **Parser fixes**: Fixed all prefix-collision regex patterns (fusion_image/imageframe/images, tab/tabs, counter/counters, content_box/content_boxes, etc.)
- **Parser fixes**: Container parser now uses recursive `parse_nested_shortcode` instead of non-greedy regex
- **Media validation**: Added strict validation for image_id, image URLs, gallery image_ids in add-element and update-element
- **Write-path stability**: All write operations now return `canonical_path` from re-read after save
- **replace-content hardened**: Structural schema validation, empty content rejection, automatic backup before replace
- **New ability**: `validate-page-structure` - Health check with tag balance, duplicates, round-trip fidelity
- **New ability**: `repair-shortcode-tree` - Dry-run-first repair with duplicate removal and empty container cleanup
- **Registry**: Added `fusion_imageframe` to static elements registry (56 types total)
- **Version**: Bumped to 3.2.0 (23 abilities total)

### 3.1.2
- Hardened permission callbacks with input validation on all 12 post-specific abilities
- Added element type and attribute validation helpers
- Transactional safety for bulk-update (all-or-nothing validation)
- Audit logging system for all mutation operations

### 3.1.0
- Added schema introspection abilities: `get-element-schema`, `get-element-defaults`, `list-element-categories`, `search-elements`

### 3.0.0
- Added advanced editing abilities: `create-page`, `list-pages`, `duplicate-element`, `move-element`, `find-element`, `bulk-update`, `restructure-layout`

### 2.0.0
- Initial Pro release with parser, hierarchy management, and core CRUD abilities.

---

## Author

**arnelG**
- GitHub: @wikiwyrhead

---

## License

GNU General Public License v2.0 or later (GPL-2.0-or-later).  
See [LICENSE](LICENSE).
