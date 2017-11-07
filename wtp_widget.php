<?php
class wtp_widget extends WP_Widget {
 
    function __construct() {
        parent::__construct(
        
        // Base ID of your widget
        'wtp_widget', 
        
        // Widget name will appear in UI
        __('Weather Widget', 'wtp_widget_domain'), 
        
        // Widget description
        array( 'description' => __( 'Present weather from latitude and longitude', 'wtp_widget_domain' ), ) 
        );
    }
    
    // Creating widget front-end
    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance['title'] );
        
        // before and after widget arguments are defined by themes
        echo $args['before_widget'];
        if ( ! empty( $title ) )
            echo $args['before_title'] . $title . $args['after_title'];
        
        // This is where you run the code and display the output
        $lat=get_option('latitude');
        $lng=get_option('longitude');
        $weather_info=wtp_getWeather($lat,$lng);
        $weather_info_location=$weather_info->query->results->channel->location;
        $weather_info_temp=$weather_info->query->results->channel->item->condition->temp;
        echo "{$weather_info_location->city}, {$weather_info_location->region}, {$weather_info_location->country}<br/>";
        echo "Temperatura: ";
        echo "{$weather_info_temp}ºC";

        echo $args['after_widget'];
    }
            
    // Widget Backend 
    public function form( $instance ) {
        if ( isset( $instance[ 'title' ] ) ) {
            $title = $instance[ 'title' ];
        }
        // Widget admin form
        ?>
        <p>
        <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
        <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <?php 
    }
        
    // Updating widget replacing old instances with new
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        return $instance;
    }
}