<?php

// Load common classes.
foreach ( glob( __DIR__ . '/admin/*.php' ) as $filename ) {
	include_once $filename;
}

// Load model classes.
foreach ( glob( __DIR__ . '/model/*.php' ) as $filename ) {
	include_once $filename;
}

// Load custom field types.
foreach ( glob( __DIR__ . '/custom-field-types/*.php' ) as $filename ) {
	include_once $filename;
}

// Load dashboard types.
foreach ( glob( __DIR__ . '/dashboard/*.php' ) as $filename ) {
	include_once $filename;
}
