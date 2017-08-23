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
	$plxAdmin = plxAdmin::getInstance();
	if(!empty($_POST)) {
		$plxPlugin->setParam("rep", ($_POST["rep"] == '' ? $plxAdmin->aConf["medias"].'images/' : plxUtils::strCheck($_POST["rep"])), "string");
		$plxPlugin->setParam("size", plxUtils::strCheck($_POST["size"]), "numeric");

		$plxPlugin->saveParams();
		header("Location: parametres_plugin.php?p=Galart");
		exit;
	}
?>
<h2><?php $plxPlugin->lang("L_TITLE") ?></h2>
<p><?php $plxPlugin->lang("L_DESCRIPTION") ?></p>
<form action="parametres_plugin.php?p=Galart" method="post" >
		<p>
		<label>Répertoire principal des images : 
			<input type="text" name="rep" value="<?php echo ($plxPlugin->getParam("rep") == '' ? $plxAdmin->aConf["medias"].'images/' : $plxPlugin->getParam("rep"));?>"/>
            <a class="hint"><span><?php echo L_HELP_SLASH_END ?></span></a>&nbsp;<strong>ex: data/medias/images/</strong>
		</label>
		</p>
		<label>taille des miniatures : 
			<input type="text" name="size" value="<?php echo $plxPlugin->getParam("size");?>"/>
		</label>


	<br />
	<input type="submit" name="submit" value="Enregistrer"/>
</form>
