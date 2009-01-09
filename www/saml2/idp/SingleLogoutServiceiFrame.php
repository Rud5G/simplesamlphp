<?php

/**
 * This SAML 2.0 endpoint can receive incoming LogoutRequests. It will also send LogoutResponses, 
 * and LogoutRequests and also receive LogoutResponses. It is implemeting SLO at the SAML 2.0 IdP.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */

require_once('../../_include.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();

SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutServiceiFrame: Accessing SAML 2.0 IdP endpoint SingleLogoutService (iFrame version)');

if (!$config->getValue('enable.saml20-idp', false))
	SimpleSAML_Utilities::fatalError(isset($session) ? $session->getTrackID() : null, 'NOACCESS');

try {
	$idpentityid = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}

SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutServiceiFrame: Got IdP entity id: ' . $idpentityid);



$logouttype = 'traditional';
$idpmeta = $metadata->getMetaDataCurrent('saml20-idp-hosted');
if (array_key_exists('logouttype', $idpmeta)) $logouttype = $idpmeta['logouttype'];

if ($logouttype !== 'iframe') 
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS', new Exception('This IdP is configured to use logout type [' . $logouttype . '], but this endpoint is only available for IdP using logout type [iframe]'));



/**
 * The $logoutInfo contains information about the current logout operation.
 * It can have the following attributes:
 * - 'RelayState' - The RelayState which should be returned to the SP which initiated the logout operation.
 * - 'Issuer' - The entity id of the SP which initiated the logout operation.
 * - 'RequestID' - The id of the LogoutRequest which initiated the logout operation.
 */
$logoutInfo = array();


/**
 * This function retrieves the logout info with the given ID.
 *
 * @param $id  The identifier of the logout info.
 */
function fetchLogoutInfo($id) {
	global $session;
	global $logoutInfo;

	$logoutInfo = $session->getData('idplogoutresponsedata', $id);

	if($logoutInfo === NULL) {
		SimpleSAML_Logger::warning('SAML2.0 - IdP.SingleLogoutService: Lost logout information.');
	}
}


/**
 * This function saves the logout info with the given ID.
 *
 * @param $id  The identifier the logout info should be saved with.
 */
function saveLogoutInfo($id) {
	global $session;
	global $logoutInfo;

	$session->setData('idplogoutresponsedata', $id, $logoutInfo);
}


// Include XAJAX definition.
require_once(SimpleSAML_Utilities::resolvePath('libextinc') . '/xajax/xajax.inc.php');



/*
 * This function is called via AJAX and will send LogoutRequest to one single SP by
 * sending a LogoutRequest using HTTP-REDIRECT
 */
function updateslostatus() {

	SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutServiceiFrame: Accessing SAML 2.0 IdP endpoint SingleLogoutService (iFrame version) within updateslostatus() ');

	$config = SimpleSAML_Configuration::getInstance();
	$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
	$session = SimpleSAML_Session::getInstance();

	$idpentityid = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
	
	$templistofsps = $session->get_sp_list(SimpleSAML_Session::STATE_ONLINE);
	$listofsps = array();
	foreach ($templistofsps AS $spentityid) {
		if (!empty($_COOKIE['spstate-' . sha1($spentityid)])) $listofsps[] = $spentityid;
	}
	SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutServiceiFrame: templistofsps ' . var_export($templistofsps, TRUE));
	SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutServiceiFrame:     listofsps ' . var_export($listofsps, TRUE));


	// Using template object to be able to translate name of service provider.
	$t = new SimpleSAML_XHTML_Template($config, 'logout-iframe.php');

    // Instantiate the xajaxResponse object
    $objResponse = new xajaxResponse();

	foreach ($listofsps AS $spentityid) {

		SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutServiceiFrame: Completed ' . $spentityid);
		
		// add a command to the response to assign the innerHTML attribute of
		// the element with id="SomeElementId" to whatever the new content is
		
		$spmetadata = $metadata->getMetaData($spentityid, 'saml20-sp-remote');
		$name = array_key_exists('name', $spmetadata) ? $spmetadata['name'] : $spentityid;
		
		$spname = is_array($name) ? $t->getTranslation($name) : $name;
		
		$objResponse->addScriptCall('slocompletesp', 'e' . sha1($spentityid));

	}
	
	if (count($templistofsps) === count($listofsps)) {

		$templistofsps = $session->get_sp_list(SimpleSAML_Session::STATE_ONLINE);
		foreach ($templistofsps AS $spentityid) {
			$session->set_sp_logout_completed($spentityid);
		}

		$objResponse->addScriptCall('slocompleted');

		/**
		 * Clean up session object to save storage.
		 */
		if ($config->getValue('debug', false)) 
			SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutService: Session Size before cleaning: ' . $session->getSize());
			
		$session->clean();
		
		if ($config->getValue('debug', false)) 
			SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutService: Session Size after cleaning: ' . $session->getSize());

	} else {
		SimpleSAML_Logger::debug('SAML2.0 - sp_logout_completed FALSE');
	}
    
    //return the  xajaxResponse object
    return $objResponse;
}



$xajax = new xajax();
$xajax->registerFunction("updateslostatus");
$xajax->processRequests();





/*
 * If we get an LogoutRequest then we initiate the logout process.
 */
if (isset($_GET['SAMLRequest'])) {

	SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutService: Got SAML reuqest');

	$binding = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);

	try {
		$logoutrequest = $binding->decodeLogoutRequest($_GET);

		if ($binding->validateQuery($logoutrequest->getIssuer(),'IdP')) {
			SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutService: Valid signature found for '.$logoutrequest->getRequestID());
		}

	} catch(Exception $exception) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'LOGOUTREQUEST', $exception);
	}
	
	// Extract some parameters from the logout request
	#$requestid = $logoutrequest->getRequestID();
	$requester = $logoutrequest->getIssuer();
	#$relayState = $logoutrequest->getRelayState();
	$responder = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
	
	SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutService: got Logoutrequest from ' . $logoutrequest->getIssuer());
	SimpleSAML_Logger::stats('saml20-idp-SLO spinit ' . $requester . ' ' . $responder);
	

	$session->doLogout();


	/* Fill in the $logoutInfo associative array with information about this logout request. */
	$logoutInfo['Issuer'] = $logoutrequest->getIssuer();
	$logoutInfo['RequestID'] = $logoutrequest->getRequestID();

	$relayState = $logoutrequest->getRelayState();
	if($relayState !== NULL) {
		$logoutInfo['RelayState'] = $relayState;
	}
		
	SimpleSAML_Logger::debug('SAML2.0 - IDP.SingleLogoutService: Setting cached request with issuer ' . $logoutrequest->getIssuer());
	
	$session->set_sp_logout_completed($logoutrequest->getIssuer());


