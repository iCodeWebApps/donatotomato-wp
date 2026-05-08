=== DonatoTomato ===
Contributors: donatotomato
Tags: nonprofit, donations, fundraising, stripe, recurring donations
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed a DonatoTomato donation widget on any page or post. Accepts one-time and recurring donations via Stripe.

== Description ==

[DonatoTomato](https://donatotomato.com) is a donation platform built for US nonprofits. Accept one-time and recurring donations through a beautiful, embeddable widget — with automatic tax receipts, donor management, and a 1% platform fee (no monthly cost).

This plugin lets you add a DonatoTomato widget to any page or post using a shortcode or a Gutenberg block.

**Features:**

* Add widgets via shortcode or Gutenberg block
* One-time and recurring (monthly) donations
* Automatic tax receipt emails for donors
* Branded with your nonprofit's logo and colors
* No transaction data stored on your WordPress site — all payments handled securely by Stripe

**Requirements:**

* A free [DonatoTomato account](https://donatotomato.com)
* A connected Stripe account (set up inside DonatoTomato)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/donatotomato/` or install via the WordPress plugin directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings → DonatoTomato** and enter your Organization Slug (found in your DonatoTomato dashboard).
4. Add a widget to any page using the shortcode or Gutenberg block.

== Usage ==

**Shortcode:**

`[donatotomato campaign="your-campaign-id"]`

With optional overrides:

`[donatotomato slug="your-org" campaign="your-campaign-id" width="480" height="600"]`

**Gutenberg Block:**

Search for "DonatoTomato Widget" in the block inserter (under Embeds). Enter your Campaign ID in the block settings panel.

== Frequently Asked Questions ==

= Where do I find my Organization Slug and Campaign ID? =

Log in to your [DonatoTomato dashboard](https://app.donatotomato.com). The organization slug is in Settings → Embed Code. Campaign IDs are listed on your Campaigns page.

= Does this plugin store donor payment information? =

No. All payment processing is handled by Stripe via DonatoTomato. No card data or sensitive donor information is stored on your WordPress site.

= What does DonatoTomato cost? =

DonatoTomato charges a 1% platform fee on donations processed. There is no monthly subscription fee.

= Is this plugin compatible with page builders? =

The shortcode works with any page builder that supports WordPress shortcodes (Elementor, Divi, WPBakery, etc.).

== Screenshots ==

1. Settings page — enter your Organization Slug
2. Gutenberg block in the editor
3. Live donation widget on a published page

== Changelog ==

= 1.0.0 =
* Initial release
