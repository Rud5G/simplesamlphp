<?php


require_once('../../www/_include.php');

require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Logger.php');
require_once('SimpleSAML/XML/MetaDataStore.php');
require_once('SimpleSAML/XHTML/Template.php');

session_start();

$config = SimpleSAML_Configuration::getInstance();
$metadata = new SimpleSAML_XML_MetaDataStore($config);
$session = SimpleSAML_Session::getInstance();
$logger = new SimpleSAML_Logger();

$ldapconfigfile = $config->getValue('basedir') . 'config/ldapmulti.php';
require_once($ldapconfigfile);


$logger->log(LOG_INFO, $session->getTrackID(), 'AUTH', 'ldap-multi', 'EVENT', 'Access', 'Accessing auth endpoint login-ldapmulti');


$error = null;
$attributes = array();
	
if (isset($_POST['username'])) {

	$ldapconfig = $ldapmulti[$_POST['org']];
	
	

	$dn = str_replace('%username%', $_POST['username'], $ldapconfig['dnpattern'] );
	$pwd = $_POST['password'];

	$ds = ldap_connect($ldapconfig['hostname']);
	
	if ($ds) {
	
		if (!ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3)) {
		
			$logger->log(LOG_CRIT, $session->getTrackID(), 'AUTH', 'ldap-multi', 'LDAP_OPT_PROTOCOL_VERSION', '3', 'Error setting LDAP prot version to 3');
			
			echo "Failed to set LDAP Protocol version to 3";
			exit;
		}
		/*
		if (!ldap_start_tls($ds)) {
		echo "Failed to start TLS";
		exit;
		}
		*/
		if (!ldap_bind($ds, $dn, $pwd)) {
			$error = "Bind failed, wrong username or password. Tried with DN=[" . $dn . "] DNPattern=[" .  $ldapconfig['dnpattern'] . "]";

			$logger->log(LOG_NOTICE, $session->getTrackID(), 'AUTH', 'ldap-multi', 'Fail', $_POST['username'], $_POST['username'] . ' failed to authenticate');
			
		} else {
			$sr = ldap_read($ds, $dn, $ldapconfig['attributes'] );
			$ldapentries = ldap_get_entries($ds, $sr);
			

			for ($i = 0; $i < $ldapentries[0]['count']; $i++) {
				$values = array();
				if ($ldapentries[0][$i] == 'jpegphoto') continue;
				for ($j = 0; $j < $ldapentries[0][$ldapentries[0][$i]]['count']; $j++) {
					$values[] = $ldapentries[0][$ldapentries[0][$i]][$j];
				}
				
				$attributes[$ldapentries[0][$i]] = $values;
			}

			// generelt ldap_next_entry for flere, men bare ett her
			//print_r($ldapentries);
			//print_r($attributes);
			
			$logger->log(LOG_NOTICE, $session->getTrackID(), 'AUTH', 'ldap-multi', 'OK', $_POST['username'], $_POST['username'] . ' successfully authenticated');
			
			
			$session->setAuthenticated(true);
			$session->setAttributes($attributes);
			
			$session->setNameID(SimpleSAML_Utilities::generateID());
			$session->setNameIDFormat('urn:oasis:names:tc:SAML:2.0:nameid-format:transient');
			
			$returnto = $_REQUEST['RelayState'];
			header("Location: " . $returnto);
			exit(0);

		}
	// ldap_close() om du vil, men frigjoeres naar skriptet slutter
	}

	
}


$t = new SimpleSAML_XHTML_Template($config, 'login-ldapmulti.php');

$t->data['header'] = 'simpleSAMLphp: Enter username and password';	
$t->data['relaystate'] = $_REQUEST['RelayState'];
$t->data['ldapconfig'] = $ldapmulti;
$t->data['org'] = $_REQUEST['org'];
$t->data['error'] = $error;
if (isset($error)) {
	$t->data['username'] = $_POST['username'];
}

$t->show();


?>
