# nera-responsible-play-plugin — Wave 2 Contract

This document is the authoritative integration contract for Wave 2 agents filling
the stub leaf classes.  Each agent edits exactly ONE file and must not touch the
main plugin file or any other class file.

---

## Constants & globals

| Constant | Value |
|---|---|
| `NERA_RP_VERSION` | `'1.0.0'` |
| `NERA_RP_PLUGIN_FILE` | `__FILE__` of main plugin |
| `NERA_RP_PLUGIN_DIR` | `plugin_dir_path(__FILE__)` |
| `NERA_RP_PLUGIN_URL` | `plugin_dir_url(__FILE__)` |

Text domain: `nera-responsible-play-plugin`  
CSS class prefix: `.nera-rp-`  
ACF options post_id: `'nera-features'`  
Init hook: `plugins_loaded` priority 20, function `nera_rp_init()`  
HPOS compat: declared via `FeaturesUtil::declare_compatibility('custom_order_tables', …, true)`

---

## Init order inside `nera_rp_init()`

```php
Nera_RP_Settings::init();
Nera_RP_Services::init();
Nera_RP_Page::init();
if ( class_exists( 'WooCommerce' ) ) {
    Nera_RP_Account::init();
    Nera_RP_Checkout::init();
    Nera_RP_Account_Close::init();
    Nera_RP_Assets::init();
    Nera_RP_Footer::init();
}
```

Activation hook (file scope): `register_activation_hook(NERA_RP_PLUGIN_FILE, ['Nera_RP_Page','activate'])`

---

## ACF field group

Group key: `group_nera_rp`  
Location: `options_page == nera-features`

| Field name | Key | Type | Default |
|---|---|---|---|
| `nera_rp_intro_copy` | `field_nera_rp_intro_copy` | wysiwyg | (built-in paragraph) |
| `nera_rp_sp_footer` | `field_nera_rp_sp_footer` | true_false (ui) | 1 |
| `nera_rp_sp_account` | `field_nera_rp_sp_account` | true_false (ui) | 1 |
| `nera_rp_sp_checkout` | `field_nera_rp_sp_checkout` | true_false (ui) | 1 |
| `nera_rp_sp_close` | `field_nera_rp_sp_close` | true_false (ui) | 1 |
| `nera_rp_services` | `field_nera_rp_services` | repeater (button: "Add service") | — |
| → `name` | `field_nera_rp_services_name` | text, required | — |
| → `blurb` | `field_nera_rp_services_blurb` | text | — |
| → `phone` | `field_nera_rp_services_phone` | text | — |
| → `url` | `field_nera_rp_services_url` | url | — |

Options: `nera_rp_help_page_id` (int), `nera_rp_seeded` ('1').

---

## Fully-implemented classes (Wave 1 — do not modify)

### `Nera_RP_Settings` — `includes/class-settings.php`

```php
public static function init(): void
    // hooks: acf/init → register_options_page, register_fields

public static function register_options_page(): void
    // idempotent parent page + nera-features sub-page

public static function register_fields(): void
    // acf_add_local_field_group( group_nera_rp … )

public static function is_signpost_enabled( string $which ): bool
    // $which ∈ 'footer'|'account'|'checkout'|'close'
    // default TRUE when ACF absent or field null (treat unset as enabled)

public static function help_page_id(): int
    // (int) get_option('nera_rp_help_page_id', 0)

public static function intro_copy(): string
    // get_field('nera_rp_intro_copy','nera-features') with DEFAULT_INTRO_COPY fallback
```

### `Nera_RP_Services` — `includes/class-services.php`

