<?php
/**
 * Floating Donate button — renders a fixed-position <button data-dt-donate>
 * in the site footer when the admin has enabled the feature in
 * Settings → DonatoTomato → Floating Donate Button. Click handling is
 * delegated to the existing embed.js (loaded site-wide by
 * DonatoTomato_Embed_Loader when this feature is on).
 *
 * @package DonatoTomato
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DonatoTomato_Floating_Button {

    const META_HAS_INLINE = '_dt_has_inline_widget';

    public function __construct() {
        add_action( 'wp_footer', [ $this, 'render' ], 100 );
        add_action( 'save_post', [ $this, 'index_inline_widget' ], 10, 2 );
    }

    /**
     * On post save, scan the post content for the inline iframe block /
     * shortcode and write a post_meta flag. should_render() consults the
     * flag at request time to auto-hide the floating button on pages that
     * already host an inline donation widget. The render-time post_content
     * scan is a fallback for shortcodes invoked from PHP templates via
     * do_shortcode().
     *
     * @param int     $post_id Post being saved.
     * @param WP_Post $post    Full post object.
     */
    public function index_inline_widget( $post_id, $post ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }
        if ( ! $post instanceof WP_Post ) {
            return;
        }

        $has_inline = $this->content_has_inline_widget( $post->post_content );
        if ( $has_inline ) {
            update_post_meta( $post_id, self::META_HAS_INLINE, '1' );
        } else {
            delete_post_meta( $post_id, self::META_HAS_INLINE );
        }
    }

    /**
     * Heuristic detection. Matches the Gutenberg block comment delimiter
     * and the [donatotomato] shortcode (NOT the [donatotomato_button]
     * shortcode — buttons are fine alongside the floating button).
     *
     * @param string $content Post content.
     * @return bool True if inline widget present.
     */
    public function content_has_inline_widget( $content ) {
        if ( ! is_string( $content ) || '' === $content ) {
            return false;
        }
        if ( false !== strpos( $content, '<!-- wp:donatotomato/block' ) ) {
            return true;
        }
        // Match [donatotomato ...] / [donatotomato] but not [donatotomato_button].
        if ( preg_match( '/\[donatotomato(?![_a-z])[^\]]*\]/i', $content ) ) {
            return true;
        }
        return false;
    }

    /**
     * Gate: should the floating button render on the current request?
     *
     * @return bool
     */
    public function should_render() {
        if ( is_admin() ) {
            return false;
        }
        if ( is_feed() || is_embed() ) {
            return false;
        }

        $enabled = get_option( 'donatotomato_floating_enabled', '0' );
        if ( '1' !== (string) $enabled ) {
            return false;
        }

        $slug = trim( (string) get_option( 'donatotomato_org_slug', '' ) );
        if ( '' === $slug ) {
            return false;
        }

        $campaign = trim( (string) get_option( 'donatotomato_floating_campaign', '' ) );
        if ( '' === $campaign ) {
            return false;
        }

        $excluded_ids = get_option( 'donatotomato_floating_exclude_ids', [] );
        if ( ! is_array( $excluded_ids ) ) {
            $excluded_ids = [];
        }
        $current_id = (int) get_queried_object_id();
        if ( $current_id > 0 && in_array( $current_id, array_map( 'intval', $excluded_ids ), true ) ) {
            return false;
        }

        $auto_hide_inline = get_option( 'donatotomato_floating_auto_hide_inline', '1' );
        if ( '1' === (string) $auto_hide_inline && $current_id > 0 ) {
            if ( '1' === (string) get_post_meta( $current_id, self::META_HAS_INLINE, true ) ) {
                return false;
            }
            // Fallback: scan rendered post content for shortcodes injected
            // via do_shortcode() in a PHP template (not caught by save_post).
            $post = get_post( $current_id );
            if ( $post instanceof WP_Post && $this->content_has_inline_widget( $post->post_content ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Render the floating button + scoped CSS in wp_footer. Also opts the
     * page in to embed.js loading by calling DonatoTomato_Embed_Loader::enqueue()
     * before WP prints the footer scripts.
     */
    public function render() {
        if ( ! $this->should_render() ) {
            return;
        }

        $campaign = sanitize_text_field( (string) get_option( 'donatotomato_floating_campaign', '' ) );
        $label    = (string) get_option( 'donatotomato_floating_label', '' );
        if ( '' === trim( $label ) ) {
            $label = __( 'Donate', 'donatotomato' );
        }
        $label = sanitize_text_field( $label );

        $size     = $this->sanitize_choice(
            (string) get_option( 'donatotomato_floating_size', 'medium' ),
            [ 'small', 'medium', 'large' ],
            'medium'
        );
        $shape    = $this->sanitize_choice(
            (string) get_option( 'donatotomato_floating_shape', 'pill' ),
            [ 'pill', 'rounded', 'sharp' ],
            'pill'
        );
        $position = $this->sanitize_choice(
            (string) get_option( 'donatotomato_floating_position', 'bottom-right' ),
            [ 'bottom-right', 'bottom-left', 'top-right', 'top-left' ],
            'bottom-right'
        );

        $offset = (int) get_option( 'donatotomato_floating_offset', 24 );
        if ( $offset < 12 ) {
            $offset = 12;
        }
        if ( $offset > 48 ) {
            $offset = 48;
        }

        $z_index = (int) get_option( 'donatotomato_floating_zindex', 999999 );
        if ( $z_index < 1 ) {
            $z_index = 999999;
        }

        $color = sanitize_hex_color( (string) get_option( 'donatotomato_floating_color', '' ) );
        if ( ! $color ) {
            $color = '#10b981';
        }
        $text_color = $this->contrast_text_color( $color );

        $show_heart = '1' === (string) get_option( 'donatotomato_floating_show_heart', '0' );

        $padding_map = [
            'small'  => '10px 18px',
            'medium' => '14px 28px',
            'large'  => '18px 36px',
        ];
        $font_size_map = [
            'small'  => '13px',
            'medium' => '15px',
            'large'  => '17px',
        ];
        $radius_map = [
            'pill'    => '9999px',
            'rounded' => '8px',
            'sharp'   => '0',
        ];

        $position_css       = $this->position_to_css( $position, $offset );
        $position_css_mobile = $this->position_to_css( $position, max( 12, (int) round( $offset * 0.6 ) ) );

        DonatoTomato_Embed_Loader::enqueue();

        // Build CSS. Values are pre-sanitized scalars (validated enums,
        // bounded int, hex color) so the inline style block is safe.
        $css  = '.dt-floating-donate{position:fixed;';
        $css .= $position_css;
        $css .= 'background:' . $color . ';color:' . $text_color . ';';
        $css .= 'padding:' . $padding_map[ $size ] . ';';
        $css .= 'font-size:' . $font_size_map[ $size ] . ';';
        $css .= 'border-radius:' . $radius_map[ $shape ] . ';';
        $css .= 'font-weight:600;line-height:1.2;border:0;cursor:pointer;';
        $css .= 'box-shadow:0 8px 24px rgba(0,0,0,0.15);';
        $css .= 'z-index:' . $z_index . ';';
        $css .= 'display:inline-flex;align-items:center;justify-content:center;gap:0.5em;';
        $css .= 'transition:transform 0.15s ease;-webkit-appearance:none;appearance:none;';
        $css .= 'text-decoration:none;font-family:inherit;}';
        $css .= '.dt-floating-donate:hover,.dt-floating-donate:focus{transform:scale(1.05);color:' . $text_color . ';}';
        $css .= '.dt-floating-donate:focus-visible{outline:2px solid ' . $color . ';outline-offset:3px;}';
        $css .= '.dt-floating-donate__heart{font-size:1.05em;line-height:1;}';
        $css .= '@media (max-width:640px){.dt-floating-donate{';
        $css .= $position_css_mobile;
        $css .= 'padding:12px 22px;font-size:14px;}}';

        $aria = sprintf(
            /* translators: %s: button label, e.g. "Donate". */
            __( 'Open donation form: %s', 'donatotomato' ),
            $label
        );

        // wp_print_inline_script_tag exists in WP 5.7+; use a plain <style> tag
        // for broadest compat (we target WP 6.0+ per readme, but keeping it
        // simple here keeps the footer print stable). Style attribute values
        // are all pre-sanitized scalars.
        echo '<style id="donatotomato-floating-button-css">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        printf(
            '<button type="button" class="dt-floating-donate" data-dt-donate="%1$s" aria-label="%2$s">%3$s<span class="dt-floating-donate__label">%4$s</span></button>',
            esc_attr( $campaign ),
            esc_attr( $aria ),
            $show_heart ? '<span class="dt-floating-donate__heart" aria-hidden="true">&#9829;</span>' : '',
            esc_html( $label )
        );
    }

    /**
     * Constrain a string option value to a whitelist.
     *
     * @param string   $value    Raw value.
     * @param string[] $allowed  Allowed values.
     * @param string   $fallback Value returned when $value is not in $allowed.
     * @return string
     */
    private function sanitize_choice( $value, array $allowed, $fallback ) {
        return in_array( $value, $allowed, true ) ? $value : $fallback;
    }

    /**
     * Map a position enum + offset to the matching fixed-position CSS
     * declarations (top/right/bottom/left).
     *
     * @param string $position One of bottom-right/bottom-left/top-right/top-left.
     * @param int    $offset   Pixel offset, 12-48.
     * @return string CSS declarations terminated by semicolons.
     */
    private function position_to_css( $position, $offset ) {
        $o = (int) $offset . 'px';
        switch ( $position ) {
            case 'bottom-left':
                return 'bottom:' . $o . ';left:' . $o . ';top:auto;right:auto;';
            case 'top-right':
                return 'top:' . $o . ';right:' . $o . ';bottom:auto;left:auto;';
            case 'top-left':
                return 'top:' . $o . ';left:' . $o . ';bottom:auto;right:auto;';
            case 'bottom-right':
            default:
                return 'bottom:' . $o . ';right:' . $o . ';top:auto;left:auto;';
        }
    }

    /**
     * Pick a readable text color (black or white) for a given background
     * hex via the standard sRGB luminance heuristic.
     *
     * @param string $hex Sanitized hex color, e.g. "#10b981".
     * @return string "#ffffff" or "#111111".
     */
    private function contrast_text_color( $hex ) {
        $hex = ltrim( $hex, '#' );
        if ( 3 === strlen( $hex ) ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if ( 6 !== strlen( $hex ) ) {
            return '#ffffff';
        }
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );
        // Perceived luminance, ITU-R BT.601 coefficients.
        $luma = ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255;
        return $luma > 0.6 ? '#111111' : '#ffffff';
    }
}
