<?php

require_once('_include.php');

/* Load simpleSAMLphp, configuration */
$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

/* Check if valid local session exists.. */
if ($config->getBoolean('admin.protectindexpage', false)) {
	SimpleSAML_Utilities::requireAdmin();
}
$loginurl = SimpleSAML_Utilities::getAdminLoginURL();
$isadmin = SimpleSAML_Utilities::isAdmin();


$warnings = array();

if (SimpleSAML_Utilities::getSelfProtocol() != 'https') {
	$warnings[] = 'warnings_https';
}

	
$links = array();


if ($config->getBoolean('enable.saml20-sp', TRUE) === true)
	$links[] = array(
		'href' => 'example-simple/saml2-example.php', 
		'text' => 'link_saml2example');

if ($config->getBoolean('enable.shib13-sp', FALSE) === true)
	$links[] = array(
		'href' => 'example-simple/shib13-example.php', 
		'text' => 'link_shib13example'
	);

if($config->getBoolean('idpdisco.enableremember', FALSE)) {
	$links[] = array(
		'href' => 'cleardiscochoices.php',
		'text' => 'link_cleardiscochoices',
	);
}

$publishURL = $config->getString('metashare.publishurl', NULL);
if ($publishURL !== NULL) {
	$metadataSources = array(
		'saml20-idp' => 'saml2/idp/metadata.php',
		'saml20-sp' => 'saml2/sp/metadata.php',
		'shib13-idp' => 'shib13/idp/metadata.php',
		'shib13-sp' => 'shib13/sp/metadata.php',
	);
	foreach($metadataSources as $name => $url) {
		if(!$config->getBoolean('enable.' . $name, FALSE)) continue;

		$url = SimpleSAML_Utilities::resolveURL($url);
		$linkTarget = SimpleSAML_Utilities::addURLparameter($publishURL, array('url' => $url));
		$links[] = array(
			'href' => $linkTarget,
			'text' => 'link_publish_' . $name,
		);
	}
}



$linksconf = array();

$linksconf[] = array(
	'href' => 'example-simple/hostnames.php?dummy=1', 
	'text' => 'link_diagnostics'
);

$linksconf[] = array(
	'href' => 'admin/phpinfo.php', 
	'text' => 'link_phpinfo'
);

$linksconf[] = array(
	'href' => 'admin/config.php',
	'text' => 'link_configcheck',
);






$linksmeta = array();

$linksmeta[] = array(
	'href' => 'admin/metadata.php', 
	'text' => 'link_meta_overview');

// if ($config->getValue('enable.saml20-sp') === true)
// 	$linksmeta[] = array(
// 		'href' => 'saml2/sp/metadata.php?output=xhtml', 
// 		'text' => 'link_meta_saml2sphosted');
// 
// if ($config->getValue('enable.saml20-idp') === true)
// 	$linksmeta[] = array(
// 		'href' => 'saml2/idp/metadata.php?output=xhtml', 
// 		'text' => 'link_meta_saml2idphosted');
// 
// if ($config->getValue('enable.shib13-sp') === true)
// 	$linksmeta[] = array(
// 		'href' => 'shib13/sp/metadata.php?output=xhtml', 
// 		'text' => 'link_meta_shib13sphosted');
// 
// if ($config->getValue('enable.shib13-idp') === true)
// 	$linksmeta[] = array(
// 		'href' => 'shib13/idp/metadata.php?output=xhtml', 
// 		'text' => 'link_meta_shib13idphosted');


$linksmeta[] = array(
	'href' => 'admin/metadata-converter.php',
	'text' => 'link_xmlconvert',
	);


$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

$metaentries = array('hosted' => array(), 'remote' => array() );
if ($config->getBoolean('enable.saml20-sp', TRUE) === true) {
	try {
		$metaentries['hosted']['saml20-sp'] = $metadata->getMetaDataCurrent('saml20-sp-hosted');
		$metaentries['hosted']['saml20-sp']['metadata-url'] = '/' . $config->getBaseURL() . 'saml2/sp/metadata.php?output=xhtml';
		if ($isadmin)
			$metaentries['remote']['saml20-idp-remote'] = $metadata->getList('saml20-idp-remote');
	} catch(Exception $e) {}
}
if ($config->getBoolean('enable.saml20-idp', FALSE) === true) {
	try {
		$metaentries['hosted']['saml20-idp'] = $metadata->getMetaDataCurrent('saml20-idp-hosted');
		$metaentries['hosted']['saml20-idp']['metadata-url'] = '/' . $config->getBaseURL() . 'saml2/idp/metadata.php?output=xhtml';
		if ($isadmin)
			$metaentries['remote']['saml20-sp-remote'] = $metadata->getList('saml20-sp-remote');
	} catch(Exception $e) {}
}
if ($config->getBoolean('enable.shib13-sp', FALSE) === true) {
	try {
		$metaentries['hosted']['shib13-sp'] = $metadata->getMetaDataCurrent('shib13-sp-hosted');
		$metaentries['hosted']['shib13-sp']['metadata-url'] = '/' . $config->getBaseURL() . 'shib13/sp/metadata.php?output=xhtml';
		if ($isadmin)
			$metaentries['remote']['shib13-idp-remote'] = $metadata->getList('shib13-idp-remote');
	} catch(Exception $e) {}
}
if ($config->getBoolean('enable.shib13-idp', FALSE) === true) {
	try {
		$metaentries['hosted']['shib13-idp'] = $metadata->getMetaDataCurrent('shib13-idp-hosted');
		$metaentries['hosted']['shib13-idp']['metadata-url'] = '/' . $config->getBaseURL() . 'shib13/idp/metadata.php?output=xhtml';
		if ($isadmin)
			$metaentries['remote']['shib13-sp-remote'] = $metadata->getList('shib13-sp-remote');
	} catch(Exception $e) {}
}