```php
public static function init(): void
    // empty (nothing to hook; shortcode/block live in Page)

public static function default_services(): array
    // returns 4 hardcoded rows:
    //   Citizens Advice | 0800 144 8848 | https://www.citizensadvice.org.uk
    //   National Debtline (Money Advice Trust) | 0808 808 4000 | https://www.nationaldebtline.org
    //   Samaritans | 116 123 | https://www.samaritans.org
    //   Mind | 0300 123 3393 | https://www.mind.org.uk

public static function get_services(): array
    // reads get_field('nera_rp_services','nera-features'), falls back to default_services()
    // sanitizes: sanitize_text_field name/blurb, esc_url_raw url, phone → /[^0-9+\s()\-]/

public static function render_directory( array $args = [] ): string
    // args: intro (bool), notice (bool), heading (string)
    // notice=true → compact wp_kses_post-safe markup (<a><ul><li><strong><br>)
    // notice=false → full .nera-rp-directory div with optional intro, heading, list
    // phone rendered as <a href="tel:+...">
    // url rendered as <a href rel="noopener noreferrer" target="_blank">
    // returns ESCAPED HTML (never echoes directly)
```

### `Nera_RP_Page` — `includes/class-page.php`

```php
public static function init(): void
    // add_shortcode('nera_responsible_play', render_shortcode)
    // add_action('init', register_block)
    // add_action('admin_init', maybe_heal_page)

public static function render_shortcode(): string
    // returns Nera_RP_Services::render_directory(['intro'=>true])

public static function register_block(): void
    // register_block_type('nera/responsible-play', api_version:3, render_callback: render_block)

public static function render_block(): string
    // returns Nera_RP_Services::render_directory(['intro'=>true])

public static function activate(): void  // STATIC — called by register_activation_hook
    // idempotent: skip if stored ID is live page; adopt published 'help-and-support' slug if exists;
    // else wp_insert_post page titled 'Help and support', content '<!-- wp:nera/responsible-play /-->'
    // stores id in nera_rp_help_page_id; calls maybe_seed_services()

public static function maybe_seed_services(): void
    // guard nera_rp_seeded !== '1'; if update_field absent register deferred acf/init hook
    // update_field('nera_rp_services', default_services(), 'nera-features')
    // set nera_rp_seeded = '1'

public static function maybe_heal_page(): void
    // if stored page missing/trashed, call activate()
```

### `Nera_RP_Assets` — `includes/class-assets.php`

```php
public static function init(): void
    // add_action('wp_enqueue_scripts', enqueue, 20)

public static function enqueue(): void
    // skip if is_admin(); register+enqueue assets/css/responsible-play.css (NERA_RP_VERSION) site-wide
```

---

## Stub classes — Wave 2 fills these

### `Nera_RP_Footer` — `includes/class-footer.php`

```php
public static function init(): void
    // Already wired: add_action('wp_footer', render, 50)
    // Wave 2: no init changes needed

public static function render(): void
    // Guards: is_signpost_enabled('footer'), help_page_id() > 0, !is_admin()
    // Output: <div class="nera-rp-footer-strip">…<a href="[help_page_url]">Responsible play</a>…</div>
    // Escape: esc_url(get_permalink(…)), esc_html() for text
```

### `Nera_RP_Account` — `includes/class-account.php`

```php
const ENDPOINT_KEY = 'nera-support';

public static function init(): void
    // Already wired: add_filter('woocommerce_account_menu_items', add_menu_item, 20)
    //                add_filter('woocommerce_get_endpoint_url', endpoint_url, 10, 4)

public static function add_menu_item( array $items ): array
    // Guards: is_signpost_enabled('account'), help_page_id() > 0
    // Insert key 'nera-support' => __('Need support?') BEFORE 'customer-logout'
    // Return modified $items

public static function endpoint_url( $url, $endpoint, $value, $permalink ): string
    // If $endpoint === 'nera-support' return esc_url(get_permalink(help_page_id()))
    // Otherwise return $url unchanged
```

### `Nera_RP_Checkout` — `includes/class-checkout.php`

