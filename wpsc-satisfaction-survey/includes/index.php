<?php

// Load common classes.
foreach ( glob( __DIR__ . '/admin/*.php' ) as $filename ) {
	include_once $filename;
}

// Load custom field types classes.
foreach ( glob( __DIR__ . '/custom-field-types/*.php' ) as $filename ) {
	include_once $filename;
}

// Load model classes.
foreach ( glob( __DIR__ . '/model/*.php' ) as $filename ) {
	include_once $filename;
}

// Load report classes.
foreach ( glob( __DIR__ . '/reports/*.php' ) as $filename ) {
	include_once $filename;
}

// Load dashboard classes.
foreach ( glob( __DIR__ . '/dashboard/*.php' ) as $filename ) {
	include_once $filename;
}
