<?php

/**
 * All hooked functions used by Decorations extension.
 * @ingroup Extensions
 * @author Josef Martiňák
 * @license MIT
 * @file
 */

class DecorationsHooks {

	/**
	 * Get wikipath
	 * @return string
	 */
	public static function getWikipath()
	{
		return (!empty($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
	} 
	
}