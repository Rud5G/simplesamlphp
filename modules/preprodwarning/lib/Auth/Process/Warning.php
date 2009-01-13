<?php

/**
 * Give a warning that the user is accessing a test system, not a production system.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_preprodwarning_Auth_Process_Warning extends SimpleSAML_Auth_ProcessingFilter {



	/**
	 * Process a authentication response.
	 *
	 * This function saves the state, and redirects the user to the page where the user
	 * can authorize the release of the attributes.
	 *
	 * @param array $state  The state of the response.
	 */
	public function process(&$state) {
		assert('is_array($state)');

		/* Save state and redirect. */
		$id = SimpleSAML_Auth_State::saveState($state, 'consent:request');
		$url = SimpleSAML_Module::getModuleURL('preprodwarning/showwarning.php');
		SimpleSAML_Utilities::redirect($url, array('StateId' => $id));
	}
	


}

?>