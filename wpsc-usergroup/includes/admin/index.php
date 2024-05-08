<?php

// Load common classes.
foreach ( glob( __DIR__ . '/custom-fields/*.php' ) as $filename ) {
	include_once $filename;
}

// Load email notification classes.
foreach ( glob( __DIR__ . '/email-notifications/*.php' ) as $filename ) {
	include_once $filename;
}

// Load model classes.
foreach ( glob( __DIR__ . '/settings/*.php' ) as $filename ) {
	include_once $filename;
}

// Load widget classes.
foreach ( glob( __DIR__ . '/widget/*.php' ) as $filename ) {
	include_once $filename;
}

// Load tickets.
foreach ( glob( __DIR__ . '/tickets/*.php' ) as $filename ) {
	include_once $filename;
}
