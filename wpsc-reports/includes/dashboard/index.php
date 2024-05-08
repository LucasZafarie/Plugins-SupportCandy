<?php
// Load widgets classes.
foreach ( glob( WPSC_RP_ABSPATH . 'includes/dashboard/widgets/*.php' ) as $filename ) {
	include_once $filename;
}
