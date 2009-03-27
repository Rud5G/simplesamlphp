<?php

/**
 * Template form for giving consent.
 *
 * Parameters:
 * - 'srcMetadata': Metadata/configuration for the source.
 * - 'dstMetadata': Metadata/configuration for the destination.
 * - 'yesTarget': Target URL for the yes-button. This URL will receive a POST request.
 * - 'yesData': Parameters which should be included in the yes-request.
 * - 'noTarget': Target URL for the no-button. This URL will receive a GET request.
 * - 'noData': Parameters which should be included in the no-request.
 * - 'attributes': The attributes which are about to be released.
 * - 'sppp': URL to the privacy policy of the destination, or FALSE.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
assert('is_array($this->data["srcMetadata"])');
assert('is_array($this->data["dstMetadata"])');
assert('is_string($this->data["yesTarget"])');
assert('is_array($this->data["yesData"])');
assert('is_string($this->data["noTarget"])');
assert('is_array($this->data["noData"])');
assert('is_array($this->data["attributes"])');
assert('$this->data["sppp"] === FALSE || is_string($this->data["sppp"])');


/* Parse parameters. */

if (array_key_exists('name', $this->data['srcMetadata'])) {
	$srcName = $this->data['srcMetadata']['name'];
	if (is_array($srcName)) {
		$srcName = $this->t($srcName);
	}
} else {
	$srcName = $this->data['srcMetadata']['entityid'];
}

if (array_key_exists('name', $this->data['dstMetadata'])) {
	$dstName = $this->data['dstMetadata']['name'];
	if (is_array($dstName)) {
		$dstName = $this->t($dstName);
	}
} else {
	$dstName = $this->data['dstMetadata']['entityid'];
}


$attributes = $this->data['attributes'];


$this->data['header'] = $this->t('{consent:consent:consent_header}');
$this->data['head']  = '<link rel="stylesheet" type="text/css" href="/' . $this->data['baseurlpath'] . 'module.php/consent/style.css" />' . "\n";


$this->includeAtTemplateBase('includes/header.php');
?>


<p>
<?php
	echo $this->t('{consent:consent:consent_accept}', array( 'SPNAME' => $dstName, 'IDPNAME' => $srcName ));
	if (array_key_exists('descr_purpose', $this->data['dstMetadata'])) {
		echo '</p><p>' . $this->t('{consent:consent:consent_purpose}', array(
			'SPNAME' => $dstName,
			'SPDESC' => $this->getTranslation(SimpleSAML_Utilities::arrayize($this->data['dstMetadata']['descr_purpose'], 'en')),
		));
	}
?>
</p>


<form style="display: inline; margin: 0px; padding: 0px" action="<?php echo htmlspecialchars($this->data['yesTarget']); ?>">
<p style="margin: 1em">

<?php
	if ($this->data['usestorage']) {
		$checked = ($this->data['checked'] ? 'checked="checked"' : '');
		echo('<input type="checkbox" name="saveconsent" ' . $checked . ' value="1" /> ' . $this->t('{consent:consent:remember}') . '');
	}
	
	// Embed hidden fields...
	foreach ($this->data['yesData'] as $name => $value) {
		echo('<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />');
	}
?>

</p>
		<input type="submit" name="yes" id="yesbutton" value="<?php echo htmlspecialchars($this->t('{consent:consent:yes}')) ?>" />
</form>

<form style="display: inline; margin-left: .5em;" action="<?php echo htmlspecialchars($this->data['noTarget']); ?>" method="get">

<?php
foreach ($this->data['noData'] as $name => $value) {
        echo('<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />');
}
?>

	<input type="submit" style="display: inline" name="no" id="nobutton" value="<?php echo htmlspecialchars($this->t('{consent:consent:no}')) ?>" />

</form>

<?php
if ($this->data['sppp'] !== FALSE) {
	echo "<p>" . htmlspecialchars($this->t('{consent:consent:consent_privacypolicy}')) . " ";
	echo "<a target='_blank' href='" . htmlspecialchars($this->data['sppp']) . "'>" . htmlspecialchars($dstName) . "</a>";
	echo "</p>";
}
?>

<?php
	function present_list($attr) {
		if (is_array($attr) && count($attr) > 1) {
			$str = '<ul><li>' . join('</li><li>', $attr) . '</li></ul>';
			return $str;
		} else {
			return htmlspecialchars($attr[0]);
		}
	}


	function present_assoc($attr) {
		if (is_array($attr)) {
			
			$str = '<dl>';
			foreach ($attr AS $key => $value) {
				$str .= "\n" . '<dt>' . htmlspecialchars($key) . '</dt><dd>' . present_list($value) . '</dd>';
			}
			$str .= '</dl>';
			return $str;
		} else {
			return htmlspecialchars($attr);
		}
	}
	


	function present_attributes($t, $attributes, $nameParent) {
		$alternate = array('odd', 'even'); $i = 0;
		$summary = 'summary="' . $t->t('{consent:consent:table_summary}') . '"';
		
		if(strlen($nameParent) > 0){
			$parentStr = strtolower($nameParent) . '_';
			$str = '<table class="attributes"' . $summary . ' >';
		}else{
			$parentStr = '';
			$str = '<table id="table_with_attributes"  class="attributes" '. $summary .'>';
			$str .= "\n" . '<caption>' . $t->t('{consent:consent:table_caption}') . '</caption>';
		}

		foreach ($attributes as $name => $value) {
			$nameraw = $name;
			$nameTag = '{attributes:attribute_' . $parentStr . str_replace(":", "_", strtolower($name) ) . '}';
			if ($t->getTag($nameTag) !== NULL) {
				$name = $t->t($nameTag);
			}
			
			if (preg_match('/^child_/', $nameraw)) {
				// Insert child table
				$parentName = preg_replace('/^child_/', '', $nameraw);
				foreach($value AS $child) {
					$str .= "\n" . '<tr class="odd"><td style="padding: 2em">' . present_attributes($t, $child, $parentName) . '</td></tr>';
				}
			} else {
				// Insert values directly
				$str .= "\n" . '<tr class="' . $alternate[($i++ % 2)] . '"><td><span class="attrname">' . htmlspecialchars($name) . '</span><div class="attrvalue">';
				if (sizeof($value) > 1) {
					// We hawe several values
					$str .= '<ul>';
					foreach ($value AS $listitem) {
						if ($nameraw === 'jpegPhoto') {
							$str .= '<li><img src="data:image/jpeg;base64,' . $listitem . '" alt="User photo" /></li>';
						} else {
							$str .= '<li>' . present_assoc($listitem) . '</li>';
						}
					}
					$str .= '</ul>';
				} elseif(isset($value[0])) {
					// We hawe only one value
					if ($nameraw === 'jpegPhoto') {
						$str .= '<img src="data:image/jpeg;base64,' . htmlspecialchars($value[0]) . '" alt="User photo" />';
					} else {
						$str .= htmlspecialchars($value[0]);
					}
				}	// end of if multivalue
				$str .= '</div></td></tr>';
			}	// end else: not child table
		}	// end foreach
		$str .= count($attributes)>1? '</table>':'';
		return $str;
	}
?>
	<h3 id="attributeheader"><?php echo $this->t('{consent:consent:consent_attributes_header}',array( 'SPNAME' => $dstName, 'IDPNAME' => $srcName )); ?></h3>
	<?php echo(present_attributes($this, $attributes, '')); ?>

<?php
$this->includeAtTemplateBase('includes/footer.php');
?>