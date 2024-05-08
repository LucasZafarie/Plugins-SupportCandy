<?php

// Load common classes.
foreach ( glob( __DIR__ . '/settings/*.php' ) as $filename ) {
	include_once $filename;
}

// Load notification classes.
foreach ( glob( __DIR__ . '/email-notifications/*.php' ) as $filename ) {
	include_once $filename;
}

// Load notification classes.
foreach ( glob( __DIR__ . '/widget/*.php' ) as $filename ) {
	include_once $filename;
}
