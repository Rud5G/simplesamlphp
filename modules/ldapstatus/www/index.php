<?php


$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

if (!$session->isValid('login-admin') ) {
	SimpleSAML_Utilities::redirect('/' . $config->getBaseURL() . 'auth/login-admin.php',
		array('RelayState' => SimpleSAML_Utilities::selfURL())
	);
}


function myErrorHandler($errno, $errstr, $errfile, $errline) {

    switch ($errno) {
    case E_USER_ERROR:
    	SimpleSAML_Logger::error('PHP_ERROR   : [' . $errno . '] ' . $errstr . '. Fatal error on line ' . $errline . ' in file ' . $errfile);
    	break;

    case E_USER_WARNING:
    	SimpleSAML_Logger::error('PHP_WARNING : [' . $errno . '] ' . $errstr . '. Warning on line ' . $errline . ' in file ' . $errfile);
    	break;

    case E_USER_NOTICE:
    	SimpleSAML_Logger::error('PHP_WARNING : [' . $errno . '] ' . $errstr . '. Warning on line ' . $errline . ' in file ' . $errfile);        
    	break;

    default:
    	SimpleSAML_Logger::error('PHP_UNKNOWN : [' . $errno . '] ' . $errstr . '. Unknown error on line ' . $errline . ' in file ' . $errfile);        
        break;
    }

    /* Don't execute PHP internal error handler */
    return true;
}
$old_error_handler = set_error_handler("myErrorHandler");

$ldapconfig = $config->copyFromBase('loginfeide', 'config-login-feide.php');
$ldapStatusConfig = $config->copyFromBase('ldapstatus', 'module_ldapstatus.php');

$pingcommand = $ldapStatusConfig->getValue('ping');

$debug = $ldapconfig->getValue('ldapDebug', FALSE);

$orgs = $ldapconfig->getValue('orgldapconfig');

#echo '<pre>'; print_r($orgs); exit;



function phpping($host, $port) {

	SimpleSAML_Logger::debug('ldapstatus phpping(): ping [' . $host . ':' . $port . ']' );

	$timeout = 1.0;
	$socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
	@fclose($socket);
	if ($errno) {
		return array(FALSE, $errno . ':' . $errstr . ' [' . $host . ':' . $port . ']');
	} else {		
		return array(TRUE,NULL);
	}
}

function is_in_array($needles, $haystack) {
	$needles = SimpleSAML_Utilities::arrayize($needles);
	foreach($needles AS $needle) {
		if (array_key_exists($needle, $haystack) && !empty($haystack[$needle])) return TRUE;
	}
	return FALSE;
}

function checkConfig($conf, $req) {
	$err = array();
	foreach($req AS $r) {
		
		if (!is_in_array($r, $conf)) {
			$err[] = 'missing or empty: ' . join(', ', SimpleSAML_Utilities::arrayize($r));
		}
	}
	if (count($err) > 0) {
		return array(FALSE, 'Missing: ' . join(', ', $err));
	}
	return array(TRUE, NULL);	
}

$results = NULL;

$results = $session->getData('module:ldapstatus', 'results');
if (empty($results)) {
	$results = array();
} elseif (array_key_exists('reset', $_GET) && $_GET['reset'] === '1') {
	$results = array();
}

#echo('<pre>'); print_r($results); exit;


$start = microtime(TRUE);
$previous = microtime(TRUE);

$maxtime = $ldapStatusConfig->getValue('maxExecutionTime', 15); 