/*
 * We receive a Logout Response to a Logout Request that we have issued earlier.
 * If so, there is a misconfiguration.
 */
} elseif (isset($_GET['SAMLResponse'])) {

	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'LOGOUTRESPONSE', 
		new Exception('The SP is likely to be misconfigured. The LogoutResponse is sent to wrong endpoint. This iFrame endpoint only accepts LogoutRequests, and the response is to be sent to a separate endpoint. Please revisit the IdP-Remote metadata on the SP.')
	);

} else {
	/*
	 * We have no idea what to do here. It is neither a logout request, a logout
	 * response nor a response from bridged SLO.
	 */
	SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutService: No request, response or bridge');
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'SLOSERVICEPARAMS');
}


// Debug entries in the log about what services the user is logged into.
$session->dump_sp_sessions();



/*
 * Generate a list of all service providers, and create a LogoutRequest message for all these SPs.
 */
$listofsps = $session->get_sp_list();
$sparray = array();
$sparrayNoLogout = array();
foreach ($listofsps AS $spentityid) {

	// ($issuer, $receiver, $nameid, $nameidformat, $sessionindex, $mode) {
	$nameId = $session->getSessionNameId('saml20-sp-remote', $spentityid);
	if($nameId === NULL) {
		$nameId = $session->getNameID();
	}


	$spmetadata = $metadata->getMetaData($spentityid, 'saml20-sp-remote');
	$name = array_key_exists('name', $spmetadata) ? $spmetadata['name'] : $spentityid;

	try {	
		$lr = new SimpleSAML_XML_SAML20_LogoutRequest($config, $metadata);
		$req = $lr->generate($idpentityid, $spentityid, $nameId, $session->getSessionIndex(), 'IdP');
		$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
		// $request, $localentityid, $remoteentityid, $relayState = null, $endpoint = 'SingleSignOnService', $direction = 'SAMLRequest', $mode = 'SP'
		$url = $httpredirect->getRedirectURL($req, $idpentityid, $spentityid, NULL, 'SingleLogoutService', 'SAMLRequest', 'IdP');

	
		$sparray[$spentityid] = array('url' => $url, 'name' => $name);
		
	} catch (Exception $e) {
		
		$sparrayNoLogout[$spentityid] = array('name' => $name);
		
	}

}


