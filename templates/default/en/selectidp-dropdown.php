<?php $this->includeAtTemplateBase('includes/header.php'); ?>

	<div id="header">
		<h1>SAML 2.0 IdP Discovery Service</h1>
		<div id="poweredby"><img src="/<?php echo $data['baseurlpath']; ?>resources/icons/bino.png" alt="Bino" /></div>
	</div>
	
	<div id="content">

		<h2><?php if (isset($data['header'])) { echo $data['header']; } else { echo "Select your IdP"; } ?></h2>
		
		<p>Please select the identity provider where you want to authenticate:</p>
		
		<form method="get" action="<?php echo $data['urlpattern']; ?>">
		<input type="hidden" name="entityID" value="<?php echo $data['entityID']; ?>" />
		<input type="hidden" name="return" value="<?php echo $data['return']; ?>" />
		<input type="hidden" name="returnIDParam" value="<?php echo $data['returnIDParam']; ?>" />
		<select name="idpentityid">
		<?php
		
		foreach ($data['idplist'] AS $idpentry) {

			echo '<option value="'.$idpentry['entityid'].'"';
			if ($idpentry['entityid'] == $data['preferedidp']) echo ' selected="selected"';
			echo '>'.$idpentry['name'].'</option>';
		
		}
		?>
		</select>
		<input type="submit" value="Select"/>
		</form>

		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
