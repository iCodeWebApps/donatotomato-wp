=== DonatoTomato ===
Contributors: dev1consulting
Tags: nonprofit, donations, fundraising, stripe, recurring donations
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 1.2.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed a DonatoTomato donation widget on any page or post. Accepts one-time and recurring donations via Stripe.

== Description ==

[DonatoTomato](https://donatotomato.com) is a donation platform built for US nonprofits. Accept one-time and recurring donations through a beautiful, embeddable widget — with automatic tax receipts, donor management, and a 1% platform fee (no monthly cost).

This plugin lets you add a DonatoTomato widget to any page or post using a shortcode or a Gutenberg block, or drop a Donate button into your site's nav that opens the donation form as a pop-up.

**Features:**

* **Inline widget** — embed the donation form directly on a page (shortcode or Gutenberg block)
* **Donate button** — drop a button in your nav (or anywhere) that opens the donation form as a pop-up overlay (shortcode or Gutenberg block)
* One-time and recurring (monthly) donations
* Automatic tax receipt emails for donors
* Branded with your nonprofit's logo and colors
* No transaction data stored on your WordPress site — all payments handled securely by Stripe

**Requirements:**

* A free [DonatoTomato account](https://donatotomato.com)
* A connected Stripe account (set up inside DonatoTomato)

== Source Code ==

The full, unminified source code for this plugin — including the Gutenberg block source that is compiled into `build/index.js` — is publicly available on GitHub:

**https://github.com/iCodeWebApps/donatotomato-wp**

The repository contains the complete, human-readable source. The compiled production output committed in `build/` is generated entirely from `src/index.js` via the official `@wordpress/scripts` build tool.

**Build instructions:**

1. Clone the repository: `git clone https://github.com/iCodeWebApps/donatotomato-wp.git`
2. Install dependencies: `npm install` (requires Node.js 18+)
3. Build the block: `npm run build` (outputs to `build/`)
4. Or run in watch mode: `npm run start`

There are no third-party developer libraries vendored into this plugin. The only build dependency is `@wordpress/scripts`, which is the official WordPress build tooling.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/donatotomato/` or install via the WordPress plugin directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings → DonatoTomato** and enter your Organization Slug (found in your DonatoTomato dashboard).
4. Add a widget to any page using the shortcode or Gutenberg block.

== Usage ==

There are two widget styles: an **inline embed** (donation form sits directly on a page) and a **Donate button** (button anywhere on your site opens the donation form in a pop-up overlay — perfect for site navigation).

= Inline widget =

**Shortcode:**

`[donatotomato campaign="your-campaign-id"]`

With optional overrides:

`[donatotomato slug="your-org" campaign="your-campaign-id" width="480" height="600"]`

**Gutenberg Block:** Search for "DonatoTomato Widget" in the block inserter (under Embeds). Enter your Campaign ID in the block settings panel.

= Donate button (pop-up) =

**Shortcode:**

`[donatotomato_button campaign="your-campaign-id"]`

With optional overrides:

`[donatotomato_button campaign="your-campaign-id" label="Give now" class="my-custom-class"]`

**Gutenberg Block:** Search for "DonatoTomato Donate Button" in the block inserter (under Embeds). Enter your Campaign ID and optional label.

**Adding to your nav menu:** Most themes support adding a Custom Link or Custom HTML to the menu. Use the shortcode in a Custom HTML block, or paste the rendered HTML directly: `<button type="button" class="donatotomato-button" data-dt-donate="your-campaign-id">Donate</button>` (works only after the plugin is active so the supporting script is loaded).

== Frequently Asked Questions ==

= Where do I find my Organization Slug and Campaign ID? =

Log in to your [DonatoTomato dashboard](https://app.donatotomato.com). The organization slug is in Settings → Embed Code. Campaign IDs are listed on your Campaigns page.

= Does this plugin store donor payment information? =

No. All payment processing is handled by Stripe via DonatoTomato. No card data or sensitive donor information is stored on your WordPress site.

= What does DonatoTomato cost? =

DonatoTomato charges a 1% platform fee on donations processed. There is no monthly subscription fee.

= Is this plugin compatible with page builders? =

The shortcode works with any page builder that supports WordPress shortcodes (Elementor, Divi, WPBakery, etc.).

== Third-Party Services ==

This plugin connects to external services operated by DonatoTomato (Dev1 Consulting LLC) and Stripe. By using this plugin you agree to their respective terms and privacy policies.

**DonatoTomato Platform (app.donatotomato.com)**

When a visitor loads a page containing a DonatoTomato widget, their browser loads an iframe from `app.donatotomato.com`. When a page contains a DonatoTomato Donate button, the browser additionally loads a small focal-modal script (`embed.js`, ~2KB gzip) from `app.donatotomato.com` that opens the donation iframe in a pop-up overlay when the button is clicked. Donation form submissions — including donor name, email, and payment details — are transmitted to and processed by DonatoTomato and Stripe. No payment or donor data is stored on your WordPress site.

* Service: https://donatotomato.com
* Terms of Service: https://donatotomato.com/terms
* Privacy Policy: https://donatotomato.com/privacy

**Stripe**

Payment processing is handled by Stripe via the DonatoTomato platform. Stripe's privacy policy applies to all donation transactions.

* Service: https://stripe.com
* Terms of Service: https://stripe.com/legal
* Privacy Policy: https://stripe.com/privacy

== Screenshots ==

1. Settings page — enter your Organization Slug
2. Gutenberg block in the editor
3. Live donation widget on a published page

== Changelog ==

= 1.2.0 =
* New: `[donatotomato_button]` shortcode and "DonatoTomato Donate Button" Gutenberg block — drop a Donate button anywhere on your site (nav menu, hero, footer) that opens the donation form in a focal-modal pop-up. Powered by `embed.js`, auto-loaded only on pages that include a Donate button.
* New: per-button `label` and `class` attributes for theme integration.
* Existing inline widget block and `[donatotomato]` shortcode are unchanged.

= 1.1.0 =
* Editor: replaced live iframe preview with a configured-state placeholder so the block editor never loads an external origin
* Build/release: parameterized `build-zip.py` to read the version from the plugin header; added `bin/bump-version.py` to update all version locations in one step
* CI: added GitHub Actions workflows for PR validation (PHPCS + Plugin Check on extracted zip) and tag-triggered GitHub Release artifact

= 1.0.2 =
* Documented public source repository and build steps in the readme
* Fixed auto-resize listener event type so embedded widgets resize correctly

= 1.0.1 =
* Fixed missing compiled assets in distributed package

= 1.0.0 =
* Initial release
