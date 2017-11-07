<?php
/*
Plugin Name: WeatherTextPlugin
Description: Plugin de teste
Version: 1.0
Author: Soraia Martins
License: GPLv2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: weather-text-plugin
Domain Path: /languages

WeatherTextPlugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
WeatherTextPlugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with WeatherTextPlugin. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

add_action( 'plugins_loaded', 'wtp_load_textdomain' );

function wtp_load_textdomain() {
   load_plugin_textdomain( 'weather-text-plugin', false,dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

// registar a função para correr na ativação do plugin
register_activation_hook( __FILE__, 'wtp_install_hook' );

function wtp_install_hook() {
  // criar as opções necessárias
  add_option( 'latitude', '41.355728' );
  add_option( 'longitude', '-8.40059' );
}

// função a executar no shortcode
function wtp_weathertext_shortcode( $atts ) {
    $lat=get_option("latitude");
    $lng=get_option("longitude");

    if ( false === ( $weather_info = wp_cache_get( "{$lat},{$lng}", "wtp" ) ) ) {
        $weather_info=wtp_getWeather($lat,$lng);
        wp_cache_set( "{$lat},{$lng}", $weather_info, "wtp", HOUR_IN_SECONDS );
    }

    $weather_info_channel=$weather_info->query->results->channel;
    return __('Temperature','weather-text-plugin')." 
            {$weather_info_channel->location->city}, {$weather_info_channel->location->region}, {$weather_info_channel->location->country} 
            ".__('is','weather-text-plugin')." {$weather_info_channel->item->condition->temp}ºC.";
}
// adicionar shortcode
add_shortcode( 'weathertext', 'wtp_weathertext_shortcode' );

// adicionar menu para configurar opções do plugin
add_action("admin_menu", "wtp_addWpcMenu");
function wtp_addWpcMenu(){
    add_menu_page(__('Weather plugin','weather-text-plugin'), __('Weather plugin','weather-text-plugin'), 'administrator', "weather-plugin-config", "wtp_weather_plugin_settings_page");
    //call register settings function
	add_action( 'admin_init', 'wtp_register_weather_plugin_settings' );
}

function wtp_register_weather_plugin_settings() {
	//register our settings
	register_setting( 'weather-settings-group', 'latitude' );
	register_setting( 'weather-settings-group', 'longitude' );
}

function wtp_weather_plugin_settings_page() {
?>
    <div class="wrap">
        <h3><?php_e('Location','weather-text-plugin');?></h3>
        <form method="post" action="options.php">
            <?php settings_fields( 'weather-settings-group' ); ?>
            <?php do_settings_sections( 'weather-settings-group' ); ?>
            <table class="form-table">
                <tr valign="top">
                <th scope="row"><?php _e('Latitude','weather-text-plugin'); ?></th>
                <td><input type="text" name="latitude" value="<?php echo esc_attr( get_option('latitude') ); ?>" /></td>
                </tr>
                <tr valign="top">
                <th scope="row"><?php _e('Longitude','weather-text-plugin'); ?></th>
                <td><input type="text" name="longitude" value="<?php echo esc_attr( get_option('longitude') ); ?>" /></td>
                </tr>
            </table>
            
            <?php submit_button(); ?>

        </form>
    </div>
<?php } 

include( plugin_dir_path( __FILE__ ) . 'wtp_widget.php');
// Register and load the widget
function wtp_load_widget() {
    register_widget( 'wtp_widget' );
}
add_action( 'widgets_init', 'wtp_load_widget' );

//function to do the request
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

