<?php
/**
 * Plugin Name:       Posts To QR Code
 * Plugin URI:        https://example.com/plugins/qrcode/
 * Description:       This is practise plugin.
 * Version:           1.0
 * Author:            Sohan
 * Author URI:        https://author.example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://example.com/my-plugin/
 * Text Domain:       posts-to-qrcode
 * Domain Path:       /languages
 */

$pqrc_countries = array(
    __( 'Afganistan', 'posts-to-qrcode' ),
    __( 'Bangladesh', 'posts-to-qrcode' ),
    __( 'India', 'posts-to-qrcode' ),
    __( 'Maldives', 'posts-to-qrcode' ),
    __( 'Nepal', 'posts-to-qrcode' ),
    __( 'Pakistan', 'posts-to-qrcode' ),
    __( 'Sri Lanka', 'posts-to-qrcode' )
);

function pqrc_init(){
    global $pqrc_countries;
    $pqrc_countries = apply_filters( 'pqrc_countries', $pqrc_countries );
}
add_action( "init", "pqrc_init" );

//Load Text Domain
function pqrc_load_textdomain(){
    load_plugin_textdomain( 'posts-to-qrcode', false, dirname(__FILE__) . "/languages" );
}
add_action( 'plugins_loaded', 'pqrc_load_textdomain');

function pqrc_display_qr_code($content){
    $current_post_id    = get_the_ID();
    $current_post_title = get_the_title( $current_post_id );
    $current_post_url   = urlencode( get_the_permalink ( $current_post_id ) );
    $current_post_type  = get_post_type( $current_post_id );

    //Post Type Check
    $excluded_post_types = apply_filters( 'pqrc_excluded_post_types', array() );
    if ( in_array( $current_post_type, $excluded_post_types ) ) {
        return $content;
    }

    // Dimension Hook
    $height     = get_option( 'pqrc_height' );
    $width      = get_option( 'pqrc_width' );
    $height     = $height ? $height : 180;
    $width      = $width ? $width : 180;
    $dimension  = apply_filters( 'pqrc_qrcode_dimension', "{$width}x{$height}" );

    // Image attributes
    $image_attributes = apply_filters( 'pqrc_image_attributes', '' );

    $image_src = sprintf( 'https://api.qrserver.com/v1/create-qr-code/?size=%s&ecc=L&qzone=1&data=%s', $dimension ,$current_post_url );
    $content .=  sprintf( "<div class='qrcode'><img %s src='%s' alt='%s' /></div>", $image_attributes, $image_src, $current_post_title );
    return $content;
}
add_filter( 'the_content', 'pqrc_display_qr_code' );

function pqrc_settings_init(){
    //add_settings_section( $id:string, $title:string, $callback:callable, $page:string );
    add_settings_section( 'pqrc_section', __( 'Posts to QR Code', 'posts-to-qrcode'), "pqrc_section_callback", 'general' );

    //add_settings_field( $id:string, $title:string, $callback:callable, $page:string, $section:string, $args:array );
    add_settings_field( 'pqrc_height', __( 'QR Code Height', 'posts-to-qrcode'), 'pqrc_display_field', 'general', 'pqrc_section', array('pqrc_height') );
    add_settings_field( 'pqrc_width', __( 'QR Code Width', 'posts-to-qrcode' ), 'pqrc_display_field', 'general', 'pqrc_section', array('pqrc_width') );
    // add_settings_field( 'extra_option', __( 'QR Code Extra', 'posts-to-qrcode' ), 'pqrc_display_field', 'general', 'pqrc_section', array('extra_option') );
    add_settings_field( 'pqrc_select', __( 'Dropdown', 'posts-to-qrcode' ), 'pqrc_display_select_field', 'general', 'pqrc_section' );
    add_settings_field( 'pqrc_checkbox', __( 'Select Countries', 'posts-to-qrcode' ), 'pqrc_display_checkboxgroup_field', 'general', 'pqrc_section' );
    add_settings_field( 'pqrc_toggle', __( 'Toggle Field', 'posts-to-qrcode' ), 'pqrc_display_toggle_field', 'general', 'pqrc_section' );

    //register_setting( $option_group:string, $option_name:string, $args:array )
    register_setting( 'general', 'pqrc_height', array( 'sanitize_callback' => 'esc_attr' ) );
    register_setting( 'general', 'pqrc_width', array( 'sanitize_callback' => 'esc_attr' ) );
    // register_setting( 'general', 'extra_option', array( 'sanitize_callback' => 'esc_attr' ) );
    register_setting( 'general', 'pqrc_select', array( 'sanitize_callback' => 'esc_attr' ) );
    register_setting( 'general', 'pqrc_checkbox' );
    register_setting( 'general', 'pqrc_toggle' );
}

