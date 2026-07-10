<?php
/**
 * Frontend Calendar Widget Class
 *
 * @package WP_Booking_System_Luca
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Booking_System_Luca_Widget Class
 */
class WP_Booking_System_Luca_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'WP_Booking_System_Luca_widget',
			__( 'Booking Calendar', 'wp-booking-system-luca' ),
			array(
				'description' => __( 'Display a monthly calendar showing booking availability and allowing date selection.', 'wp-booking-system-luca' ),
			)
		);
	}

	/**
	 * Output the widget content.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Booking Calendar', 'wp-booking-system-luca' );
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		// Reuse the shared, interactive calendar renderer (static enqueued JS,
		// range selection, tooltips) so the widget behaves exactly like the
		// shortcode and block. The title is emitted above, so render without one.
		echo wp_booking_system_luca()->frontend->render_booking_calendar( array( 'title' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Output the settings form.
	 *
	 * @param array $instance Current settings.
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Booking Calendar', 'wp-booking-system-luca' );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'wp-booking-system-luca' ); ?>
			</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php
	}

	/**
	 * Save widget settings.
	 *
	 * @param array $new_instance New settings.
	 * @param array $old_instance Old settings.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = array();
		$instance['title'] = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';

		return $instance;
	}
}

