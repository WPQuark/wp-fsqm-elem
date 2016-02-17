<?php

/**
 * This class is responsible for extending FSQM Pro
 * With two more elements
 */
class IPT_FSQM_Ext_Elm {
	public static $absfile;
	public static $absdir;
	public static $version;

	public function __construct( $absfile, $absdir, $version ) {
		// Set the static variables
		self::$absfile = $absfile;
		self::$absdir = $absdir;
		self::$version = $version;

		// Add to the filter to add our new elements
	}


}