foreach ($orgs AS $orgkey => $orgconfig) {
	
	$previous = microtime(TRUE);
	
	if ((microtime(TRUE) - $start) > $maxtime) {
		SimpleSAML_Logger::debug('ldapstatus: Completing execution after maxtime [' .(microtime(TRUE) - $start) . ' of maxtime ' . $maxtime . ']');
		break;
	}
	if (array_key_exists($orgkey, $results)) {
		SimpleSAML_Logger::debug('ldapstatus: Skipping org already tested [' .$orgkey. ']');
		continue;
	} else {
		SimpleSAML_Logger::debug('ldapstatus: Not Skipping org: [' .$orgkey. ']');
	}

	SimpleSAML_Logger::debug('ldapstatus: Executing test on [' .$orgkey . ']');


	$results[$orgkey] = array();

	$results[$orgkey]['config'] = checkConfig($orgconfig, array('description', 'searchbase', 'hostname'));
	$results[$orgkey]['configMeta'] = checkConfig($orgconfig, array(array('contactMail', 'contactURL')));
	$results[$orgkey]['configTest'] = checkConfig($orgconfig, array('testUser', 'testPassword'));

	if (!$results[$orgkey]['config'][0]) {
		$results[$orgkey]['time'] = microtime(TRUE) - $previous;
		continue;
	}

	$urldef = explode(' ', $orgconfig['hostname']);
	$url = parse_url($urldef[0]);
	$port = 389;
	if (!empty($url['scheme']) && $url['scheme'] === 'ldaps') $port = 636;
	if (!empty($url['port'])) $port = $url['port'];
	
	SimpleSAML_Logger::debug('ldapstatus Url parse [' . $orgconfig['hostname'] . '] => [' . $url['host'] . ']:[' . $port . ']' );


	$results[$orgkey]['ping'] = phpping($url['host'], $port);

	if (!$results[$orgkey]['ping'][0]) {
		$results[$orgkey]['time'] = microtime(TRUE) - $previous;
		continue;
	}
	
	// LDAP Connect
	try {
		$ldap = new SimpleSAML_Auth_LDAP($orgconfig['hostname'], (array_key_exists('enable_tls', $orgconfig) ? $orgconfig['enable_tls'] : FALSE), $debug);
		if ($ldap->getLastError()) throw new Exception('LDAP warning: ' . $ldap->getLastError());
		$results[$orgkey]['connect'] = array(TRUE,NULL);
	} catch (Exception $e) {
		SimpleSAML_Logger::debug('ldapstatus: Connect error() [' .$orgkey . ']: ' . $e->getMessage());
		$results[$orgkey]['connect'] = array(FALSE,$e->getMessage());
		$results[$orgkey]['time'] = microtime(TRUE) - $previous;
		continue;
	}

	// Bind as admin user
	if (isset($orgconfig['adminUser'])) {
		try {
			SimpleSAML_Logger::debug('ldapstatus: Admin bind() [' .$orgkey . ']');
			$success = $ldap->bind($orgconfig['adminUser'], $orgconfig['adminPassword']);
			if ($ldap->getLastError()) throw new Exception('LDAP warning: ' . $ldap->getLastError());
			if ($success) {
				$results[$orgkey]['adminBind'] = array(TRUE,NULL);
			} else {
				$results[$orgkey]['adminBind'] = array(FALSE,'Could not bind()' );
			}
		} catch (Exception $e) {
			$results[$orgkey]['adminBind'] = array(FALSE,$e->getMessage());
			$results[$orgkey]['time'] = microtime(TRUE) - $previous;
			continue;
		}
	}
	
	
	$eppn = 'asdasdasdasd@feide.no';
	// Search for bogus user
	try {
		$dn = $ldap->searchfordn($orgconfig['searchbase'], 'eduPersonPrincipalName', $eppn, TRUE);
		if ($ldap->getLastError()) throw new Exception('LDAP warning: ' . $ldap->getLastError());
		$results[$orgkey]['ldapSearchBogus'] = array(TRUE,NULL);
	} catch (Exception $e) {
		$results[$orgkey]['ldapSearchBogus'] = array(FALSE,$e->getMessage());
		$results[$orgkey]['time'] = microtime(TRUE) - $previous;
		continue;
	}


	// If test user is available
	if (array_key_exists('testUser', $orgconfig)) {

		// Try to search for DN of test account
		try {
			$dn = $ldap->searchfordn($orgconfig['searchbase'], 'eduPersonPrincipalName', $orgconfig['testUser']);
			if ($ldap->getLastError()) throw new Exception('LDAP warning: ' . $ldap->getLastError());
			$results[$orgkey]['ldapSearchTestUser'] = array(TRUE,NULL);
		} catch (Exception $e) {
			$results[$orgkey]['ldapSearchTestUser'] = array(FALSE,$e->getMessage());
			$results[$orgkey]['time'] = microtime(TRUE) - $previous;
			continue;
		}
		
		if ($ldap->bind($dn, $orgconfig['testPassword'])) {
			$results[$orgkey]['ldapBindTestUser'] = array(TRUE,NULL);
			
		} else {
			$results[$orgkey]['ldapBindTestUser'] = array(FALSE,NULL);
			$results[$orgkey]['time'] = microtime(TRUE) - $previous;
			continue;
		}

		try {
			$attributes = $ldap->getAttributes($dn, $orgconfig['attributes'], $ldapconfig->getValue('attributesize.max', NULL));
			if ($ldap->getLastError()) throw new Exception('LDAP warning: ' . $ldap->getLastError());
			$results[$orgkey]['ldapGetAttributesTestUser'] = array(TRUE,NULL);
		} catch(Exception $e) {
			$results[$orgkey]['ldapGetAttributesTestUser'] = array(FALSE,$e->getMessage());
		}
	}
	$results[$orgkey]['time'] = microtime(TRUE) - $previous;
}

$_SESSION['_ldapstatus_results'] = $results;

$session->setData('module:ldapstatus', 'results', $results);

#echo '<pre>'; print_r($results); exit;

$lightCounter = array(0,0,0);

function resultCode($res) {
	global $lightCounter;
	$code = '';
	$columns = array('config', 'ping', 'adminBind', 'ldapSearchBogus', 'configTest', 'ldapSearchTestUser', 'ldapBindTestUser', 'ldapGetAttributesTestUser', 'configMeta');
	foreach ($columns AS $c) {
		if (array_key_exists($c, $res)) {
			if ($res[$c][0]) {
				$code .= '0';
				$lightCounter[0]++;
			} else {
				$code .= '2';
				$lightCounter[2]++;
			}
			
		} else {
			$code .= '1';
			$lightCounter[1]++;
		}
	}
	return $code;
}



	
	
$ressortable = array();
foreach ($results AS $key => $res) {
	$ressortable[$key] = resultCode($res);
}
asort($ressortable);
#echo '<pre>'; print_r($ressortable); exit;


$t = new SimpleSAML_XHTML_Template($config, 'ldapstatus:ldapstatus.php');

$t->data['completeNo'] = count($results);
$t->data['completeOf'] = count($orgs);
$t->data['results'] = $results;
$t->data['orgconfig'] = $orgs;
$t->data['lightCounter'] = $lightCounter;
$t->data['sortedOrgIndex'] = array_keys($ressortable);
$t->show();
exit;

?>
