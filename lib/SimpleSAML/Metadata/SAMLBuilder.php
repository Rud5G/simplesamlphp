<?php

/**
 * Class for generating SAML 2.0 metadata from simpleSAMLphp metadata arrays.
 *
 * This class builds SAML 2.0 metadata for an entity by examining the metadata for the entity.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Metadata_SAMLBuilder {

	/**
	 * The DOMDocument we are working in.
	 */
	private $document;


	/**
	 * The EntityDescriptor we are building.
	 */
	private $entityDescriptor;


	/**
	 * Initialize the builder.
	 *
	 * @param string $entityId  The entity id of the entity.
	 */
	public function __construct($entityId) {
		assert('is_string($entityId)');

		$this->document = new DOMDocument();
		$this->entityDescriptor = $this->createElement('EntityDescriptor');
		$this->entityDescriptor->setAttribute('entityID', $entityId);
		$this->document->appendChild($this->entityDescriptor);
	}


	/**
	 * Retrieve the EntityDescriptor.
	 *
	 * Retrieve the EntityDescriptor element which is generated for this entity.
	 * @return DOMElement  The EntityDescriptor element for this entity.
	 */
	public function getEntityDescriptor() {
		return $this->entityDescriptor;
	}


	/**
	 * Retrieve the EntityDescriptor as text.
	 *
	 * This function serializes this EntityDescriptor, and returns it as text.
	 *
	 * @param bool $formatted  Whether the returned EntityDescriptor should be
	 *                         formatted first.
	 * @return string  The serialized EntityDescriptor.
	 */
	public function getEntityDescriptorText($formatted = TRUE) {
		assert('is_bool($formatted)');

		if ($formatted) {
			SimpleSAML_Utilities::formatDOMElement($this->entityDescriptor);
		}

		return $this->document->saveXML();
	}
	
	
	
	private function addOrganizationInfo($metadata) {
		if (array_key_exists('name', $metadata)) {
			$org = $this->createElement('Organization'); 
			
			if (is_array($metadata['name'])) {
				foreach($metadata['name'] AS $lang => $localname) {
					$orgname = $this->createTextElement('OrganizationName', $localname); 
					$orgname->setAttribute('xml:lang', $lang);					
					$org->appendChild($orgname);
				}
			} else {
				$orgname = $this->createTextElement('OrganizationName', $metadata['name']); 
				$orgname->setAttribute('xml:lang', 'en');
				$org->appendChild($orgname);
			}


			if (is_array($metadata['name'])) {
				foreach($metadata['name'] AS $lang => $localname) {
					$orgname = $this->createTextElement('OrganizationDisplayName', $localname); 
					$orgname->setAttribute('xml:lang', $lang);					
					$org->appendChild($orgname);
				}
			} else {
				$orgname = $this->createTextElement('OrganizationDisplayName', $metadata['name']); 
				$orgname->setAttribute('xml:lang', 'en');
				$org->appendChild($orgname);
			}

			$url = '';
			if (array_key_exists('url', $metadata)) {
				$url = $metadata['url'];
			}
			$uel = $this->createTextElement('OrganizationURL', $url); 
			$uel->setAttribute('xml:lang', 'en');
			$org->appendChild($uel);
			
			$this->entityDescriptor->appendChild($org);
			
			

		}
	}
	


	/**
	 * Add metadata set for entity.
	 *
	 * This function is used to add a metadata array to the entity.
	 *
	 * @param string $set  The metadata set this metadata comes from.
	 * @param array $metadata  The metadata.
	 */
	public function addMetadata($set, $metadata) {
		assert('is_string($set)');
		assert('is_array($metadata)');

		switch ($set) {
		case 'saml20-sp-remote':
			$this->addMetadataSP20($metadata);
			break;
		case 'saml20-idp-remote':
			$this->addMetadataIdP20($metadata);
			break;
		case 'shib13-sp-remote':
			$this->addMetadataSP11($metadata);
			break;
		case 'shib13-idp-remote':
			$this->addMetadataIdP11($metadata);
			break;
		default:
			SimpleSAML_Logger::warning('Unable to generate metadata for unknown type \'' . $set . '\'.');
		}
	}


	/**
	 * Add SAML 2.0 SP metadata.
	 *
	 * @param array $metadata  The metadata.
	 */
	public function addMetadataSP20($metadata) {
		assert('is_array($metadata)');

		$e = $this->createElement('SPSSODescriptor');
		$e->setAttribute('protocolSupportEnumeration', 'urn:oasis:names:tc:SAML:2.0:protocol');

		$this->addCertificate($e, $metadata);

		if (array_key_exists('SingleLogoutService', $metadata)) {
			$t = $this->createElement('SingleLogoutService');
			$t->setAttribute('Binding', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect');
			$t->setAttribute('Location', $metadata['SingleLogoutService']);

			if (array_key_exists('SingleLogoutServiceResponse', $metadata)) {
				$t->setAttribute('ResponseLocation', $metadata['SingleLogoutServiceResponse']);
			}

			$e->appendChild($t);
		}

		if (array_key_exists('NameIDFormat', $metadata)) {
			$t = $this->createElement('NameIDFormat');
			$t->appendChild($this->document->createTextNode($metadata['NameIDFormat']));
			$e->appendChild($t);
		}

		if (array_key_exists('AssertionConsumerService', $metadata)) {
			$t = $this->createElement('AssertionConsumerService');
			$t->setAttribute('index', '0');
			$t->setAttribute('Binding', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST');
			$t->setAttribute('Location', $metadata['AssertionConsumerService']);
			$e->appendChild($t);
		}


		/**
		 * Add an AttributeConsumingService element with information as name and description and list
		 * of requested attributes
		 */
		$attributeconsumer = $this->createElement('AttributeConsumingService');
		$attributeconsumer->setAttribute('index', '0');
		
		if (array_key_exists('name', $metadata)) {	
			if (is_array($metadata['name'])) {
				foreach($metadata['name'] AS $lang => $localname) {
					$t = $this->createTextElement('ServiceName', $localname); 
					$t->setAttribute('xml:lang', $lang);					
					$attributeconsumer->appendChild($t);
				}
			} else {
				$t = $this->createTextElement('ServiceName', $metadata['name']); 
				$t->setAttribute('xml:lang', 'en');
				$attributeconsumer->appendChild($t);
			}
		}
		
		
		
		if (array_key_exists('description', $metadata)) {	
			if (is_array($metadata['description'])) {
				foreach($metadata['description'] AS $lang => $localname) {
					$t = $this->createTextElement('ServiceDescription', $localname); 
					$t->setAttribute('xml:lang', $lang);					
					$attributeconsumer->appendChild($t);
				}
			} else {
				$t = $this->createTextElement('ServiceDescription', $metadata['description']); 
				$t->setAttribute('xml:lang', 'en');
				$attributeconsumer->appendChild($t);
			}
		}
		
		if (array_key_exists('attributes', $metadata) && is_array($metadata['attributes'])) {
			foreach ($metadata['attributes'] AS $attribute) {
				$t = $this->createElement('RequestedAttribute'); 
				$t->setAttribute('Name', $attribute);
				$attributeconsumer->appendChild($t);
			}
		}
		$e->appendChild($attributeconsumer);		

		$this->entityDescriptor->appendChild($e);
		
		$this->addOrganizationInfo($metadata);
		
		if (array_key_exists('contacts', $metadata) && is_array($metadata['contacts']) ) {
			foreach($metadata['contacts'] AS $contact) {
				if (array_key_exists('contactType', $contact) && array_key_exists('EmailAddress', $contact)) {
					$t = $this->createElement('ContactPerson');
					$t->setAttribute('contactType', $contact['contactType']);
					
					if (array_key_exists('SurName', $contact)) {
						$surname = $this->createTextElement('SurName', $contact['SurName']);
						$t->appendChild($surname);
					}
					
					$email = $this->createTextElement('EmailAddress', $contact['EmailAddress']);
					$t->appendChild($email);
					
					$this->entityDescriptor->appendChild($t);
				}
			}
		}
		
		
		
	}


	/**
	 * Add SAML 2.0 IdP metadata.
	 *
	 * @param array $metadata  The metadata.
	 */
	public function addMetadataIdP20($metadata) {
		assert('is_array($metadata)');

		$e = $this->createElement('IDPSSODescriptor');
		$e->setAttribute('protocolSupportEnumeration', 'urn:oasis:names:tc:SAML:2.0:protocol');

		if (array_key_exists('redirect.sign', $metadata) && $metadata['redirect.sign']) {
			$e->setAttribute('WantAuthnRequestSigned', 'true');
		}

		$this->addCertificate($e, $metadata);

		if (array_key_exists('SingleLogoutService', $metadata)) {
			$t = $this->createElement('SingleLogoutService');
			$t->setAttribute('Binding', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect');
			$t->setAttribute('Location', $metadata['SingleLogoutService']);

			if (array_key_exists('SingleLogoutServiceResponse', $metadata)) {
				$t->setAttribute('ResponseLocation', $metadata['SingleLogoutServiceResponse']);
			}

			$e->appendChild($t);
		}

		if (array_key_exists('NameIDFormat', $metadata)) {
			$t = $this->createElement('NameIDFormat');
			$t->appendChild($this->document->createTextNode($metadata['NameIDFormat']));
			$e->appendChild($t);
		}

		if (array_key_exists('SingleSignOnService', $metadata)) {
			$t = $this->createElement('SingleSignOnService');
			$t->setAttribute('Binding', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect');
			$t->setAttribute('Location', $metadata['SingleSignOnService']);
			$e->appendChild($t);
		}
		
		$this->entityDescriptor->appendChild($e);
		
		$this->addOrganizationInfo($metadata);
		
		if (array_key_exists('contacts', $metadata) && is_array($metadata['contacts']) ) {
			foreach($metadata['contacts'] AS $contact) {
				if (array_key_exists('contactType', $contact) && array_key_exists('EmailAddress', $contact)) {
					$t = $this->createElement('ContactPerson');
					$t->setAttribute('contactType', $contact['contactType']);
					
					if (array_key_exists('SurName', $contact)) {
						$surname = $this->createTextElement('SurName', $contact['SurName']);
						$t->appendChild($surname);
					}
					
					$email = $this->createTextElement('EmailAddress', $contact['EmailAddress']);
					$t->appendChild($email);
					
					$this->entityDescriptor->appendChild($t);
				}
			}
		}

	}


	/**
	 * Add SAML 1.1 SP metadata.
	 *
	 * @param array $metadata  The metadata.
	 */
	public function addMetadataSP11($metadata) {
		assert('is_array($metadata)');

		$e = $this->createElement('SPSSODescriptor');
		$e->setAttribute('protocolSupportEnumeration', 'urn:oasis:names:tc:SAML:1.1:protocol');

		$this->addCertificate($e, $metadata);

		if (array_key_exists('NameIDFormat', $metadata)) {
			$t = $this->createElement('NameIDFormat');
			$t->appendChild($this->document->createTextNode($metadata['NameIDFormat']));
			$e->appendChild($t);
		}

		if (array_key_exists('AssertionConsumerService', $metadata)) {
			$t = $this->createElement('AssertionConsumerService');
			$t->setAttribute('index', '0');
			$t->setAttribute('Binding', 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post');
			$t->setAttribute('Location', $metadata['AssertionConsumerService']);
			$e->appendChild($t);
		}

		$this->entityDescriptor->appendChild($e);
	}


	/**
	 * Add SAML 1.1 IdP metadata.
	 *
	 * @param array $metadata  The metadata.
	 */
	public function addMetadataIdP11($metadata) {
		assert('is_array($metadata)');

		$e = $this->createElement('IDPSSODescriptor');
		$e->setAttribute('protocolSupportEnumeration', 'urn:oasis:names:tc:SAML:1.1:protocol');

		$this->addCertificate($e, $metadata);

		if (array_key_exists('NameIDFormat', $metadata)) {
			$t = $this->createElement('NameIDFormat');
			$t->appendChild($this->document->createTextNode($metadata['NameIDFormat']));
			$e->appendChild($t);
		}

		if (array_key_exists('SingleSignOnService', $metadata)) {
			$t = $this->createElement('SingleSignOnService');
			$t->setAttribute('Binding', 'urn:mace:shibboleth:1.0:profiles:AuthnRequest');
			$t->setAttribute('Location', $metadata['SingleSignOnService']);
			$e->appendChild($t);
		}

		$this->entityDescriptor->appendChild($e);
	}


	/**
	 * Add contact information.
	 *
	 * Accepts a contact type, and an array of the following elements (all are optional):
	 * - emailAddress     Email address (as string), or array of email addresses.
	 * - telephoneNumber  Telephone number of contact (as string), or array of telephone numbers.
	 * - name             Full name of contact, either as <GivenName> <SurName>, or as <SurName>, <GivenName>.
	 * - surName          Surname of contact.
	 * - givenName        Givenname of contact.
	 * - company          Company name of contact.
	 *
	 * 'name' will only be used if neither givenName nor surName is present.
	 *
	 * The following contact types are allowed:
	 * "technical", "support", "administrative", "billing", "other"
	 *
	 * @param string $type  The type of contact.
	 * @param array $details  The details about the contact.
	 */
	public function addContact($type, $details) {
		assert('is_string($type)');
		assert('is_array($details)');
		assert('in_array($type, array("technical", "support", "administrative", "billing", "other"), TRUE)');

		/* Parse name into givenName and surName. */
		if (isset($details['name']) && empty($details['surName']) && empty($details['givenName'])) {
			$names = explode(',', $details['name'], 2);
			if (count($names) === 2) {
				$details['surName'] = trim($names[0]);
				$details['givenName'] = trim($names[1]);
			} else {
				$names = explode(' ', $details['name'], 2);
				if (count($names) === 2) {
					$details['givenName'] = trim($names[0]);
					$details['surName'] = trim($names[1]);
				} else {
					$details['surName'] = trim($names[0]);
				}
			}
		}

		$e = $this->createElement('ContactPerson');
		$e->setAttribute('contactType', $type);

		if (isset($details['company'])) {
			$e->appendChild($this->createTextElement('Company', $details['company']));
		}
		if (isset($details['givenName'])) {
			$e->appendChild($this->createTextElement('GivenName', $details['givenName']));
		}
		if (isset($details['surName'])) {
			$e->appendChild($this->createTextElement('SurName', $details['surName']));
		}

		if (isset($details['emailAddress'])) {
			$eas = $details['emailAddress'];
			if (!is_array($eas)) {
				$eas = array($eas);
			}
			foreach ($eas as $ea) {
				$e->appendChild($this->createTextElement('EmailAddress', $ea));
			}
		}

		if (isset($details['telephoneNumber'])) {
			$tlfNrs = $details['telephoneNumber'];
			if (!is_array($tlfNrs)) {
				$tlfNrs = array($tlfNrs);
			}
			foreach ($tlfNrs as $tlfNr) {
				$e->appendChild($this->createTextElement('TelephoneNumber', $tlfNr));
			}
		}

		$this->entityDescriptor->appendChild($e);
	}


	/**
	 * Create DOMElement in metadata namespace.
	 *
	 * Helper function for creating DOMElements with the metadata namespace.
	 *
	 * @param string $name  The name of the DOMElement.
	 * @return DOMElement  The new DOMElement.
	 */
	private function createElement($name) {
		assert('is_string($name)');

		return $this->document->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', $name);
	}


	/**
	 * Create a DOMElement in metadata namespace with a single text node.
	 *
	 * @param string $name  The name of the DOMElement.
	 * @param string $text  The text contained in the element.
	 * @return DOMElement  The new DOMElement with a text node.
	 */
	private function createTextElement($name, $text) {
		assert('is_string($name)');
		assert('is_string($text)');

		$node = $this->createElement($name);
		$node->appendChild($this->document->createTextNode($text));

		return $node;
	}


	/**
	 * Add certificate.
	 *
	 * Helper function for adding a certificate to the metadata.
	 *
	 * @param DOMElement $ssoDesc  The IDPSSODescroptor or SPSSODecriptor the certificate
	 *                             should be added to.
	 * @param array $metadata  The metadata for the entity.
	 */
	private function addCertificate(DOMElement $ssoDesc, $metadata) {
		assert('is_array($metadata)');

		$certInfo = SimpleSAML_Utilities::loadPublicKey($metadata);
		if ($certInfo === NULL || !array_key_exists('certData', $certInfo)) {
			/* No certificate to add. */
			return;
		}

		$certData = $certInfo['certData'];

		$keyDescriptor = $this->createElement('KeyDescriptor');
		$ssoDesc->appendChild($keyDescriptor);

		$keyInfo = $this->document->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:KeyInfo');
		$keyDescriptor->appendChild($keyInfo);

		$x509Data = $this->document->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:X509Data');
		$keyInfo->appendChild($x509Data);

		$x509Certificate = $this->document->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:X509Certificate');
		$x509Data->appendChild($x509Certificate);

		$x509Certificate->appendChild($this->document->createTextNode($certData));
	}

}

?>