#echo '<pre>'; print_r($metaentries); exit;




$linksdoc = array();

$linksdoc[] = array(
	'href' => 'http://rnd.feide.no/content/installing-simplesamlphp', 
	'text' => 'link_doc_install');

if ($config->getBoolean('enable.saml20-sp', TRUE) || $config->getBoolean('enable.shib13-sp', false))
	$linksdoc[] = array(
		'href' => 'http://rnd.feide.no/content/using-simplesamlphp-service-provider', 
		'text' => 'link_doc_sp');

if ($config->getBoolean('enable.saml20-idp', false ) || $config->getBoolean('enable.shib13-idp', false))
	$linksdoc[] = array(
		'href' => 'http://rnd.feide.no/content/using-simplesamlphp-identity-provider', 
		'text' => 'link_doc_idp');

if ($config->getBoolean('enable.shib13-idp', false))
	$linksdoc[] = array(
		'href' => 'http://rnd.feide.no/content/configure-shibboleth-13-sp-work-simplesamlphp-idp', 
		'text' => 'link_doc_shibsp');

if ($config->getBoolean('enable.saml20-idp', false ))
	$linksdoc[] = array(
		'href' => 'http://rnd.feide.no/content/simplesamlphp-idp-google-apps-education', 
		'text' => 'link_doc_googleapps');

$linksdoc[] = array(
	'href' => 'http://rnd.feide.no/content/simplesamlphp-advanced-features', 
	'text' => 'link_doc_advanced',
);



$linksdoc[] = array(
	'href' => 'http://rnd.feide.no/content/simplesamlphp-maintenance-and-configuration', 
	'text' => 'link_doc_maintenance');


$allLinks = array(
	'links' => &$links,
	'metadata' => &$linksmeta,
	'doc' => &$linksdoc,
	);

SimpleSAML_Module::callHooks('frontpage', $allLinks);

$enablematrix = array(
	'saml20-sp' => $config->getBoolean('enable.saml20-sp', TRUE),
	'saml20-idp' => $config->getBoolean('enable.saml20-idp', false),
	'shib13-sp' => $config->getBoolean('enable.shib13-sp', false),
	'shib13-idp' => $config->getBoolean('enable.shib13-idp', false),
);


$functionchecks = array(
	'hash'             => array('required',  'Hashing function'),
	'gzinflate'        => array('required',  'ZLib'),
	'openssl_sign'     => array('required',  'OpenSSL'),
	'simplexml_import_dom' => array('required', 'SimpleXML'),
	'dom_import_simplexml' => array('required', 'XML DOM'),
	'preg_match'       => array('required',  'RegEx support'),
	'ldap_bind'        => array('required_ldap',  'LDAP Extension'),
	'radius_auth_open' => array('required_radius',  'Radius Extension'),
	'mcrypt_module_open'=> array('optional',  'MCrypt'),
	'mysql_connect'    => array('optional',  'MySQL support'),
);
$funcmatrix = array();
$funcmatrix[] = array(
	'required' => 'required', 
	'descr' => 'PHP Version >= 5.1.2. You run: ' . phpversion(), 
	'enabled' => version_compare(phpversion(), '5.1.2', '>='));
$funcmatrix[] = array(
	'required' => 'reccomended', 
	'descr' => 'PHP Version >= 5.2 (Required for Shibboleth 1.3 SP)', 
	'enabled' => version_compare(phpversion(), '5.2', '>='));
foreach ($functionchecks AS $func => $descr) {
	$funcmatrix[] = array('descr' => $descr[1], 'required' => $descr[0], 'enabled' => function_exists($func));
}


/* Some basic configuration checks */

if($config->getString('technicalcontact_email', 'na@example.org') === 'na@example.org') {
	$mail_ok = FALSE;
} else {
	$mail_ok = TRUE;
}
$funcmatrix[] = array(
	'required' => 'reccomended',
	'descr' => 'technicalcontact_email option set',
	'enabled' => $mail_ok
	);
if($config->getString('auth.adminpassword', '123') === '123') {
	$password_ok = FALSE;
} else {
	$password_ok = TRUE;
}
$funcmatrix[] = array(
	'required' => 'required',
	'descr' => 'auth.adminpassword option set',
	'enabled' => $password_ok
);

$funcmatrix[] = array(
	'required' => 'required',
	'descr' => 'Magic Quotes should be turned off',
	'enabled' => (get_magic_quotes_runtime() === 0)
);


$t = new SimpleSAML_XHTML_Template($config, 'frontpage.php', 'frontpage');
$t->data['isadmin'] = $isadmin;
$t->data['loginurl'] = $loginurl;
$t->data['header'] = 'simpleSAMLphp installation page';
$t->data['icon'] = 'compass_l.png';
$t->data['warnings'] = $warnings;
$t->data['links'] = $links;
$t->data['links_meta'] = $linksmeta;
$t->data['links_doc'] = $linksdoc;
$t->data['links_conf'] = $linksconf;
$t->data['metaentries'] = $metaentries;

$t->data['enablematrix'] = $enablematrix;
$t->data['funcmatrix'] = $funcmatrix;
$t->data['version'] = $config->getVersion();
$t->data['directory'] = dirname(dirname(__FILE__));

$t->show();



?>