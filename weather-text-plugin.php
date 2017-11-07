<?php
/**
* Plugin Name: WeatherTextPlugin
* Description: Plugin metereologia: traduz shortcode e cria widget
* Version: 1.0
* Author: Soraia Martins
* Text Domain: weather-text-plugin
* Domain Path: /languages
* License: GPLv2
* License URI:  https://www.gnu.org/licenses/gpl-2.0.html
* 
* WeatherTextPlugin is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 2 of the License, or
* any later version.
*  
* WeatherTextPlugin is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*  
* You should have received a copy of the GNU General Public License
* along with WeatherTextPlugin. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
//incluí a classe do widget
require_once( plugin_dir_path( __FILE__ ) . '/includes/wtp_widget.php');

//adicionar ação para carregar o textdomain do plugin para i18n quando os plugins são carregados
add_action( 'plugins_loaded', 'wtp_load_textdomain' );

function wtp_load_textdomain() {
   load_plugin_textdomain( 'weather-text-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

// registar a função para correr na ativação do plugin
register_activation_hook( __FILE__, 'wtp_install_hook' );
// função a executar na ativação do plugin
function wtp_install_hook() {
  // criar as opções necessárias
  add_option( 'latitude', '41.355728' );
  add_option( 'longitude', '-8.40059' );
}

// adicionar shortcode
add_shortcode( 'weathertext', 'wtp_weathertext_shortcode' );
// função a executar no shortcode
function wtp_weathertext_shortcode( $atts ) {
    $lat=get_option("latitude");
    $lng=get_option("longitude");

    if ( false === ( $weather_info = wp_cache_get( "{$lat},{$lng}", "wtp" ) ) ) {
        $weather_info=wtp_getWeather($lat,$lng);
        wp_cache_set( "{$lat},{$lng}", $weather_info, "wtp", HOUR_IN_SECONDS );
    }

    $weather_info_channel=$weather_info->query->results->channel;
    return sprintf(__('Temperature in %1$s, %2$s, %3$s is %4$sºC','weather-text-plugin'),
                    $weather_info_channel->location->city, 
                    $weather_info_channel->location->region, 
                    $weather_info_channel->location->country,
                    $weather_info_channel->item->condition->temp
            );
           
}

// adicionar menu para configurar opções do plugin
add_action("admin_menu", "wtp_addWpcMenu");
function wtp_addWpcMenu(){
    add_menu_page(__('Weather plugin','weather-text-plugin'), __('Weather plugin','weather-text-plugin'), 'administrator', "weather-plugin-config", "wtp_weather_plugin_settings_page");
    // chama a função para registar as configs
	add_action( 'admin_init', 'wtp_register_weather_plugin_settings' );
}

function wtp_weather_plugin_settings_page() { ?>
    <div class="wrap">
        <h1><?php _e('Weather plugin','weather-text-plugin');?></h1>
        <?php settings_errors(); ?>
        <form method="post" action="options.php">
            <?php 
            settings_fields( 'weather-settings-options' ); 
            do_settings_sections ('weather-plugin-config');
            submit_button(); 
            ?>
        </form>
    </div>
<?php } 

function wtp_register_weather_plugin_settings() {
	//regista as configs necessárias
	register_setting( 'weather-settings-options', 'latitude', 'wtp_settinglat_validate');
    register_setting( 'weather-settings-options', 'longitude', 'wtp_settinglng_validate');
    add_settings_section('wtp_section_location', __('Location','weather-text-plugin'), 'wtp_location_section_text', 'weather-plugin-config');
    add_settings_field('wtp_field_latitude', __('Latitude','weather-text-plugin'), 'wtp_field_latitude_input', 'weather-plugin-config', 'wtp_section_location');
    add_settings_field('wtp_field_longitude', __('Longitude','weather-text-plugin'), 'wtp_field_longitude_input', 'weather-plugin-config', 'wtp_section_location');
}

function wtp_settinglat_validate($input) {
	
	return wtp_setting_validate($input,'latitude');
	
}
function wtp_settinglng_validate($input) {
	
	return wtp_setting_validate($input,'longitude');
	
}
function wtp_setting_validate($input,$field) {
	
	$message = null;
    $type = null;
    $valid = true;
	
	if (empty($input)) {
        $message = sprintf(__('The %s can\'t be empty','weather-text-plugin'), $field);
        $type = 'error';
        $valid = false;
        
	} else if(!is_numeric(trim($input))) {
        $message = sprintf(__('The %s has to be a number','weather-text-plugin'), $field);
        $type = 'error';
        $valid = false;
        
	} 
	
	if($valid){
        return $input;
    }
    else{
        add_settings_error($field, 'lat_error_notice', $message, $type);
        return get_option($field);
    } 
	
}
function wtp_location_section_text() {
    echo '<p>'.__('Set the latitude and longitude of the wanted location.','weather-text-plugin').'</p>';
}
function wtp_field_latitude_input() {
    $lat = get_option('latitude');
    echo "<input id='wtp_field_longitude' name='latitude' size='40' type='text' value='{$lat}' />";
}
function wtp_field_longitude_input() {
    $lng = get_option('longitude');
    echo "<input id='wtp_field_longitude' name='longitude' size='40' type='text' value='{$lng}' />";
}

// regista e carrega o widget
function wtp_load_widget() {
    register_widget( 'wtp_widget' );
}
add_action( 'widgets_init', 'wtp_load_widget' );

// função para fazer o pedidio à api de metereologia
function wtp_getWeather($lat,$lng){
    $response = wp_remote_get("https://simple-weather.p.mashape.com/weatherdata?lat={$lat}&lng={$lng}",
        array("headers" => array(
            "X-Mashape-Key" => "hVwceKgYU7mshhI4XBbijLDGBqyLp1MATp7jsnvqSzMUosBNTc",
            "Accept" => "application/json")
        )
    );
    $content=wp_remote_retrieve_body( $response );
    return json_decode($content);
}
?>

