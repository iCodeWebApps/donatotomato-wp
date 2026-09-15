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

Let donors pick the destination instead of naming one campaign. The form opens on your active campaigns, and `group` narrows that list to a single label, set per campaign in your dashboard:

```
[donatotomato choose="yes"]
[donatotomato choose="yes" group="Missionaries"]
```

`choose="yes"` wins if a campaign is given as well, and `group` applies only while the donor is choosing. If your organization has one active campaign, the donor goes straight to it.

Gutenberg block: search for **DonatoTomato Widget** in the block inserter. Enter your Campaign ID in the settings panel; the editor shows a configured-state placeholder and the live widget renders on the published page.

### Donate button (pop-up modal trigger)

Drops a button anywhere (nav menu, hero CTA, footer) that opens the donation form as a focal-modal pop-up — the standard donate-button trigger pattern across donation platforms.

```
[donatotomato_button campaign="your-campaign-id"]
[donatotomato_button campaign="your-campaign-id" label="Give now" class="my-custom-class"]
[donatotomato_button choose="yes" group="Programs" label="Support a program"]
```

The button takes the same `choose` and `group` attributes as the inline widget: `choose="yes"` opens the pop-up on your active campaigns, and `group` narrows that list to one label.

Gutenberg block: search for **DonatoTomato Donate Button** in the block inserter. Configure Campaign ID, button label, and optional per-button org-slug override in the settings panel.

The button is powered by a small focal-modal script (`embed.js`, ~2KB gzip) auto-loaded only on pages that contain a Donate button.

### Shortcode Builder (Divi, Elementor and other page builders)

Page builders replace the block editor, so the blocks and their campaign picker are out of reach. **Settings → DonatoTomato → Shortcode Builder** covers that case: pick a campaign by name, choose the inline form or a donate button, set width, height, label, the destination picker and its group, then copy a complete shortcode to paste into the builder. It generates the text in your browser and changes no settings.

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

1. Bump every version location with one command:
   ```bash
   python bin/bump-version.py X.Y.Z
   ```
   In `readme.txt`, replace the generated changelog stub with the real entry and add an Upgrade Notice of 300 characters at most. Set `Tested up to` only to a released WordPress version the plugin has been tested on.
2. Open a PR to `main`. CI builds the distribution zip, runs PHPCS, and runs WordPress.org's Plugin Check against the extracted zip. The build fails on any Plugin Check error, and on any warning not listed in `bin/plugin-check-baseline.json`.
3. After merging, tag the merge commit and push the tag. The `Release` workflow builds the zip and attaches it to a new [GitHub Release](https://github.com/iCodeWebApps/donatotomato-wp/releases):
   ```bash
   git tag -a vX.Y.Z -m "vX.Y.Z"
   git push origin vX.Y.Z
   ```
4. Publish to WordPress.org SVN (manual). Copy only the files that changed in this release from the git working tree into `trunk/`; the release zip's line endings differ from the SVN checkout. Confirm the versions agree, then tag and commit:
   ```bash
   python bin/bump-version.py --check /path/to/svn-checkout/trunk
   cd /path/to/svn-checkout
   svn cp trunk tags/X.Y.Z
   svn ci -m "Release X.Y.Z"
   ```
5. Listing artwork (banners, icons and `screenshot-N.png`) lives in the SVN `assets/` directory, next to `trunk/`, and is updated separately from releases.

## Requirements

- WordPress 6.0+
- PHP 7.4+
- A free [DonatoTomato account](https://donatotomato.com)

## Support

- [WordPress.org support forum](https://wordpress.org/support/plugin/donatotomato/)
- [GitHub Issues](https://github.com/iCodeWebApps/donatotomato-wp/issues)

## License

GPL-2.0-or-later — see [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
