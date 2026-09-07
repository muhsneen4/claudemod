# Appyn Pro

A professional, fully admin-controlled child theme for the **Appyn** WordPress theme
(APK / app download sites). Every colour, font, size, spacing, shadow, animation and
layout choice is edited from one panel. Nothing about the design is hardcoded.

* Parent theme: **Appyn 2.0.17** (`Template: appyn`)
* Options screen: **Appyn Pro** in the admin menu, or **Appearance → Appyn Pro options**
* Live preview: **Appearance → Customize → Appyn Pro** (colours, fonts, layout, dark mode)

---

## Install

1. Keep the Appyn parent theme installed and updated. Do not delete it.
2. Copy the `appyn-pro` folder into `wp-content/themes/`, or upload the zip built by
   `tools/build-theme.sh` through **Appearance → Themes → Add New → Upload Theme**.
3. Activate **Appyn Pro**.
4. Open **Appyn Pro** in the admin menu and set your colours, logo, navigation and slides.

Your existing Appyn settings, apps, downloads and posts are untouched: Appyn Pro reads
the parent theme's data and only replaces the presentation.

---

## How it works

```
Theme options panel  →  one option row (appyn_pro_settings)
                     →  CSS custom properties (:root and the dark scope)
                     →  assets/css/theme.css, which never hardcodes a value
```

Every field in `inc/settings-schema.php` may declare a `css_var`. The generator in
`inc/class-apx-css.php` walks the schema, formats each value for its type (colour,
slider + unit, spacing box, border, shadow, gradient) and prints it into the right
selector and media query. Add a field to the schema and it appears in the panel, is
sanitised on save and becomes a design token - no other code to touch.

The generated CSS is cached in a transient and flushed on every save.

### File map

| Path | What it does |
| --- | --- |
| `functions.php` | Bootstraps the child theme |
| `inc/settings-schema.php` | Every option: type, default, range, CSS variable |
| `inc/class-apx-settings.php` | Storage, per-type sanitising, import/export, presets |
| `inc/class-apx-admin.php` | The tabbed options panel and its save handlers |
| `inc/class-apx-css.php` | Turns settings into CSS custom properties |
| `inc/class-apx-frontend.php` | Assets, Google fonts, body classes, preloader, custom code |
| `inc/class-apx-blocks.php` | Hero slider, category bar, navigation, footer pieces |
| `inc/class-apx-customizer.php` | Live preview for the everyday tokens |
| `assets/css/theme.css` | All styling, driven only by the tokens |
| `assets/js/theme.js` | Slider, dark mode, scroll animations, ripples, tilt, download states |
| `header-default.php`, `footer-default.php` | Customizable header and footer |
| `template-parts/loop/app.php` | App card |
| `template-parts/loop/blog-home.php` | News card |

---

## The panel, tab by tab

| Tab | What you control |
| --- | --- |
| **Global** | Brand, state, surface and text colours; container width; global radius; animation speed; master switches for the custom header/footer and cards |
| **Typography** | Heading and body Google font, weights, base size, line height, letter spacing, H1-H3 sizes, font loading strategy |
| **Header** | Top bar (colours, height, links), logo and logo height, header colours, border, shadow, navigation repeater (label, icon, link, colour, hover colour, CSS class, new tab), nav style and spacing, search, user menu, dark switch, sticky behaviour, mobile hamburger, drawer and bottom tab bar |
| **Hero Slider** | On/off, width, per-device height, radius, overlay colour and gradient, autoplay and speed, loop, pause on hover, transition effect and speed, arrows and dots (style, size, colours), and an unlimited slide repeater with image, title, text, button, colours, content position, entrance animation and per-slide CSS |
| **Categories** | Section title and icon, layout (scroll, grid, pills, list), card style, columns, gap, icon size/shape/colour, label and count colours, hover background and movement, scroll arrows, unlimited category repeater, section CSS |
| **App Cards** | Card background, border, radius, padding, shadow; icon size (desktop and mobile), radius, shadow, hover scale; title size/weight/colour/hover/lines; developer name; version and size tags; stars and numeric rating; badge style, position, and per-badge colours (MOD, Premium, Editor's choice, New) with an optional animation; hover lift/scale/shadow/border/background/speed/easing/glow; click scale; grid columns for desktop, tablet and phone; gaps; section headings and the "more" link; entrance animation and stagger |
| **News / Blog** | Section header, layout, columns, card style and colours, image height/radius/fit/hover zoom and filter, category badge, title, excerpt, meta (date, author, read time, views), read-more style, hover lift and shadow |
| **App Detail Page** | Blurred banner (height, overlay gradient, blur, radius, parallax), breadcrumb style and colours, app icon size/radius/shadow/3D tilt, title and developer, MOD text colour, info bar, the download button in every state (normal, hover, pressed, loading, success) with icon animation, loading style, success animation and ripple, secondary buttons, tabs, content boxes and screenshots |
| **Footer** | Background (colour or image), text and link colours, hover effect, padding, top border, columns, column titles and underline, brand column (logo, description), link column repeater, social icon repeater with per-icon colours, bottom bar, copyright with `{year}` and `{site_name}`, and the back-to-top button (shape, size, colours, position, offsets, trigger, animation, shadow) |
| **Animations** | Master switch, reduce-motion respect, motion style, preloader, page entrance, scroll animation (type, duration, offset, distance, easing, once), hover speed, click feedback and ripple colour, skeletons, parallax, 3D tilt, mobile reduction, GPU hints |
| **Dark Mode** | On/off, default mode (light, dark, follow device), switch style and position, transition time, dark logo, and an independent dark value for every surface, text, border, card, header, footer and accent token |
| **Responsive** | Desktop and tablet breakpoints, per-device font scale, touch target size, mobile side padding, disable animations on phones, simplify layout on phones |
| **Custom Code** | Global / desktop / tablet / mobile / header / footer CSS boxes and head + footer JavaScript, all with syntax highlighting |
| **Import / Export** | Export every setting to JSON, import one back, save named presets, load or delete them, reset everything |

---

## Notes worth knowing

**Dark mode is shared with the parent theme.** The switch sets `data-apx-theme` on
`<html>`, writes `apx_theme` to local storage and keeps Appyn's own
`px_light_dark_option` and `#css-dark-theme` stylesheet in step, so both themes agree on
the current mode. The mode is applied in `<head>` before the first paint, so pages never
flash white.

**Nothing is forced on you.** *Global → Use Appyn Pro header & footer* and *Use Appyn Pro
app & news cards* both fall back to the parent templates when off - useful if you only
want the colour system.

**Sanitising.** Every value is validated against its schema entry on save: colours must be
hex or rgb(a), sliders are clamped to their range, selects must be a real choice, repeater
rows drop empty entries, and the CSS/JS boxes cannot close their own tag.

**Custom JavaScript** is printed as written and is only editable by users with
`edit_theme_options`. Treat it like any other admin-level code box.

**Accessibility and speed.** Motion follows `prefers-reduced-motion` when the option is
on, tap targets use the configured minimum size, images added by the theme lazy load, and
the generated CSS is one cached inline block rather than an extra request.

---

## Development

The theme is plain PHP, CSS and JavaScript - no build step. To package it:

```bash
tools/build-theme.sh          # writes dist/appyn-pro.zip
```

To add a new option:

1. Add the field to the right `apx_schema_*()` function in `inc/settings-schema.php`.
2. Give it a `css_var` if it should become a design token.
3. Use `var(--your-token)` in `assets/css/theme.css`.

That is the whole process - the panel, the sanitiser, the exporter and the generator all
read the same schema.
