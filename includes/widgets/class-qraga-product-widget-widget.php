<?php
// File: includes/widgets/class-qraga-product-widget-widget.php

defined('ABSPATH') || exit;

/**
 * Adds Qraga Product Widget.
 */
class Qraga_Product_Widget_Widget extends WP_Widget {

    /**
     * Register widget with WordPress.
     */
    function __construct() {
        parent::__construct(
            'qraga_product_widget', // Base ID
            esc_html__( 'Qraga Product Widget', 'qraga' ), // Name
            [ 'description' => esc_html__( 'Displays the Qraga widget for the current product.', 'qraga' ), ] // Args
        );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance ) {
        if ( ! is_singular('product') ) {
            return; 
        }

        if (!class_exists('Qraga_Widget_Display')) {
             return;
        }
        
        echo $args['before_widget'];
        
        // Output the placeholder div using the same method as the block
        // We don't have block attributes here, but render_placeholder handles defaults
        echo Qraga_Widget_Display::render_placeholder(); 
        
        echo $args['after_widget'];
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance ) {
        // No options needed for this widget currently.
        ?>
        <p>
            <?php esc_html_e( 'This widget displays the Qraga Product Widget on single product pages where this widget area is shown.', 'qraga' ); ?>
        </p>
        <p>
            <em><?php esc_html_e( 'Ensure Site ID and Widget ID are configured in Qraga settings.', 'qraga' ); ?></em>
        </p>
        <?php 
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ) {
        // No options to save, return empty instance
        return [];
    }

} 