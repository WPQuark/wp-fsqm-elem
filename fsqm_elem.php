<?php
/*
Plugin Name: eForm Extended Elements
Description: This plugin boilerplate shows how you can extend eForm elements
Plugin URI: https://wpquark.com
Author: Swashata
Author URI: https://swashata.me
Version: 4.2.0
License: GPL3
Text Domain: efom_elm
Domain Path: /translations
*/

/*

    Copyright (C) 2016  WPQuark  contact@wpquark.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once dirname( __FILE__ ) . '/classes/class-eform-extended-element.php';

$eform_ext_elm = new EForm_Extended_Element( __FILE__, dirname( __FILE__ ), '4.2.0' );
