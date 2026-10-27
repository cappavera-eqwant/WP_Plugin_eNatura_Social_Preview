<?php
/**
 * Plugin Name: eNatura Social Preview
 * Description: Серверные Open Graph и Twitter Cards из публичных данных WordPress и WooCommerce.
 * Version: 1.0.0
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Author: eNatura
 * License: GPL-2.0-or-later
 * Text Domain: enatura-social-preview
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class ENatura_Social_Preview {
    const OPTION = 'enatura_social_preview';
    public static function init() {
        add_action( 'wp_head', array( __CLASS__, 'render' ), 20 );
        add_action( 'admin_init', array( __CLASS__, 'settings' ) );
        add_action( 'admin_menu', function () {
            add_options_page( 'eNatura Social Preview', 'eNatura Social Preview', 'manage_options', 'enatura-social-preview', array( __CLASS__, 'admin' ) );
        } );
    }
    public static function options() {
        return wp_parse_args( get_option( self::OPTION, array() ), array( 'mode' => 'auto', 'image_id' => 0 ) );
    }
    public static function seo_present() {
        return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' ) || function_exists( 'the_seo_framework' ) || defined( 'SLIM_SEO_VERSION' ) || class_exists( 'Jetpack' ) && method_exists( 'Jetpack', 'is_module_active' ) && Jetpack::is_module_active( 'publicize' );
    }
    public static function settings() {
        register_setting( self::OPTION, self::OPTION, array( 'type' => 'array', 'sanitize_callback' => array( __CLASS__, 'sanitize' ), 'default' => array() ) );
    }
    public static function sanitize( $value ) {
        $value = is_array( $value ) ? $value : array();
        $id = isset( $value['image_id'] ) ? absint( $value['image_id'] ) : 0;
        if ( $id && ! self::image( $id ) ) {
            add_settings_error( self::OPTION, 'image', 'Выберите доступное изображение JPEG, PNG, WebP или GIF из медиатеки, минимум 200×200.' );
            $id = 0;
        }
        return array( 'mode' => isset( $value['mode'] ) && in_array( $value['mode'], array( 'auto', 'on', 'off' ), true ) ? $value['mode'] : 'auto', 'image_id' => $id );
    }
    public static function admin() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $o = self::options();
        echo '<div class="wrap"><h1>eNatura Social Preview</h1><p>Название, описание и язык берутся из настроек WordPress. Изображение бренда — резерв для страниц без подходящей картинки.</p><p>Обнаружен SEO-провайдер: <strong>' . ( self::seo_present() ? 'да' : 'нет' ) . '</strong>. Автоматический режим уступает ему вывод всех карточек.</p><form action="options.php" method="post">';
        settings_fields( self::OPTION );
        echo '<p><label for="esp-mode">Вывод карточек</label> <select id="esp-mode" name="' . esc_attr( self::OPTION ) . '[mode]">';
        foreach ( array( 'auto' => 'Автоматически (рекомендуется)', 'on' => 'Включён — я проверил отсутствие дублей', 'off' => 'Выключен' ) as $key => $label ) {
            echo '<option value="' . esc_attr( $key ) . '" ' . selected( $o['mode'], $key, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select></p><p><label for="esp-image">ID изображения бренда в медиатеке</label> <input id="esp-image" type="number" min="0" name="' . esc_attr( self::OPTION ) . '[image_id]" value="' . absint( $o['image_id'] ) . '"></p><p>Загрузите JPEG/PNG 1200×630, желательно до 5 МБ. ID виден в адресе страницы редактирования медиа (post=…). 0 — без общей картинки. Иконка сайта не используется.</p>';
        $image = self::image( $o['image_id'] );
        if ( $image ) { echo '<p><img style="max-width:400px;height:auto" src="' . esc_url( $image['url'] ) . '" alt="' . esc_attr( $image['alt'] ) . '"></p>'; }
        submit_button();
        echo '</form></div>';
    }
    public static function clean( $text, $limit = 200 ) {
        $text = preg_replace( '/<!--[\s\S]*?-->/', ' ', (string) $text );
        $text = strip_shortcodes( $text );
        $text = html_entity_decode( wp_strip_all_tags( $text, true ), ENT_QUOTES, 'UTF-8' );
        $text = trim( preg_replace( '/\s+/u', ' ', $text ) );
        if ( function_exists( 'mb_strlen' ) && mb_strlen( $text ) > $limit ) { return mb_substr( $text, 0, $limit - 1 ) . '…'; }
        return wp_html_excerpt( $text, $limit, '…' );
    }
    public static function url( $url ) {
        $parts = wp_parse_url( (string) $url );
        return is_array( $parts ) && ! empty( $parts['host'] ) && isset( $parts['scheme'] ) && in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) && empty( $parts['user'] ) && empty( $parts['pass'] ) ? esc_url_raw( $url, array( 'http', 'https' ) ) : '';
    }
    public static function image( $id ) {
        $id = absint( $id );
        if ( ! $id || ! in_array( get_post_mime_type( $id ), array( 'image/jpeg', 'image/png', 'image/webp', 'image/gif' ), true ) ) { return null; }
        $attachment = get_post( $id );
        if ( ! $attachment || ! in_array( $attachment->post_status, array( 'inherit', 'publish' ), true ) || $attachment->post_password ) { return null; }
        if ( $attachment->post_parent ) {
            $parent = get_post( $attachment->post_parent );
            if ( $parent && ( ! is_post_publicly_viewable( $parent ) || $parent->post_password ) ) { return null; }
        }
        $src = wp_get_attachment_image_src( $id, 'full' );
        if ( ! $src || $src[1] < 200 || $src[2] < 200 || ! self::url( $src[0] ) ) { return null; }
        return array( 'url' => self::url( $src[0] ), 'width' => (int) $src[1], 'height' => (int) $src[2], 'type' => get_post_mime_type( $id ), 'alt' => self::clean( get_post_meta( $id, '_wp_attachment_image_alt', true ), 420 ) );
    }
    public static function data() {
        if ( is_admin() || is_feed() || is_trackback() || is_embed() || is_preview() || is_404() || is_search() || ! get_option( 'blog_public' ) ) { return null; }
        if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) { return null; }
        $site = self::clean( get_bloginfo( 'name' ), 200 );
        $desc = self::clean( get_bloginfo( 'description' ) );
        $title = $site;
        $url = '';
        $image_id = 0;
        $type = 'website';
        if ( is_singular() ) {
            $post = get_queried_object();
            if ( ! $post instanceof WP_Post || ! is_post_publicly_viewable( $post ) || $post->post_password ) { return null; }
            $url = wp_get_canonical_url( $post );
            $title = self::clean( get_the_title( $post ), 200 );
            $desc = self::clean( $post->post_excerpt );
            if ( ! $desc ) { $desc = self::clean( $post->post_content ); }
            $image_id = get_post_thumbnail_id( $post );
            $type = $post->post_type === 'post' ? 'article' : 'website';
            if ( $post->post_type === 'product' && function_exists( 'wc_get_product' ) ) {
                $product = wc_get_product( $post->ID );
                if ( $product ) {
                    $title = self::clean( $product->get_name() );
                    $desc = self::clean( $product->get_short_description() );
                    if ( ! $desc ) { $desc = self::clean( $product->get_description() ); }
                    $image_id = $product->get_image_id();
                }
            }
            if ( is_front_page() ) { $title = $site ?: $title; $desc = self::clean( get_bloginfo( 'description' ) ) ?: $desc; }
        } elseif ( is_front_page() || is_home() ) {
            $id = is_home() && ! is_front_page() ? (int) get_option( 'page_for_posts' ) : 0;
            $url = $id ? get_permalink( $id ) : home_url( '/' );
            if ( $id ) {
                $posts_page = get_post( $id );
                if ( ! $posts_page || ! is_post_publicly_viewable( $posts_page ) || $posts_page->post_password ) { return null; }
                $title = self::clean( get_the_title( $id ) );
                $image_id = get_post_thumbnail_id( $id );
            }
        } elseif ( is_category() || is_tag() || is_tax() ) {
            $term = get_queried_object();
            $taxonomy = get_taxonomy( $term->taxonomy );
            if ( ! $taxonomy || ! is_taxonomy_viewable( $taxonomy ) ) { return null; }
            $url = get_term_link( $term );
            if ( is_wp_error( $url ) ) { return null; }
            $title = self::clean( $term->name );
            $desc = self::clean( $term->description );
        } elseif ( is_post_type_archive() ) {
            $obj = get_queried_object();
            if ( ! $obj instanceof WP_Post_Type || ! is_post_type_viewable( $obj ) ) { return null; }
            $url = get_post_type_archive_link( $obj->name );
            $title = self::clean( $obj->labels->name );
            $desc = self::clean( $obj->description );
        } else { return null; }
        if ( ! is_singular() && get_query_var( 'paged' ) > 1 ) {
            $url = get_pagenum_link( (int) get_query_var( 'paged' ), false );
            // Strip arbitrary tracking/query parameters from the request-derived archive URL.
            $parts = wp_parse_url( $url );
            $query = array();
            if ( ! empty( $parts['query'] ) ) { parse_str( $parts['query'], $query ); }
            $url = remove_query_arg( array_diff( array_keys( $query ), array( 'paged', 'page', 'cat', 'tag', 'taxonomy', 'term', 'post_type', 'page_id' ) ), $url );
        }
        $url = self::url( $url );
        if ( ! $url ) { return null; }
        $o = self::options();
        $image = self::image( $image_id );
        $brand = self::image( $o['image_id'] );
        if ( ! $image || ( $image['width'] < 600 && $brand && $brand['width'] >= 600 && $brand['width'] > $brand['height'] ) ) { $image = $brand ?: $image; }
        if ( $image && ! $image['alt'] ) { $image['alt'] = $title ?: $site; }
        return array( 'title' => $title ?: $site, 'description' => $desc ?: self::clean( get_bloginfo( 'description' ) ), 'site' => $site, 'url' => $url, 'locale' => str_replace( '-', '_', get_bloginfo( 'language' ) ), 'type' => $type, 'image' => $image );
    }
    public static function render() {
        $o = self::options();
        if ( $o['mode'] === 'off' || ( $o['mode'] === 'auto' && apply_filters( 'enatura_social_preview_external_provider', self::seo_present() ) ) ) { return; }
        $d = self::data();
        if ( ! $d || ! apply_filters( 'enatura_social_preview_enabled', true ) ) { return; }
        $og = array( 'og:type' => $d['type'], 'og:title' => $d['title'], 'og:description' => $d['description'], 'og:url' => $d['url'], 'og:site_name' => $d['site'], 'og:locale' => $d['locale'] );
        $image = $d['image'];
        $large = $image && $image['width'] >= 600 && $image['height'] >= 315 && $image['width'] > $image['height'];
        $twitter = array( 'twitter:card' => $large ? 'summary_large_image' : 'summary', 'twitter:title' => $d['title'], 'twitter:description' => $d['description'] );
        if ( $image ) {
            $og += array( 'og:image' => $image['url'], 'og:image:type' => $image['type'], 'og:image:width' => $image['width'], 'og:image:height' => $image['height'], 'og:image:alt' => $image['alt'] );
            if ( strpos( $image['url'], 'https://' ) === 0 ) { $og['og:image:secure_url'] = $image['url']; }
            $twitter += array( 'twitter:image' => $image['url'], 'twitter:image:alt' => $image['alt'] );
        }
        echo "\n<!-- eNatura Social Preview 1.0.0 -->\n";
        foreach ( array( 'property' => $og, 'name' => $twitter ) as $attr => $tags ) {
            foreach ( $tags as $name => $value ) {
                if ( $value !== '' ) { echo '<meta ' . $attr . '="' . esc_attr( $name ) . '" content="' . esc_attr( $value ) . '" />' . "\n"; }
            }
        }
    }
}
ENatura_Social_Preview::init();
