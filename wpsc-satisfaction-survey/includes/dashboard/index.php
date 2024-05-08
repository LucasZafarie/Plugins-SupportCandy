<?php

// Load widgets classes.
foreach ( glob( __DIR__ . '/widgets/*.php' ) as $filename ) {
	include_once $filename;
}
