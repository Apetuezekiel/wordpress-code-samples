<?php
/**
 * ACF Block Registration and Rendering.
 *
 * @package EzekielApetu\AcfBlocks
 */

namespace EzekielApetu\AcfBlocks;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers all ACF-powered Gutenberg blocks and provides render callbacks.
 *
 * Each block is registered programmatically via acf_register_block_type()
 * inside the acf/init action, keeping field definitions version-controlled
 * rather than stored in the database.
 *
 * Usage:
 *   $blocks = new ACF_Blocks();
 *   add_action( 'acf/init', array( $blocks, 'register_blocks' ) );
 */
class ACF_Blocks {

    /**
     * Path to the directory containing block template files.
     *
     * @var string
     */
    private string $templates_dir;

    /**
     * URL base for block asset files.
     *
     * @var string
     */
    private string $assets_url;

    /**
     * Set up paths.
     *
     * @param string $templates_dir Absolute path to the templates directory.
     * @param string $assets_url    URL to the assets directory.
     */
    public function __construct( string $templates_dir, string $assets_url ) {
        $this->templates_dir = trailingslashit( $templates_dir );
        $this->assets_url    = trailingslashit( $assets_url );
    }

    /**
     * Register all ACF blocks.
     *
     * Hooked into acf/init. The guard below means this is safe to call even
     * when ACF is deactivated — nothing will break, blocks just won't appear.
     *
     * @return void
     */
    public function register_blocks(): void {
        if ( ! function_exists( 'acf_register_block_type' ) ) {
            return;
        }

        $this->register_hero_block();
        $this->register_feature_grid_block();
        $this->register_team_member_block();
        $this->register_cta_banner_block();
    }

    // -------------------------------------------------------------------------
    // Block registrations
    // -------------------------------------------------------------------------

    /**
     * Register the Hero block.
     *
     * @return void
     */
    private function register_hero_block(): void {
        acf_register_block_type(
            array(
                'name'            => 'ezekiel-hero',
                'title'           => __( 'Hero Section', 'ezekiel-blocks' ),
                'description'     => __( 'Full-width hero with headline, subtext, CTA button, and background image.', 'ezekiel-blocks' ),
                'render_callback' => array( $this, 'render_hero' ),
                'category'        => 'layout',
                'icon'            => 'cover-image',
                'keywords'        => array( 'hero', 'banner', 'header', 'landing' ),
                'mode'            => 'preview',
                'align'           => 'full',
                'supports'        => array(
                    'align'  => array( 'wide', 'full' ),
                    'anchor' => true,
                    'mode'   => true,
                ),
                'example'         => array(
                    'attributes' => array(
                        'mode' => 'preview',
                        'data' => array(
                            'headline' => 'Build something great.',
                            'subtext'  => 'We help businesses grow with clean, fast WordPress.',
                        ),
                    ),
                ),
            )
        );
    }

    /**
     * Register the Feature Grid block.
     *
     * @return void
     */
    private function register_feature_grid_block(): void {
        acf_register_block_type(
            array(
                'name'            => 'ezekiel-feature-grid',
                'title'           => __( 'Feature Grid', 'ezekiel-blocks' ),
                'description'     => __( 'A repeatable grid of icon + title + description feature cards.', 'ezekiel-blocks' ),
                'render_callback' => array( $this, 'render_feature_grid' ),
                'category'        => 'layout',
                'icon'            => 'grid-view',
                'keywords'        => array( 'features', 'grid', 'cards', 'services' ),
                'mode'            => 'preview',
                'supports'        => array(
                    'align'  => array( 'wide', 'full' ),
                    'anchor' => true,
                ),
            )
        );
    }

    /**
     * Register the Team Member block.
     *
     * @return void
     */
    private function register_team_member_block(): void {
        acf_register_block_type(
            array(
                'name'            => 'ezekiel-team-member',
                'title'           => __( 'Team Member', 'ezekiel-blocks' ),
                'description'     => __( 'Individual team member card with photo, name, role, and bio.', 'ezekiel-blocks' ),
                'render_callback' => array( $this, 'render_team_member' ),
                'category'        => 'layout',
                'icon'            => 'businessman',
                'keywords'        => array( 'team', 'person', 'staff', 'employee', 'bio' ),
                'mode'            => 'edit',
                'supports'        => array(
                    'anchor' => true,
                ),
            )
        );
    }

