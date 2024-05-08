<?php
// Load report classes.
foreach ( glob( WPSC_RP_ABSPATH . 'includes/reports/*.php' ) as $filename ) {
	include_once $filename;
}

// Load report classes.
foreach ( glob( WPSC_RP_ABSPATH . 'includes/reports/custom-field-types/*.php' ) as $filename ) {
	include_once $filename;
}

// Load misc classes.
foreach ( glob( WPSC_RP_ABSPATH . 'includes/misc/*.php' ) as $filename ) {
	include_once $filename;
}

// Load dashboard classes.
foreach ( glob( WPSC_RP_ABSPATH . 'includes/dashboard/*.php' ) as $filename ) {
	include_once $filename;
}
