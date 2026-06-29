=== Nera – Responsible Play ===
Contributors: Nera
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
WC requires at least: 8.0
WC tested up to: 9.0
Stable tag: 1.0.0
License: GPLv2 or later

Responsible-play / player-protection signposting for WooCommerce competition sites.

== Description ==

Auto-creates an editable **Help & support** page and surfaces responsible-play
signposts at six touchpoints, all configurable without touching the theme:

= Admin (CMS) =
Theme Settings → **Nera Features** → **Responsible Play**:

* **Intro Copy** — wysiwyg guidance shown at the top of the Help page.
* **Footer / Account / Checkout / Account-close** toggles — enable or disable
  each signpost surface independently.
* **Support Services** — repeater of organisations (name, blurb, phone, URL).
  Four defaults are seeded on activation; admins can add, edit, or reorder.

= 6 surfaces =

1. **Help & support page** — auto-created on activation, rendered by a dynamic
   block (`nera/responsible-play`) or shortcode (`[nera_responsible_play]`).
   Self-heals if trashed.

2. **Footer strip** — slim "Responsible play" link on every page (via wp_footer).

3. **My Account menu** — "Need support?" item before the Logout link.

4. **Checkout signpost (clause 1.4)** — brief support link at checkout when the
   customer is over their voluntary spending limit (requires
   nera-spending-amount-limit-plugin; fully inert when absent).

5. **Account-close toast (clause 1.5)** — support notice surfaced as a WooCommerce
   toast after the customer closes their account.

6. **Services directory** — the canonical service list used by all surfaces above,
   rendered by a single class (Nera_RP_Services::render_directory()).

== Notes ==

* Pure standalone plugin — zero edits to the theme or any other plugin.
* HPOS-compatible (no order meta accessed).
* ACF Pro required for admin settings UI. All getters return safe defaults when
  ACF is absent.

== Changelog ==

= 1.0.0 =
* Initial release.
