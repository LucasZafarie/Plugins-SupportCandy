<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_CF_SF_DATE' ) ) :

	final class WPSC_CF_SF_DATE {

		/**
		 * Slug for this custom field type
		 *
		 * @var string
		 */
		public static $slug = 'df_sf_date';

		/**
		 * Set whether this custom field type is of type date
		 *
		 * @var boolean
		 */
		public static $is_date = true;

		/**
		 * Set whether this custom field type has applicable to date range
		 *
		 * @var boolean
		 */
		public static $has_date_range = false;

		/**
		 * Set whether this custom field type has multiple values
		 *
		 * @var boolean
		 */
		public static $has_multiple_val = false;

		/**
		 * Data type for column created in tickets table
		 *
		 * @var string
		 */
		public static $data_type = 'DATETIME NULL DEFAULT NULL';

		/**
		 * Set whether this custom field type has reference to other class
		 *
		 * @var boolean
		 */
		public static $has_ref = true;

		/**
		 * Reference class for this custom field type so that its value(s) return with object or array of objects automatically. Empty string indicate no reference.
		 *
		 * @var string
		 */
		public static $ref_class = 'datetime';

		/**
		 * Set whether this custom field field type is system default (no fields can be created from it).
		 *
		 * @var boolean
		 */
		public static $is_default = true;

		/**
		 * Set whether this field type has extra information that can be used in ticket form, edit custom fields, etc.
		 *
		 * @var boolean
		 */
		public static $has_extra_info = false;

		/**
		 * Set whether this custom field type can accept personal info.
		 *
		 * @var boolean
		 */
		public static $has_personal_info = false;

		/**
		 * Set whether fields created from this custom field type is allowed in create ticket form
		 *
		 * @var boolean
		 */
		public static $is_ctf = false;

		/**
		 * Set whether fields created from this custom field type is allowed in ticket list
		 *
		 * @var boolean
		 */
		public static $is_list = false;

		/**
		 * Set whether fields created from this custom field type is allowed in ticket filter
		 *
		 * @var boolean
		 */
		public static $is_filter = false;

		/**
		 * Set whether fields created from this custom field type can be given character limits
		 *
		 * @var boolean
		 */
		public static $has_char_limit = false;

		/**
		 * Set whether this custom field has user given custom options
		 *
		 * @var boolean
		 */
		public static $has_options = false;

		/**
		 * Set whether fields created from this custom field type can be available for ticket list sorting
		 *
		 * @var boolean
		 */
		public static $is_sort = false;

		/**
		 * Set whether fields created from this custom field type can be auto-filled
		 *
		 * @var boolean
		 */
		public static $is_auto_fill = false;

		/**
		 * Set whether fields created from this custom field type can have placeholder
		 *
		 * @var boolean
		 */
		public static $is_placeholder = false;

		/**
		 * Set whether fields created from this custom field type is applicable for visibility conditions in create ticket form
		 *
		 * @var boolean
		 */
		public static $is_visibility_conditions = false;

		/**
		 * Set whether fields created from this custom field type is applicable for macros
		 *
		 * @var boolean
		 */
		public static $has_macro = true;

		/**
		 * Initialize the class
		 *
		 * @return void
		 */
		public static function init() {

			// Get object of this class.
			add_filter( 'wpsc_load_ref_classes', array( __CLASS__, 'load_ref_class' ) );
		}

		/**
		 * Load current class to ref classes
		 *
		 * @param array $classes - array of ref classes.
		 * @return array
		 */
		public static function load_ref_class( $classes ) {

			$classes[ self::$slug ] = array(
				'class'    => __CLASS__,
				'save-key' => 'id',
			);
			return $classes;
		}

		/**
		 * Print edit custom field properties
		 *
		 * @param WPSC_Custom_Fields $cf - custom field object.
		 * @param string             $field_class - class name of field category.
		 * @return void
		 */
		public static function get_edit_custom_field_properties( $cf, $field_class ) {
			?>

			<div data-type="date-format" data-required="false" class="wpsc-input-group date-format">
				<div class="label-container">
					<label for=""><?php echo esc_attr( wpsc__( 'Date format', 'supportcandy' ) ); ?></label>
				</div>
				<input type="text" name="date_format" value="<?php echo esc_attr( $cf->date_format ); ?>" autocomplete="off" />
			</div>
			<?php
		}

		/**
		 * Set custom field properties. Can be used by add/edit custom field.
		 * Ignore phpcs nonce issue as we already checked where it is called from.
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param string            $field_class - class of field category.
		 * @return void
		 */
		public static function set_cf_properties( $cf, $field_class ) {

			$cf->date_format = isset( $_POST['date_format'] ) ? sanitize_text_field( wp_unslash( $_POST['date_format'] ) ) : ''; // phpcs:ignore

			// save!
			$cf->save();
		}

		/**
		 * Return orderby string
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @return string
		 */
		public static function get_orderby_string( $cf ) {

			return 't.' . $cf->slug;
		}

		/**
		 * Returns printable ticket value for custom field. Can be used in export tickets, replace macros etc.
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param WPSC_Ticket       $ticket - ticket object.
		 * @param string            $module - module name.
		 * @return string
		 */
		public static function get_ticket_field_val( $cf, $ticket, $module = '' ) {

			$general_settings = get_option( 'wpsc-gs-general' );
			$format           = $cf->date_format ? $cf->date_format : $general_settings['default-date-format'];
			$date             = $ticket->{$cf->slug};
			$value = is_object( $date ) ? wp_date( $format, $date->setTimezone( wp_timezone() )->getTimestamp() ) : '';

			return apply_filters( 'wpsc_ticket_field_val_sf_date', $value, $cf, $ticket, $module );
		}

		/**
		 * Parse filter and return sql query to be merged in ticket model query builder
		 *
		 * @param WPSC_Custom_Field $cf - custom field of this type.
		 * @param mixed             $compare - comparison operator.
		 * @param mixed             $val - value to compare.
		 * @return string
		 */
		public static function parse_filter( $cf, $compare, $val ) {

			$str = '';

			switch ( $compare ) {

				case '=':
					$from = WPSC_Functions::get_utc_date_str( $val . ' 00:00:00' );
					$to   = WPSC_Functions::get_utc_date_str( $val . ' 23:59:59' );
					$str  = 't.' . $cf->slug . ' BETWEEN \'' . esc_sql( $from ) . '\' AND \'' . esc_sql( $to ) . '\'';
					break;

				case '<':
					$from = WPSC_Functions::get_utc_date_str( $val . ' 00:00:00' );
					$str  = 't.' . $cf->slug . $compare . '\'' . esc_sql( $from ) . '\'';
					break;

				case '>':
					$to  = WPSC_Functions::get_utc_date_str( $val . ' 23:59:59' );
					$str = 't.' . $cf->slug . $compare . '\'' . esc_sql( $to ) . '\'';
					break;

				case '<=':
					$from = WPSC_Functions::get_utc_date_str( $val . ' 00:00:00' );
					$to   = WPSC_Functions::get_utc_date_str( $val . ' 23:59:59' );
					$arr  = array(
						't.' . $cf->slug . $compare . '\'' . esc_sql( $from ) . '\'',
						't.' . $cf->slug . ' BETWEEN \'' . esc_sql( $from ) . '\' AND \'' . esc_sql( $to ) . '\'',
					);
					$str  = '(' . implode( ' OR ', $arr ) . ')';
					break;

				case '>=':
					$from = WPSC_Functions::get_utc_date_str( $val . ' 00:00:00' );
					$to   = WPSC_Functions::get_utc_date_str( $val . ' 23:59:59' );
					$arr  = array(
						't.' . $cf->slug . $compare . '\'' . esc_sql( $to ) . '\'',
						't.' . $cf->slug . ' BETWEEN \'' . esc_sql( $from ) . '\' AND \'' . esc_sql( $to ) . '\'',
					);
					$str  = '(' . implode( ' OR ', $arr ) . ')';
					break;

				case 'BETWEEN':
					$from = $val['operand_val_1'];
					$to   = $val['operand_val_2'];
					if ( preg_match( '/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $from ) ) {
						$from = WPSC_Functions::get_utc_date_str( $val['operand_val_1'] );
						$to   = WPSC_Functions::get_utc_date_str( $val['operand_val_2'] );
					} else {
						$from = WPSC_Functions::get_utc_date_str( $val['operand_val_1'] . ' 00:00:00' );
						$to   = WPSC_Functions::get_utc_date_str( $val['operand_val_2'] . ' 23:59:59' );
					}
					$str = 't.' . $cf->slug . ' BETWEEN \'' . esc_sql( $from ) . '\' AND \'' . esc_sql( $to ) . '\'';
					break;

				default:
					$str = '1=1';
			}

			return $str;
		}
	}
endif;

WPSC_CF_SF_DATE::init();
