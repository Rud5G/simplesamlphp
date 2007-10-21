<?php $this->includeAtTemplateBase('includes/header.php'); ?>

	<div id="header">
		<h1>User consent on attribute release</h1>
		<div id="poweredby"><img src="/<?php echo $data['baseurlpath']; ?>resources/icons/bino.png" alt="Bino" /></div>
	</div>
	
	<div id="content">

		<p>You are about to login to the service <strong><?php echo $data['spentityid']; ?></strong>. In the login proccess, the identity provider will send attributes containing information about your identity to this service. Do you accept this?</p>
				
		<p><a href="<?php echo $data['consenturl']; ?>"><strong>Yes</strong>, I accept that attributes are sent to this service</a></p>
		
		<p style="font-size: x-small">[ <a href="">Show attributes that are sent</a> ]</p>
		<table style="font-size: x-small">
<?php


			$attributes = $data['attributes'];
			foreach ($attributes AS $name => $value) {
				if (sizeof($value) > 1) {
					echo '<tr><td>' . $name . '</td><td><ul>';
					foreach ($value AS $v) {
						echo '<li>' . $v . '</li>';
					}
					echo '</ul></td></tr>';
				} else {
					echo '<tr><td>' . $name . '</td><td>' . $value[0] . '</td></tr>';
				}
			}

?>
		</table>


<?php $this->includeAtTemplateBase('includes/footer.php'); ?>