function pqrc_display_toggle_field(){
    $option = get_option( 'pqrc_toggle' );
    echo '<div id="toggle1"></div>';
    echo "<input type='hidden' name='pqrc_toggle' id='pqrc_toggle' value='".$option."' />";
}

function pqrc_display_checkboxgroup_field(){
    global $pqrc_countries;
    $option = get_option('pqrc_checkbox');

    foreach( $pqrc_countries as $country ) {
        $selected = '';

        if( is_array($option) && in_array( $country, $option ) ) {
            $selected = 'checked';
        }
        printf('<input type="checkbox" name="pqrc_checkbox[]" value="%s" %s /> %s <br>', $country, $selected, $country);
    }
}

function pqrc_display_select_field(){
    global $pqrc_countries;
    $option = get_option('pqrc_select');

    printf('<select id="%s" name="%s">', 'pqrc_select', 'pqrc_select');
    foreach( $pqrc_countries as $country ) {
        $selected = '';
        if( $option == $country ) {
            $selected = 'selected';
        }
        printf('<option value="%s" %s>%s</option>', $country, $selected, $country);
    }
    echo "</select>";
}

function pqrc_section_callback(){
    echo "<p>". __( 'Settings for posts to QR Plugin', 'posts-to-qrcode') ."</p>";
}

function pqrc_display_field($args){
    $option = get_option( $args[0] );
    printf( "<input type='text' id='%s' name='%s' value='%s'/>", $args[0], $args[0], $option );
}

// function pqrc_display_width(){
//     $width = get_option( 'pqrc_width' );
//     printf( "<input type='text' id='%s' name='%s' value='%s'/>", 'pqrc_width', 'pqrc_width', $width );
// }
// function pqrc_display_height(){
//     $height = get_option( 'pqrc_height' );
//     printf( "<input type='text' id='%s' name='%s' value='%s'/>", 'pqrc_height', 'pqrc_height', $height );
// }

add_action( "admin_init", "pqrc_settings_init" );

function pqrc_assets($screen) {
    if ( 'options-general.php' == $screen ) {

        wp_enqueue_style( 'minitoggle-css', plugin_dir_url( __FILE__ ) . "/assets/css/minitoggle.css", true );

        wp_enqueue_script( 'minitoggle-js', plugin_dir_url( __FILE__ ) . "/assets/js/minitoggle.js", array('jquery'), time(), true );
        wp_enqueue_script( 'pqrc-main-js', plugin_dir_url( __FILE__ ) . "/assets/js/pqrc-main.js", array('jquery'), time(), true );
    }
}
add_action( 'admin_enqueue_scripts', 'pqrc_assets' );


// Shortcode 
function test_button2( $atts, $content = '' ) {
    $default = array(
        'type' => 'primary',
        'title' => __( 'Button', 'posts-to-qrcode' ),
        'url' => ''
    );

    $button_atts = shortcode_atts( $default, $atts );

    return sprintf( '<a target="_blank" class="btn btn--%s full-width" href="%s">%s</a>',
        $button_atts['type'],
        $button_atts['url'],
        $content
    );
}
add_shortcode( 'button2', 'test_button2' );

function test_uppercase( $atts, $content = '' ){
    return strtoupper($content);
}
add_shortcode( 'uc', 'test_uppercase' );


function test_google_map($atts){
    $default = array(
        'place' => 'Dhaka Museum'
    );

    $params = shortcode_atts($default, $atts);

    $map = 
        '<div>
            <div>
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d29186.928981510508!2d90.36077837889393!3d23.87663260973124!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755c5d05e7074dd%3A0xd1c58803049f00c7!2sUttara%2C%20Dhaka!5e0!3m2!1sen!2sbd!4v1655255076287!5m2!1sen!2sbd" 
                    width="600" 
                    height="450" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>';

    return $map;
}
add_shortcode( 'gmap', 'test_google_map' );