```php
const SIGNPOST_ID = 'nera-rp-checkout-signpost';

public static function init(): void
    // Already wired: add_action('woocommerce_checkout_before_terms_and_conditions', render_signpost, 20)
    //                add_filter('woocommerce_update_order_review_fragments', fragment, 20)

public static function render_signpost(): void
    // Guards (ALL required):
    //   Nera_RP_Settings::is_signpost_enabled('checkout')
    //   is_user_logged_in()
    //   class_exists('Nera_SL_User_Limit') && method_exists('Nera_SL_User_Limit','evaluate_for_user')
    //   $total = WC()->cart ? (float)WC()->cart->get_total('edit') : 0.0
    //   $eval = Nera_SL_User_Limit::evaluate_for_user(get_current_user_id(), $total)
    //   is_array($eval) && !empty($eval['has_limit'])
    //   in_array($eval['state'], ['over_soft','over_blocked'], true)
    // Output: <div id="nera-rp-checkout-signpost"><div class="nera-rp-checkout-signpost">…link to help page…</div></div>
    // When guards fail: echo empty wrapper <div id="nera-rp-checkout-signpost"></div> (fragment stability)

public static function fragment( array $fragments ): array
    // Capture render_signpost() output via ob_start/ob_get_clean()
    // $fragments['#nera-rp-checkout-signpost'] = captured_html
    // Return $fragments
```

### `Nera_RP_Account_Close` — `includes/class-account-close.php`

```php
public static function init(): void
    // Already wired: add_action('template_redirect', maybe_add_support_notice, 1)

public static function maybe_add_support_notice(): void
    // Guards (ALL required):
    //   Nera_RP_Settings::is_signpost_enabled('close')
    //   isset($_GET['nera_account_closed']) && '1' === (string)$_GET['nera_account_closed']
    //   !is_user_logged_in()
    //   function_exists('wc_add_notice')
    // Action:
    //   wc_add_notice(Nera_RP_Services::render_directory(['notice'=>true]), 'notice')
    //   NO redirect, NO exit — lets theme's pri-1 flash handler run next
```

---

## Security rules (apply to every method)

- Escape ALL output: `esc_html()` for text, `esc_attr()` for attributes, `esc_url()` for URLs.
- No front-end write endpoints; no nonces needed (read-only front end; ACF handles settings).
- Sanitize on read (already done in `get_services()`).
- HPOS-safe (no order meta accessed in this plugin).

---

## CSS classes (responsible-play.css already ships these)

| Selector | Usage |
|---|---|
| `.nera-rp-footer-strip` | Footer strip container |
| `.nera-rp-directory` | Page/block directory wrapper |
| `.nera-rp-directory__intro` | Intro copy div |
| `.nera-rp-directory__heading` | Services heading |
| `.nera-rp-directory__list` | `<ul>` of services |
| `.nera-rp-directory__item` | `<li>` per service |
| `.nera-rp-directory__name` | Service name `<strong>` |
| `.nera-rp-directory__blurb` | One-line description `<span>` |
| `.nera-rp-directory__contacts` | Phone + URL row `<span>` |
| `.nera-rp-directory__phone` | `<a href="tel:…">` |
| `.nera-rp-directory__link` | `<a href="https://…">` |
| `#nera-rp-checkout-signpost` | Fragment wrapper (ID — stable for AJAX) |
| `.nera-rp-checkout-signpost` | Inner checkout signpost div |

---

## Files owned by Wave 2

Each agent edits exactly ONE of:

| File | Class | Methods to implement |
|---|---|---|
| `includes/class-footer.php` | `Nera_RP_Footer` | `render()` |
| `includes/class-account.php` | `Nera_RP_Account` | `add_menu_item()`, `endpoint_url()` |
| `includes/class-checkout.php` | `Nera_RP_Checkout` | `render_signpost()`, `fragment()` |
| `includes/class-account-close.php` | `Nera_RP_Account_Close` | `maybe_add_support_notice()` |

Wave 2 agents MUST NOT touch:
- `nera-responsible-play-plugin.php`
- `includes/class-settings.php`
- `includes/class-services.php`
- `includes/class-page.php`
- `includes/class-assets.php`
- `assets/css/responsible-play.css`
- Any file in `lib/`
