<?php
/**
 * Provides helper functions.
 *
 * @since	  1.0.0
 *
 * @package	RBM_Support_Tiers
 * @subpackage RBM_Support_Tiers/core
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Returns the main plugin object
 *
 * @since		1.0.0
 *
 * @return		RBM_Support_Tiers
 */
function RBMSUPPORTTIERS() {
	return RBM_Support_Tiers::instance();
}