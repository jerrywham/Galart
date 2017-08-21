<?php /**
* Plugin Galart - config page
*
* @package	PLX
* @version	1.0
* @date	16/09/16
* @author Bronco
**/
if(!defined("PLX_ROOT")) exit; ?>
<?php 
	if(!empty($_POST)) {
		$plxPlugin->setParam("size", plxUtils::strCheck($_POST["size"]), "numeric");

		$plxPlugin->saveParams();
		header("Location: parametres_plugin.php?p=Galart");
		exit;
	}
?>
<h2><?php $plxPlugin->lang("L_TITLE") ?></h2>
<p><?php $plxPlugin->lang("L_DESCRIPTION") ?></p>
<form action="parametres_plugin.php?p=Galart" method="post" >

		<label>taille des miniatures : 
			<input type="text" style="width:100%;" name="size" value="<?php echo $plxPlugin->getParam("size");?>"/>
		</label>


	<br />
	<input type="submit" name="submit" value="Enregistrer"/>
</form>