SimpleSAML_Logger::debug('SAML2.0 - SP Counter. other SPs with SLO support (' . count($sparray) . ')  without SLO support (' . count($sparrayNoLogout) . ')');


#print_r($sparray);






/*
 * Logout procedure is done and we send a Logout Response back to the SP
 */

try {

	if(!$logoutInfo) SimpleSAML_Utilities::fatalError($session->getTrackID(), 'LOGOUTINFOLOST');
	SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutService: Found logout info with these keys: ' . join(',', array_keys($logoutInfo)));
	
	/*
	 * Check if the Single Logout procedure is initated by an SP (alternatively IdP initiated SLO)
	 */
	if (array_key_exists('Issuer', $logoutInfo)) {
		
		/**
		 * Create a Logot Response.
		 */
		$rg = new SimpleSAML_XML_SAML20_LogoutResponse($config, $metadata);
	
		// generate($issuer, $receiver, $inresponseto, $mode )
		$logoutResponseXML = $rg->generate($idpentityid, $logoutInfo['Issuer'], $logoutInfo['RequestID'], 'IdP');
	
		// Create a HTTP-REDIRECT Binding.
		$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
	
		// Find the relaystate if cached.
		$relayState = isset($logoutInfo['RelayState']) ? $logoutInfo['RelayState'] : null;
	
		$logoutresponse = NULL;
		/*
		 * If the user is not logged into any other SPs, send the LogoutResponse immediately
		 */
		if (count($sparray) === 0) {
			$httpredirect->sendMessage($logoutResponseXML, $idpentityid, $logoutInfo['Issuer'], $relayState, 'SingleLogoutService', 'SAMLResponse', 'IdP');
			exit;
		} else {
			$logoutresponse = $httpredirect->getRedirectURL($logoutResponseXML, $idpentityid, $logoutInfo['Issuer'], $relayState, 'SingleLogoutService', 'SAMLResponse', 'IdP');
		}

		
	} elseif (array_key_exists('RelayState', $logoutInfo)) {

		SimpleSAML_Utilities::redirect($logoutInfo['RelayState']);
		exit;
		
	} else {
	
		echo 'You are logged out'; exit;
	
	}

} catch(Exception $exception) {
	
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'GENERATELOGOUTRESPONSE', $exception);
	
}




$spmeta = $metadata->getMetaData($requester, 'saml20-sp-remote');
$spname = $requester;
if (array_key_exists('name', $spmeta)) $spname = $spmeta['name'];







$et = new SimpleSAML_XHTML_Template($config, 'logout-iframe.php');

$et->data['header'] = 'Global logout';
$et->data['sparray'] = $sparray;
$et->data['sparrayNoLogout'] = $sparrayNoLogout;

$et->data['logoutresponse'] = $logoutresponse;
$et->data['xajax'] = $xajax;
$et->data['requesterName'] = $spname;

$et->data['head'] = $xajax->getJavascript();

$et->show();

exit(0);




?>