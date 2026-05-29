=== DonatoTomato ===
Contributors: dev1consulting
Tags: nonprofit, donations, fundraising, stripe, recurring donations
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 1.4.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add a floating Donate button to every page of your nonprofit site in 60 seconds. No theme edits, no HTML, no developer.

== Description ==

Add a Donate button to every page of your nonprofit site in 60 seconds. No theme edits, no HTML, no developer.

DonatoTomato's WordPress plugin gives you a one-time admin setup — pick a campaign, pick a position, save — and a styled floating Donate button appears site-wide. Clicking it opens your donation form in a focal-modal pop-up. The button is responsive, theme-compatible, and accessible by default.

[DonatoTomato](https://donatotomato.com) is a donation platform built for US nonprofits. Accept one-time and recurring donations through a beautiful, embeddable widget — with automatic tax receipts, donor management, and a 1% platform fee (no monthly cost).

**Three ways to add donations to your site:**

* **Floating Donate button (new in 1.3.0)** — admin-configured, appears on every page automatically. The simplest path.
* **Inline widget** — embed the donation form directly on a page (shortcode or Gutenberg block)
* **Donate button block** — drop a button anywhere on your site (nav, hero, footer) that opens the donation form as a pop-up

**Features:**

* Site-wide floating Donate button — pick a campaign, label, color, size, shape, position; live preview in admin
* Per-page exclusion list — hide the floating button on legal pages, the embedded donation page itself, etc.
* Auto-hide on pages that already contain the inline donation widget — no double-donate-UI confusion
* Mobile-responsive with smaller offset on small screens
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

= 60-second setup (recommended) =

1. Upload the plugin files to `/wp-content/plugins/donatotomato/` or install via the WordPress plugin directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings → DonatoTomato** and enter your Organization Slug (found in your DonatoTomato dashboard).
4. Switch to the **Floating Donate Button** tab.
5. Toggle **Enable floating Donate button** on, pick your campaign from the dropdown, and click **Save Changes**.
6. Visit any page on your site — the Donate button is now anchored to the bottom-right corner, on every page.

= Alternative: place a Donate button or inline form manually =

If you want a Donate button in a specific spot (nav menu, hero section, etc.) or want the donation form embedded inline on a page, use the Gutenberg blocks or shortcodes — see the **Usage** section below.

== Usage ==

There are three widget styles: a **floating Donate button** (admin-configured, site-wide — recommended), an **inline embed** (donation form sits directly on a page), and a **Donate button** block (button anywhere on your site opens the donation form in a pop-up overlay).

= Floating Donate button (new in 1.3.0) =

Configure under **Settings → DonatoTomato → Floating Donate Button**. No shortcodes, no blocks — pick a campaign from the dropdown, tweak label/color/size/shape/position, save. The button appears on every front-end page automatically.

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

Log in to your [DonatoTomato dashboard](https://app.donatotomato.com). The organization slug is in Settings → Embed Code. Campaign IDs are listed on your Campaigns page. The floating Donate button picker shows campaigns by name (you do not need to copy the ID manually).

= Does the floating Donate button work on mobile? =

Yes. The button is fully responsive — on screens 640px wide and below it uses a smaller offset from the screen edge so it does not collide with iOS bottom bars or mobile cookie banners. Tap target remains thumb-sized.

= Can I hide the floating Donate button on certain pages? =

Yes. Under **Settings → DonatoTomato → Floating Donate Button → Visibility**, use the **Hide on these pages** picker to select any pages or posts where the button should not appear (for example, your legal pages, a thank-you page, or the embedded donation page itself). Pages that already contain the inline donation widget auto-hide the floating button by default — no double-donate-UI to confuse donors.

= Will the floating Donate button slow down my site? =

No. The button renders inline in the page footer with about 8KB of JavaScript loaded once site-wide. Nothing is render-blocking, no external CSS frameworks are pulled in, and the donation form itself loads only after the donor clicks the button.

= Can I change the floating Donate button's color and label? =

Yes. Every aspect is admin-configurable under **Settings → DonatoTomato → Floating Donate Button**: button label (with one-click presets like "Donate", "Give Now", "Support Us", "Make a Gift"), color (defaults to your widget's primary color, or pick any hex), size (Small / Medium / Large), shape (Pill / Rounded / Sharp), and position (Bottom right / Bottom left / Top right / Top left). A live preview at the bottom of the settings tab updates as you tweak the form.

= Does this plugin store donor payment information? =

No. All payment processing is handled by Stripe via DonatoTomato. No card data or sensitive donor information is stored on your WordPress site.

= What does DonatoTomato cost? =

DonatoTomato charges a 1% platform fee on donations processed. There is no monthly subscription fee.

= Is this plugin compatible with page builders? =

The shortcode works with any page builder that supports WordPress shortcodes (Elementor, Divi, WPBakery, etc.). The floating Donate button renders via `wp_footer` and works regardless of theme or page builder.

== Third-Party Services ==

This plugin connects to external services operated by DonatoTomato (Dev1 Consulting LLC) and Stripe. By using this plugin you agree to their respective terms and privacy policies.

**DonatoTomato Platform (app.donatotomato.com)**

When a visitor loads a page containing a DonatoTomato widget, their browser loads an iframe from `app.donatotomato.com`. When a page contains a DonatoTomato Donate button (including the site-wide floating Donate button), the browser additionally loads a small focal-modal script (`embed.js`, ~2KB gzip) from `app.donatotomato.com` that opens the donation iframe in a pop-up overlay when the button is clicked. The plugin admin also fetches a list of your campaigns from `app.donatotomato.com` to populate the campaign picker dropdown. Donation form submissions — including donor name, email, and payment details — are transmitted to and processed by DonatoTomato and Stripe. No payment or donor data is stored on your WordPress site.

* Service: https://donatotomato.com
* Terms of Service: https://donatotomato.com/terms
* Privacy Policy: https://donatotomato.com/privacy

**Stripe**

Payment processing is handled by Stripe via the DonatoTomato platform. Stripe's privacy policy applies to all donation transactions.

* Service: https://stripe.com
* Terms of Service: https://stripe.com/legal
* Privacy Policy: https://stripe.com/privacy

== Screenshots ==

1. One-click floating Donate button — appears on every page automatically once enabled.
2. The donation modal opens when the floating button is clicked — same focal-modal pop-up donors see from the Donate Button block.
3. Settings → DonatoTomato — pick a campaign from the live-populated dropdown, set label and styling.
4. Settings → DonatoTomato — position, visibility rules, and live preview of the button.

== Changelog ==

= 1.4.0 =
* New: campaign picker dropdown in both block inspectors (Donation Widget block + Donate Button block) — pick a campaign from a list of names with status badges (Active / Draft / Paused) instead of pasting a UUID. Mirrors the floating-button picker UX.
* New: "Refresh" control next to the picker busts the 5-minute server-side cache so admins see new campaigns immediately.
* New: empty-state notice with a link to **Settings → DonatoTomato** when the Organization Slug is unset.
* New: collapsed **Advanced** disclosure inside both block inspectors preserves the manual UUID-paste path for power users.
* New: first-activation onboarding notice — on the first admin page load after activating the plugin, a dismissible notice points new installers to the Floating Donate Button settings tab. The notice is suppressed if the floating button is already configured (upgrade path), only shows to users with the manage_options capability, and persists dismissal per-user so it does not re-appear after deactivate/reactivate cycles.
* Improved: floating Donate button auto-hide now also detects pages with a raw `<iframe src="https://app.donatotomato.com/widget/...">` pasted into a Custom HTML block or copied from another donation page (in addition to the Gutenberg block and `[donatotomato]` shortcode already detected). Customers no longer need to manually exclude such pages from the floating button.
* Fix: the Gutenberg block delimiter check that drives auto-hide now matches the actual block name `donatotomato/widget` (previously the check was looking for a non-existent `donatotomato/block` delimiter and quietly never matched).
* Existing saved blocks continue to render correctly — the underlying `campaignId` attribute is unchanged.

= 1.3.0 =
* New: site-wide floating Donate button — admin-configured under **Settings → DonatoTomato → Floating Donate Button**, no code required. Pick a campaign, label, color, size, shape, and position; the button appears on every front-end page automatically and opens the existing donation modal on click.
* New: campaign picker dropdown in the admin — shows campaigns by name (no copy-pasting UUIDs) with status badges.
* New: per-page exclusion list and auto-hide on pages that already contain the inline donation widget.
* New: live preview in the admin settings tab updates as you tweak label, color, size, shape, and position — no need to save and reload the front end to see changes.
* Existing Donate Button block, Donation Widget block, `[donatotomato_button]` shortcode, and `[donatotomato]` shortcode are unchanged.

= 1.2.2 =
* New: block editor now flags a malformed Campaign ID inline (UUID format check) and surfaces a "View live preview" link in the inspector when both the Organization Slug and Campaign ID are set. Catches typos and slug/campaign mismatches before publishing — previously the widget would silently render a "Campaign not found" error to donors on the live page.

= 1.2.1 =
* Fix: "DonatoTomato Donate Button" Gutenberg block now registers correctly. In 1.2.0 the block silently failed to register because WordPress's `register_block_type_from_metadata()` only recognises files literally named `block.json`. The block now registers via a manual handle-based path. The `[donatotomato_button]` shortcode was not affected.

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

== Upgrade Notice ==

= 1.4.0 =
The Donation Widget block and Donate Button block now show a campaign picker dropdown in the editor sidebar — pick a campaign by name with status badges, no more pasting UUIDs. Adds a first-activation onboarding notice that points new installers at the Floating Donate Button settings tab, and extends the floating-button auto-hide to also detect raw `<iframe>` embeds. Existing saved blocks continue to render correctly.

= 1.3.0 =
Adds a one-click site-wide floating Donate button — configure once under Settings → DonatoTomato, appears on every page automatically. Existing blocks and shortcodes are unchanged.
