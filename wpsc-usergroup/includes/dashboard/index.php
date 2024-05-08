<?php
// Load widgets types.
foreach ( glob( __DIR__ . '/widgets/*.php' ) as $filename ) {
	include_once $filename;
}
