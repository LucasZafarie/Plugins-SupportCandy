<?php

// admin side classes.
foreach ( glob( __DIR__ . '/admin/*.php' ) as $filename ) {
	include_once $filename;
}
