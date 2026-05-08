# DonatoTomato for WordPress

Embed a [DonatoTomato](https://donatotomato.com) donation widget on any WordPress page or post — via shortcode or Gutenberg block.

## What is DonatoTomato?

DonatoTomato is a donation platform built for US nonprofits. Accept one-time and recurring donations through a branded widget connected to your Stripe account. No monthly fee — just 1% per donation.

## Installation

1. Upload the plugin to `/wp-content/plugins/donatotomato/` or install via the WordPress plugin directory.
2. Activate the plugin through the **Plugins** menu.
3. Go to **Settings → DonatoTomato** and enter your Organization Slug (found in your [DonatoTomato dashboard](https://app.donatotomato.com) under Settings → Embed Code).

## Usage

**Shortcode**

```
[donatotomato campaign="your-campaign-id"]
```

Override the org slug or dimensions for a specific widget:

```
[donatotomato slug="your-org" campaign="your-campaign-id" width="480" height="600"]
```

**Gutenberg Block**

Search for **DonatoTomato Widget** in the block inserter. Select your campaign from the dropdown in the block settings panel.

## Development

```bash
npm install
npm run build   # production build → build/
npm run start   # watch mode
```

Requires Node.js 18+. The `build/` directory is gitignored — run the build before deploying.

## Requirements

- WordPress 6.0+
- PHP 7.4+
- A free [DonatoTomato account](https://donatotomato.com)

## License

GPL-2.0-or-later — see [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
