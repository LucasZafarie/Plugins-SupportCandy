<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_UPW_Rating' ) ) :

	final class WPSC_UPW_Rating {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// SF widget.
			add_action( 'wpsc_user_profile_widgets_after', array( __CLASS__, 'user_profile_widgets' ) );
		}

		/**
		 * Prints body of current widget
		 *
		 * @param WPSC_Customer $customer - customer object.
		 * @return void
		 */
		public static function user_profile_widgets( $customer ) {

			$filters = array(
				'items_per_page' => 10,
				'orderby'        => 'id',
				'order'          => 'DESC',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'slug'    => 'customer',
						'compare' => '=',
						'val'     => $customer->id,
					),
					array(
						'slug'    => 'rating',
						'compare' => 'NOT IN',
						'val'     => array( '0' ),
					),
				),
			);
			$tickets = WPSC_Ticket::find( $filters )['results'];
			?>
			<div class="wpsc-xs-6 wpsc-sm-6 wpsc-md-6 wpsc-lg-6">
				<div class="wpsc-it-widget wpsc-itw-add-rec">
					<div class="wpsc-widget-header">
						<h2><?php echo esc_attr__( 'Customer feedback', 'wpsc-sf' ); ?></h2>
						<span>
							<div class="info-list-item">
								<div class="info-label">
									<?php
										$url = admin_url( 'admin.php?page=wpsc-customer-feedback' );
										echo '<a class="wpsc-link" target="_blank" href="' . esc_attr( $url ) . '">' . esc_attr__( 'View more', 'wpsc-sf' ) . '</a>';
									?>
								</div>
							</div>
						</span>
					</div>
					<div class="wpsc-widget-body wpsc-widget-scroller">
						<?php
						if ( $tickets ) {
							?>
							<table class="wpsc-up-feedback">
								<thead>
									<tr>
										<th><?php echo esc_attr__( 'Ticket', 'supportcandy' ); ?></th>
										<th><?php echo esc_attr__( 'Subject', 'supportcandy' ); ?></th>
										<th><?php echo esc_attr__( 'Rating', 'supportcandy' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php
									foreach ( $tickets as $ticket ) {
										?>
										<tr>
											<td>
												<?php
													$url = admin_url( 'admin.php?page=wpsc-tickets&section=ticket-list&id=' . $ticket->id );
													echo '<a class="wpsc-link" href="' . esc_attr( $url ) . '" target="_blank">#' . esc_attr( $ticket->id ) . '</a>';
												?>
											</td>
											<td><?php echo esc_attr( $ticket->subject ); ?></td>
											<td>
												<div class="wpsc-tag" style="background-color:<?php echo esc_attr( $ticket->rating->bg_color ); ?>; color:<?php echo esc_attr( $ticket->rating->color ); ?>; margin-right: 10px;">
												<?php echo esc_attr( $ticket->rating->name ); ?>
												</div>
											</td>
										</tr>
										<?php
									}
									?>
								</tbody>
							</table>
							<script>
									jQuery('table.wpsc-up-feedback').DataTable({
										ordering: false,
										pageLength: 10,
										searching: false,
										bLengthChange: false,
										info: false,
										paging: false,
										columnDefs: [
											{ targets: '_all', className: 'dt-left' },
											{
												"targets": '_all', // All other columns
												"searchable": false,
												"orderable": true
											}
										],
										language: supportcandy.translations.datatables
									});
							</script>
							<?php
						} else {
							?>
							<div class="wpsc-widget-default"><?php esc_attr_e( 'Not Applicable', 'wpsc-sf' ); ?></div>
							<?php
						}
						?>
					</div>
				</div>
			</div>
			<?php
		}
	}
endif;

WPSC_UPW_Rating::init();
