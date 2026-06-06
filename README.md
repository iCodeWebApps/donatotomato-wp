# DonatoTomato for WordPress

[![CI](https://github.com/iCodeWebApps/donatotomato-wp/actions/workflows/ci.yml/badge.svg)](https://github.com/iCodeWebApps/donatotomato-wp/actions/workflows/ci.yml)
[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/donatotomato.svg)](https://wordpress.org/plugins/donatotomato/)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/donatotomato.svg)](https://wordpress.org/plugins/donatotomato/)

Add donations to your WordPress site with a no-code site-wide floating Donate button, an inline [DonatoTomato](https://donatotomato.com) widget on any page or post (shortcode or Gutenberg block), or a Donate button that opens the donation form as a pop-up.

Live in the WordPress Plugin Directory: **https://wordpress.org/plugins/donatotomato/**

## What is DonatoTomato?

DonatoTomato is a donation platform built for US nonprofits. Accept one-time and recurring donations through a branded widget connected to your own Stripe account, so your organization stays the merchant of record. No monthly fee and no setup fee — a flat 1% platform fee per donation, on top of Stripe's standard payment processing.

## Installation

1. Install **DonatoTomato** from the WordPress plugin directory (Plugins → Add New → search "DonatoTomato"), or upload the plugin to `/wp-content/plugins/donatotomato/`.
2. Activate the plugin through the **Plugins** menu.
3. Go to **Settings → DonatoTomato** and enter your **Organization ID** (found in your [DonatoTomato dashboard](https://app.donatotomato.com) on any campaign's **Add to your website** panel).

## Usage

The plugin offers three ways to add donations — start with the no-code floating button, or use a shortcode/block for in-page placement.

### Floating Donate button (no code)

Go to **Settings → DonatoTomato → Floating Donate Button**, pick a campaign, set the label, color, size, shape, and position, and enable it. A Donate button then appears site-wide and opens the donation form as a pop-up — no shortcode or block required.

### Inline widget (donation form embedded on a page)

```
[donatotomato campaign="your-campaign-id"]
```

Override the org slug or dimensions for a specific widget:

```
[donatotomato slug="your-org" campaign="your-campaign-id" width="480" height="600"]
```

Gutenberg block: search for **DonatoTomato Widget** in the block inserter. Enter your Campaign ID in the settings panel; the editor shows a configured-state placeholder and the live widget renders on the published page.

### Donate button (pop-up modal trigger)

Drops a button anywhere (nav menu, hero CTA, footer) that opens the donation form as a focal-modal pop-up — the standard donate-button trigger pattern across donation platforms.

```
[donatotomato_button campaign="your-campaign-id"]
[donatotomato_button campaign="your-campaign-id" label="Give now" class="my-custom-class"]
```

Gutenberg block: search for **DonatoTomato Donate Button** in the block inserter. Configure Campaign ID, button label, and optional per-button org-slug override in the settings panel.

The button is powered by a small focal-modal script (`embed.js`, ~2KB gzip) auto-loaded only on pages that contain a Donate button.

## Development

```bash
npm install
npm run build   # production build → build/
npm run start   # watch mode
```

Requires Node.js 18+. The `build/` directory is gitignored — run the build before packaging.

PHP linting (PHPCS + WordPress Coding Standards) runs in CI via composer:

```bash
composer install
composer lint        # check
composer lint:fix    # auto-fix
```

## Releasing a new version

1. Bump the version in all five canonical locations with one command:
   ```bash
   python bin/bump-version.py 1.2.0
   ```
2. Replace the auto-generated `TODO: describe changes` stub in `readme.txt`'s `== Changelog ==` with the real entry.
3. Commit, push, and open a PR. The `CI` workflow runs PHPCS + Plugin Check against the extracted distribution zip.
4. After merge to `main`, tag the release:
   ```bash
   git tag v1.2.0
   git push origin v1.2.0
   ```
5. The `Release` workflow builds the zip and attaches it to a new [GitHub Release](https://github.com/iCodeWebApps/donatotomato-wp/releases).
6. Push the release to WordPress.org SVN (manual until automated):
   ```bash
   cd /path/to/svn-checkout
   cp -r /path/to/extracted-zip/donatotomato/* trunk/
   svn cp trunk tags/1.2.0
   svn ci -m "Release 1.2.0"
   ```

## Requirements

- WordPress 6.0+
- PHP 7.4+
- A free [DonatoTomato account](https://donatotomato.com)

## Support

- [WordPress.org support forum](https://wordpress.org/support/plugin/donatotomato/)
- [GitHub Issues](https://github.com/iCodeWebApps/donatotomato-wp/issues)

## License

GPL-2.0-or-later — see [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
