<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_Usergroup' ) ) :

	final class WPSC_Usergroup {

		/**
		 * Object data in key => val pair.
		 *
		 * @var array
		 */
		private $data = array();

		/**
		 * Set whether or not current object properties modified
		 *
		 * @var boolean
		 */
		private $is_modified = false;

		/**
		 * Schema for this model
		 *
		 * @var array
		 */
		public static $schema = array();

		/**
		 * Prevent fields to modify
		 *
		 * @var array
		 */
		public static $prevent_modify = array();

		/**
		 * DB object caching
		 *
		 * @var array
		 */
		private static $cache = array();

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// Apply schema for this model.
			add_action( 'init', array( __CLASS__, 'apply_schema' ), 2 );

			// Get object of this class.
			add_filter( 'wpsc_load_ref_classes', array( __CLASS__, 'load_ref_class' ) );
		}

		/**
		 * Apply schema for this model
		 *
		 * @return void
		 */
		public static function apply_schema() {

			$schema = array(
				'id'          => array(
					'has_ref'          => false,
					'ref_class'        => '',
					'has_multiple_val' => false,
				),
				'name'        => array(
					'has_ref'          => false,
					'ref_class'        => '',
					'has_multiple_val' => false,
				),
				'description' => array(
					'has_ref'          => false,
					'ref_class'        => '',
					'has_multiple_val' => false,
				),
				'members'     => array(
					'has_ref'          => true,
					'ref_class'        => 'wpsc_customer',
					'has_multiple_val' => true,
				),
				'supervisors' => array(
					'has_ref'          => true,
					'ref_class'        => 'wpsc_customer',
					'has_multiple_val' => true,
				),
				'category'    => array(
					'has_ref'          => true,
					'ref_class'        => 'wpsc_category',
					'has_multiple_val' => false,
				),
			);

			foreach ( WPSC_Custom_Field::$custom_fields as $cf ) {

				if ( $cf->field == 'usergroup' ) {
					$schema[ $cf->slug ] = array(
						'has_ref'          => $cf->type::$has_ref,
						'ref_class'        => $cf->type::$ref_class,
						'has_multiple_val' => $cf->type::$has_multiple_val,
					);
				}
			}
			self::$schema = $schema;

			// Prevent modify.
			$prevent_modify       = array( 'id' );
			self::$prevent_modify = apply_filters( 'wpsc_usergroup_prevent_modify', $prevent_modify );
		}

		/**
		 * Model constructor
		 *
		 * @param int $id - Optional. Data record id to retrive object for.
		 */
		public function __construct( $id = 0 ) {

			global $wpdb;

			$id = intval( $id );

			if ( isset( self::$cache[ $id ] ) ) {
				$this->data = self::$cache[ $id ]->data;
				return;
			}

			if ( $id > 0 ) {

				$usergroup = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}psmsc_usergroups WHERE id = " . $id, ARRAY_A );
				if ( ! is_array( $usergroup ) ) {
					return;
				}

				foreach ( $usergroup as $key => $val ) {
					$this->data[ $key ] = $val !== null ? $val : '';
				}
			}
		}

		/**
		 * Magic get function to use with object arrow function
		 *
		 * @param string $var_name - variable name.
		 * @return mixed
		 */
		public function __get( $var_name ) {

			if ( ! isset( $this->data[ $var_name ] ) ||
				$this->data[ $var_name ] == null ||
				$this->data[ $var_name ] == ''
			) {
				return self::$schema[ $var_name ]['has_multiple_val'] ? array() : '';
			}

			if ( self::$schema[ $var_name ]['has_multiple_val'] ) {

				$response = array();
				$values   = $this->data[ $var_name ] ? explode( '|', $this->data[ $var_name ] ) : array();
				foreach ( $values as $val ) {
					$response[] = self::$schema[ $var_name ]['has_ref'] ?
									WPSC_Functions::get_object( self::$schema[ $var_name ]['ref_class'], $val ) :
									$val;
				}
				return $response;

			} else {

				return self::$schema[ $var_name ]['has_ref'] && $this->data[ $var_name ] ?
					WPSC_Functions::get_object( self::$schema[ $var_name ]['ref_class'], $this->data[ $var_name ] ) :
					$this->data[ $var_name ];
			}
		}

		/**
		 * Magic function to use setting object field with arrow function
		 *
		 * @param string $var_name - (Required) property slug.
		 * @param mixed  $value - (Required) value to set for a property.
		 * @return void
		 */
		public function __set( $var_name, $value ) {

			if ( in_array( $var_name, self::$prevent_modify ) ) {
				return;
			}

			$data_val = '';
			if ( self::$schema[ $var_name ]['has_multiple_val'] ) {

				$data_vals = array_map(
					fn( $val ) => is_object( $val ) ? WPSC_Functions::set_object( self::$schema[ $var_name ]['ref_class'], $val ) : $val,
					$value
				);

				$data_val = $data_vals ? implode( '|', $data_vals ) : '';

			} else {

				$data_val = is_object( $value ) ? WPSC_Functions::set_object( self::$schema[ $var_name ]['ref_class'], $value ) : $value;
			}

			if ( isset( $this->data[ $var_name ] ) && $this->data[ $var_name ] == $data_val ) {
				return;
			}

			$this->data[ $var_name ] = $data_val;
			$this->is_modified       = true;
		}

		/**
		 * Clone usergroup.
		 *
		 * @return WPSC_Usergroup
		 */
		public function clone() {

			global $wpdb;

			$data = $this->data;

			unset( $data['id'] );
			$data['name'] = $this->name . ' clone';

			$usergroup = self::insert( $data );

			do_action( 'wpsc_clone_usergroup', $usergroup );

			return $usergroup;
		}

		/**
		 * Save changes made
		 *
		 * @return boolean
		 */
		public function save() {

			global $wpdb;

			if ( ! $this->is_modified ) {
				return true;
			}

			$data    = $this->data;
			$success = true;

			if ( ! isset( $data['id'] ) ) {

				$cf = self::insert( $data );
				if ( $cf ) {
					$this->data = $cf->data;
					$success    = true;
				} else {
					$success = false;
				}
			} else {

				unset( $data['id'] );
				$success = $wpdb->update(
					$wpdb->prefix . 'psmsc_usergroups',
					$data,
					array( 'id' => $this->id )
				);
			}
			$this->is_modified        = false;
			self::$cache[ $this->id ] = $this;
			return $success ? true : false;
		}

		/**
		 * Delete record of given object
		 *
		 * @param WPSC_Usergroup $usergroup - usergroup object.
		 * @return boolean
		 */
		public static function destroy( $usergroup ) {

			global $wpdb;

			do_action( 'wpsc_before_destroy_usergroup', $usergroup );

			$success = $wpdb->delete(
				$wpdb->prefix . 'psmsc_usergroups',
				array( 'id' => $usergroup->id )
			);

			unset( self::$cache[ $usergroup->id ] );

			return ! $success ? false : true;
		}

		/**
		 * Set data to create new object using direct data. Used in find method
		 *
		 * @param array $data - data to set for object.
		 * @return void
		 */
		private function set_data( $data ) {

			foreach ( $data as $var_name => $val ) {
				$this->data[ $var_name ] = $val !== null ? $val : '';
			}
		}

		/**
		 * Find records based on given filters
		 *
		 * @param array   $filter - array containing array items like search, where, orderby, order, page_no, items_per_page, etc.
		 * @param boolean $is_object - return data as array or object. Default object.
		 * @return mixed
		 */
		public static function find( $filter = array(), $is_object = true ) {

			global $wpdb;

			$sql   = 'SELECT * FROM ' . $wpdb->prefix . 'psmsc_usergroups ';
			$where = self::get_where( $filter );

			$filter['items_per_page'] = isset( $filter['items_per_page'] ) ? $filter['items_per_page'] : 0;
			$filter['page_no']        = isset( $filter['page_no'] ) ? $filter['page_no'] : 0;
			$filter['orderby']        = isset( $filter['orderby'] ) ? $filter['orderby'] : 'id';
			$filter['order']          = isset( $filter['order'] ) ? $filter['order'] : 'ASC';

			$order = WPSC_Functions::parse_order( $filter );

			$sql = $sql . $where . $order;
			$results     = $wpdb->get_results( $sql, ARRAY_A );

			// total results.
			$sql = 'SELECT count(id) FROM ' . $wpdb->prefix . 'psmsc_usergroups ';
			$total_items = $wpdb->get_var( $sql . $where );

			$response = WPSC_Functions::parse_response( $results, $total_items, $filter );

			// Return array.
			if ( ! $is_object ) {
				return $response;
			}

			// create and return array of objects.
			$temp_results = array();
			foreach ( $response['results'] as $usergroup ) {

				$ob   = new WPSC_Usergroup();
				$data = array();
				foreach ( $usergroup as $key => $val ) {
					$data[ $key ] = $val;
				}
				$ob->set_data( $data );
				$temp_results[] = $ob;

				// set cache.
				self::$cache[ $ob->id ] = $ob;
			}
			$response['results'] = $temp_results;

			return $response;
		}

		/**
		 * Get where for find method
		 *
		 * @param array $filter - user filter.
		 * @return array
		 */
		private static function get_where( $filter ) {

			$where = array( '1=1' );

			// Set user defined filters.
			$meta_query = isset( $filter['meta_query'] ) && $filter['meta_query'] ? $filter['meta_query'] : array();
			if ( $meta_query ) {
				$meta_query = WPSC_Functions::parse_user_filters( __CLASS__, $meta_query );
				$where[]    = $meta_query . ' ';
			}

			// Search.
			$search = WPSC_Functions::get_filter_search_str( $filter );
			if ( $search ) {
				$search_query = array(
					'CONVERT(name USING utf8) LIKE \'%' . $search . '%\'',
				);
				$where[]      = implode( ' OR ', $search_query );
			}

			return 'WHERE (' . implode( ') AND (', $where ) . ') ';
		}

		/**
		 * Load current class to reference classes
		 *
		 * @param array $classes - Associative array of class names indexed by its slug.
		 * @return array
		 */
		public static function load_ref_class( $classes ) {

			$classes['wpsc_usergroup'] = array(
				'class'    => __CLASS__,
				'save-key' => 'id',
			);
			return $classes;
		}

		/**
		 * Clone new record
		 *
		 * @param array $data - insert data.
		 * @return WPSC_Usergroup
		 */
		public static function insert( $data ) {

			global $wpdb;

			$success = $wpdb->insert(
				$wpdb->prefix . 'psmsc_usergroups',
				$data
			);

			if ( ! $success ) {
				return false;
			}

			$usergroup = new WPSC_Usergroup( $wpdb->insert_id );

			self::$cache[ $usergroup->id ] = $usergroup;

			return $usergroup;
		}

		/**
		 * Customer autocomplete callback
		 *
		 * @param string $term - search term.
		 * @return array
		 */
		public static function usergroup_autocomplete( $term ) {

			$current_user = WPSC_Current_User::$current_user;

			$args = array(
				'search' => $term,
			);

			if ( ! $current_user->is_agent ) {
				$args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'slug'    => 'members',
						'compare' => 'IN',
						'val'     => array( $current_user->customer->id ),
					),
				);
			}

			$args       = apply_filters( 'wpsc_usergroup_autocomplete_filters', $args );
			$usergroups = self::find( $args )['results'];

			$response = array();

			if ( $current_user->is_agent ) {
				$response[] = array(
					'id'    => 0,
					'title' => esc_attr( wpsc__( 'None', 'supportcandy' ) ),
				);
			}

			foreach ( $usergroups as $group ) {
				$response[] = array(
					'id'    => $group->id,
					'title' => $group->name,
				);
			}

			return $response;
		}

		/**
		 * Get usergroups by customer
		 *
		 * @param WPSC_Customer $customer - customer object.
		 * @return array
		 */
		public static function get_by_customer( $customer ) {

			$response = self::find(
				array(
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'slug'    => 'members',
							'compare' => 'IN',
							'val'     => array( $customer->id ),
						),
					),
				)
			);

			return $response['results'];
		}
	}
endif;

WPSC_Usergroup::init();