    /**
     * Register the CTA Banner block.
     *
     * @return void
     */
    private function register_cta_banner_block(): void {
        acf_register_block_type(
            array(
                'name'            => 'ezekiel-cta-banner',
                'title'           => __( 'CTA Banner', 'ezekiel-blocks' ),
                'description'     => __( 'Full-width call-to-action banner with headline, body copy, and one or two buttons.', 'ezekiel-blocks' ),
                'render_callback' => array( $this, 'render_cta_banner' ),
                'category'        => 'layout',
                'icon'            => 'megaphone',
                'keywords'        => array( 'cta', 'call to action', 'button', 'promo' ),
                'mode'            => 'preview',
                'align'           => 'full',
                'supports'        => array(
                    'align'  => array( 'wide', 'full' ),
                    'anchor' => true,
                ),
            )
        );
    }

    // -------------------------------------------------------------------------
    // Render callbacks
    // -------------------------------------------------------------------------

    /**
     * Render the Hero block.
     *
     * @param array      $block      ACF block attributes.
     * @param string     $content    InnerBlocks content (unused for ACF blocks).
     * @param bool       $is_preview Whether rendering in the editor preview.
     * @param int|string $post_id    The current post ID.
     * @return void
     */
    public function render_hero( array $block, string $content, bool $is_preview, int|string $post_id ): void {
        $headline        = get_field( 'headline' );
        $subtext         = get_field( 'subtext' );
        $cta_label       = get_field( 'cta_label' );
        $cta_url         = get_field( 'cta_url' );
        $background      = get_field( 'background_image' );
        $overlay_opacity = (int) ( get_field( 'overlay_opacity' ) ?: 40 );
        $text_align      = sanitize_text_field( get_field( 'text_alignment' ) ?: 'center' );

        if ( ! $headline && $is_preview ) {
            $this->render_empty_state( __( 'Hero Section', 'ezekiel-blocks' ), __( 'Add a headline to get started.', 'ezekiel-blocks' ) );
            return;
        }

        $wrapper_attrs = array(
            'id'    => ! empty( $block['anchor'] ) ? esc_attr( $block['anchor'] ) : '',
            'class' => $this->build_block_classes( $block, 'wp-block-ezekiel-hero' ),
        );

        if ( $background ) {
            $wrapper_attrs['style'] = 'background-image: url(' . esc_url( $background['url'] ) . ');';
        }

        ?>
        <section <?php $this->render_attrs( $wrapper_attrs ); ?>>
            <?php if ( $background && $overlay_opacity > 0 ) : ?>
                <div
                    class="hero__overlay"
                    style="opacity: <?php echo esc_attr( $overlay_opacity / 100 ); ?>;"
                    aria-hidden="true"
                ></div>
            <?php endif; ?>

            <div class="hero__inner" style="text-align: <?php echo esc_attr( $text_align ); ?>;">
                <?php if ( $headline ) : ?>
                    <h1 class="hero__headline"><?php echo wp_kses_post( $headline ); ?></h1>
                <?php endif; ?>

                <?php if ( $subtext ) : ?>
                    <p class="hero__subtext"><?php echo wp_kses_post( $subtext ); ?></p>
                <?php endif; ?>

                <?php if ( $cta_label && $cta_url ) : ?>
                    <a
                        href="<?php echo esc_url( $cta_url ); ?>"
                        class="hero__cta wp-element-button"
                    >
                        <?php echo esc_html( $cta_label ); ?>
                    </a>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Render the Feature Grid block.
     *
     * @param array      $block      ACF block attributes.
     * @param string     $content    InnerBlocks content (unused).
     * @param bool       $is_preview Whether rendering in the editor preview.
     * @param int|string $post_id    The current post ID.
     * @return void
     */
    public function render_feature_grid( array $block, string $content, bool $is_preview, int|string $post_id ): void {
        $section_title = get_field( 'section_title' );
        $features      = get_field( 'features' );
        $columns       = absint( get_field( 'columns' ) ?: 3 );

        if ( empty( $features ) ) {
            if ( $is_preview ) {
                $this->render_empty_state( __( 'Feature Grid', 'ezekiel-blocks' ), __( 'Add features in the sidebar to populate this block.', 'ezekiel-blocks' ) );
            }
            return;
        }

        $wrapper_attrs = array(
            'id'    => ! empty( $block['anchor'] ) ? esc_attr( $block['anchor'] ) : '',
            'class' => $this->build_block_classes( $block, 'wp-block-ezekiel-feature-grid' ),
        );
        ?>
        <section <?php $this->render_attrs( $wrapper_attrs ); ?>>
            <?php if ( $section_title ) : ?>
                <h2 class="feature-grid__heading"><?php echo esc_html( $section_title ); ?></h2>
            <?php endif; ?>

            <ul
                class="feature-grid__items"
                style="--grid-cols: <?php echo esc_attr( $columns ); ?>;"
            >
                <?php foreach ( $features as $feature ) :
                    $icon  = sanitize_text_field( $feature['icon_class'] ?? '' );
                    $title = sanitize_text_field( $feature['title'] ?? '' );
                    $desc  = wp_kses_post( $feature['description'] ?? '' );
                    ?>
                    <li class="feature-grid__item">
                        <?php if ( $icon ) : ?>
                            <span class="feature-grid__icon <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
                        <?php endif; ?>

                        <?php if ( $title ) : ?>
                            <h3 class="feature-grid__title"><?php echo esc_html( $title ); ?></h3>
                        <?php endif; ?>

                        <?php if ( $desc ) : ?>
                            <p class="feature-grid__desc"><?php echo $desc; // Already wp_kses_post'd. ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php
    }

    /**
     * Render the Team Member block.
     *
     * @param array      $block      ACF block attributes.
     * @param string     $content    InnerBlocks content (unused).
     * @param bool       $is_preview Whether rendering in the editor preview.
     * @param int|string $post_id    The current post ID.
     * @return void
     */
    public function render_team_member( array $block, string $content, bool $is_preview, int|string $post_id ): void {
        $photo    = get_field( 'photo' );
        $name     = sanitize_text_field( get_field( 'name' ) ?? '' );
        $role     = sanitize_text_field( get_field( 'role' ) ?? '' );
        $bio      = wp_kses_post( get_field( 'bio' ) ?? '' );
        $linkedin = esc_url( get_field( 'linkedin_url' ) ?? '' );
        $twitter  = esc_url( get_field( 'twitter_url' ) ?? '' );

        if ( ! $name && $is_preview ) {
            $this->render_empty_state( __( 'Team Member', 'ezekiel-blocks' ), __( 'Fill in the name field to preview.', 'ezekiel-blocks' ) );
            return;
        }

        $wrapper_attrs = array(
            'id'    => ! empty( $block['anchor'] ) ? esc_attr( $block['anchor'] ) : '',
            'class' => $this->build_block_classes( $block, 'wp-block-ezekiel-team-member' ),
        );
        ?>
        <div <?php $this->render_attrs( $wrapper_attrs ); ?>>
            <?php if ( $photo ) : ?>
                <figure class="team-member__photo">
                    <img
                        src="<?php echo esc_url( $photo['url'] ); ?>"
                        alt="<?php echo esc_attr( $name ); ?>"
                        width="<?php echo esc_attr( $photo['width'] ); ?>"
                        height="<?php echo esc_attr( $photo['height'] ); ?>"
                        loading="lazy"
                    />
                </figure>
            <?php endif; ?>

            <div class="team-member__body">
                <?php if ( $name ) : ?>
                    <h3 class="team-member__name"><?php echo esc_html( $name ); ?></h3>
                <?php endif; ?>

                <?php if ( $role ) : ?>
                    <p class="team-member__role"><?php echo esc_html( $role ); ?></p>
                <?php endif; ?>

                <?php if ( $bio ) : ?>
                    <div class="team-member__bio"><?php echo $bio; // Already wp_kses_post'd. ?></div>
                <?php endif; ?>

                <?php if ( $linkedin || $twitter ) : ?>
                    <ul class="team-member__social">
                        <?php if ( $linkedin ) : ?>
                            <li>
                                <a href="<?php echo $linkedin; ?>" target="_blank" rel="noopener noreferrer">
                                    <?php esc_html_e( 'LinkedIn', 'ezekiel-blocks' ); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if ( $twitter ) : ?>
                            <li>
                                <a href="<?php echo $twitter; ?>" target="_blank" rel="noopener noreferrer">
                                    <?php esc_html_e( 'Twitter / X', 'ezekiel-blocks' ); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render the CTA Banner block.
     *
     * @param array      $block      ACF block attributes.
     * @param string     $content    InnerBlocks content (unused).
     * @param bool       $is_preview Whether rendering in the editor preview.
     * @param int|string $post_id    The current post ID.
     * @return void
     */
    public function render_cta_banner( array $block, string $content, bool $is_preview, int|string $post_id ): void {
        $headline        = get_field( 'headline' );
        $body            = wp_kses_post( get_field( 'body' ) ?? '' );
        $primary_label   = sanitize_text_field( get_field( 'primary_button_label' ) ?? '' );
        $primary_url     = esc_url( get_field( 'primary_button_url' ) ?? '' );
        $secondary_label = sanitize_text_field( get_field( 'secondary_button_label' ) ?? '' );
        $secondary_url   = esc_url( get_field( 'secondary_button_url' ) ?? '' );
        $bg_color        = sanitize_hex_color( get_field( 'background_color' ) ?? '' ) ?: '#1a1a2e';
        $text_color      = sanitize_hex_color( get_field( 'text_color' ) ?? '' ) ?: '#ffffff';

        if ( ! $headline && $is_preview ) {
            $this->render_empty_state( __( 'CTA Banner', 'ezekiel-blocks' ), __( 'Add a headline to get started.', 'ezekiel-blocks' ) );
            return;
        }

        $wrapper_attrs = array(
            'id'    => ! empty( $block['anchor'] ) ? esc_attr( $block['anchor'] ) : '',
            'class' => $this->build_block_classes( $block, 'wp-block-ezekiel-cta-banner' ),
            'style' => "background-color: {$bg_color}; color: {$text_color};",
        );
        ?>
        <section <?php $this->render_attrs( $wrapper_attrs ); ?>>
            <div class="cta-banner__inner">
                <?php if ( $headline ) : ?>
                    <h2 class="cta-banner__headline"><?php echo wp_kses_post( $headline ); ?></h2>
                <?php endif; ?>

                <?php if ( $body ) : ?>
                    <div class="cta-banner__body"><?php echo $body; ?></div>
                <?php endif; ?>

                <?php if ( $primary_label || $secondary_label ) : ?>
                    <div class="cta-banner__actions">
                        <?php if ( $primary_label && $primary_url ) : ?>
                            <a
                                href="<?php echo $primary_url; ?>"
                                class="wp-element-button cta-banner__btn cta-banner__btn--primary"
                            >
                                <?php echo esc_html( $primary_label ); ?>
                            </a>
                        <?php endif; ?>

                        <?php if ( $secondary_label && $secondary_url ) : ?>
                            <a
                                href="<?php echo $secondary_url; ?>"
                                class="wp-element-button cta-banner__btn cta-banner__btn--secondary"
                            >
                                <?php echo esc_html( $secondary_label ); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }

    // -------------------------------------------------------------------------
    // Utility methods
    // -------------------------------------------------------------------------

    /**
     * Build a CSS class string for a block wrapper, merging ACF-provided
     * align/className attributes with a base class.
     *
     * @param array  $block      ACF block attribute array.
     * @param string $base_class The block's own BEM root class.
     * @return string Space-separated class string.
     */
    private function build_block_classes( array $block, string $base_class ): string {
        $classes = array( $base_class );

        if ( ! empty( $block['className'] ) ) {
            $classes[] = $block['className'];
        }

        if ( ! empty( $block['align'] ) ) {
            $classes[] = 'align' . $block['align'];
        }

        return implode( ' ', array_filter( $classes ) );
    }

    /**
     * Echo an HTML attribute string from an associative array, skipping empty values.
     *
     * @param array<string, string> $attrs Key/value attribute pairs.
     * @return void
     */
    private function render_attrs( array $attrs ): void {
        foreach ( $attrs as $key => $value ) {
            if ( '' === $value ) {
                continue;
            }
            echo ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
        }
    }

    /**
     * Render a minimal placeholder UI shown in the editor when a block has no data.
     *
     * @param string $title   Block name.
     * @param string $message Hint text for the editor.
     * @return void
     */
    private function render_empty_state( string $title, string $message ): void {
        ?>
        <div class="acf-block-placeholder">
            <p><strong><?php echo esc_html( $title ); ?></strong></p>
            <p><?php echo esc_html( $message ); ?></p>
        </div>
        <?php
    }
}
