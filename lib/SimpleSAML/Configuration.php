<?php


/**
 * SimpleSAMLphp
 *
 * PHP versions 4 and 5
 *
 * LICENSE: See the COPYING file included in this distribution.
 *
 * @author Andreas �kre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 */
 
/**
 * Configuration of SimpleSAMLphp
 */
class SimpleSAML_Configuration {

	private static $instance = null;

	private $configpath = null;	
	private $configuration = null;

	// private constructor restricts instantiaton to getInstance()
	private function __construct($configpath) {

		$this->configpath = $configpath;

	}
	
	public static function getInstance() {
		return self::$instance;
	}
	
	public static function init($path) {
		self::$instance = new SimpleSAML_Configuration($path);
	}

	private function loadConfig() {
		require_once($this->configpath . '/config.php');
		$this->configuration = $config;
	}

	public function getValue($name) {
		if (!isset($this->configuration)) {
			$this->loadConfig();
		}

		/* Avoid notice about non-existant member of array
		 * if an option isn't set.
		 */
		if (!array_key_exists($name, $this->configuration)) {
			return NULL;
		}

		return $this->configuration[$name];
	}


	/* Retrieve the base directory for this simpleSAMLphp installation.
	 * This function first checks the 'basedir' configuration option. If
	 * this option is undefined or NULL, then we fall back to looking at
	 * the current filename.
	 *
	 * Returns:
	 *  The absolute path to the base directory for this simpleSAMLphp
	 *  installation. This path will always end with a slash.
	 */
	public function getBaseDir() {
		/* Check if a directory is configured in the configuration
		 * file.
		 */
		$dir = $this->getValue('basedir');
		if($dir !== NULL) {
			/* Add trailing slash if it is missing. */
			if(substr($dir, -1) !== '/') {
				$dir .= '/';
			}

			return $dir;
		}

		/* The directory wasn't set in the configuration file. Our
		 * path is <base directory>/lib/SimpleSAML/Configuration.php
		 */

		$dir = __FILE__;
		assert('basename($dir) === "Configuration.php"');

		$dir = dirname($dir);
		assert('basename($dir) === "SimpleSAML"');

		$dir = dirname($dir);
		assert('basename($dir) === "lib"');

		$dir = dirname($dir);

		/* Add trailing slash. */
		$dir .= '/';

		return $dir;
	}

}

?>