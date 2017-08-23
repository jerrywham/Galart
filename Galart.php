<?php
/**
* Plugin WDDWebLinks
*
* @package	PLX
* @version	1.0
* @date	02/08/17
* @author Bronco
**/
class Galart extends plxPlugin {

	public function __construct($default_lang) {
		
		# appel du constructeur de la classe plxPlugin (obligatoire)
		parent::__construct($default_lang);		
		
		# limite l'acces a l'ecran de configuration du plugin
		# PROFIL_ADMIN , PROFIL_MANAGER , PROFIL_MODERATOR , PROFIL_EDITOR , PROFIL_WRITER
		$this->setConfigProfil(PROFIL_ADMIN);
		
		
		# Declaration d'un hook (existant ou nouveau)
		$this->addHook('AdminIndexPrepend','AdminIndexPrepend');
		$this->addHook('plxAdminEditArticleXml','plxAdminEditArticleXml');
		$this->addHook('AdminArticleContent','AdminArticleContent');
		$this->addHook('AdminArticlePreview','AdminArticlePreview');
		$this->addHook('AdminArticleParseData','AdminArticleParseData');
		$this->addHook('AdminArticlePostData','AdminArticlePostData');
		$this->addHook('AdminArticleInitData','AdminArticleInitData');
		$this->addHook('plxMotorParseArticle','plxMotorParseArticle');		
		$this->addHook('indexEnd','indexEnd');		
		
	}

	# Activation / desactivation
	public function OnActivate() {}
	public function OnDeactivate() {}
	

	########################################
	# HOOKS
	########################################


	##############################################################################
	# plxAdminEditArticleXml
	##############################################################################
	# Ajoute le stockage dossier dans le fichier de l'article
	public function plxAdminEditArticleXml(){
		echo '<?php 
					$folder = plxUtils::getValue($content["folder"]);
					$xml .= "\t<folder><![CDATA[".plxUtils::cdataCheck(trim($folder))."]]></folder>\n";					
				?>';
	}
	


	


	##############################################################################
	# plxMotorParseArticle
	##############################################################################
	# Gestion du dossier dans le parsing d'un article
	public function plxMotorParseArticle(){
		echo '<?php 
		if (!empty($iTags["folder"])){
			$art["folder"] = $values[$iTags["folder"][0]]["value"];
			if (!defined("PLX_ADMIN")){				
				$art["content"].="
					<link rel=\"stylesheet\" href=\"".$this->urlRewrite(PLX_PLUGINS."Galart/style.css")."\"/>
					<link rel=\"stylesheet\" href=\"".$this->urlRewrite(PLX_PLUGINS."Galart/assets/lightbox.css")."\"/>
					<script src=\"".$this->urlRewrite(PLX_PLUGINS."Galart/assets/lightbox.js")."\"></script>
					".$this->plxPlugins->aPlugins["Galart"]->galerie(PLX_ROOT.$art["folder"],"",'.$this->getParam("size").','.$this->getParam("size").',true)
					."<script>[].forEach.call(document.querySelectorAll(\"[lightbox]\"), function(el) { el.lightbox = new Lightbox(el);});</script>"
					;
			}
		}else{
			$art["folder"] = "";
		}

		?>';
	}



	##############################################################################
	# AdminArticleContent
	##############################################################################
	# Ajout d'un input sur la page admin pour gérer le dossier associé à un article
	public function AdminArticleContent(){
		$label=$this->getLang("L_ARTICLE_FOLDER");		
		echo '<?php 
		function recursiveDirContent($root=null){
			if (empty($root)){return false;}
			if (!is_dir($root)){return array($root);}
			$iter = new RecursiveIteratorIterator(
			    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
			    RecursiveIteratorIterator::SELF_FIRST,
			    RecursiveIteratorIterator::CATCH_GET_CHILD # Ignore "Permission denied"
			);

			$paths = array($root);
			foreach ($iter as $path => $dir) {		
				$paths[] = $path; 
			}

			return $paths;
		}
			$folders=recursiveDirContent(PLX_ROOT."'.$this->getParam('rep').'");
			?>';

		echo '
					<div class="sml-12"><label for="id_url_link">'.$label.'</label><select name="folder" id="folder_image">';
					echo "<option value=''></option>\n";
		echo '<?php
		foreach ($folders as $item) {
			if (is_dir($item)){	
				if (PLX_ROOT.$folder==$item){$selected="selected=\"true\"";}else{$selected="";}
				echo "<option value=\'".str_replace(PLX_ROOT,\'\',$item)."\' $selected>".str_replace(PLX_ROOT.$plxAdmin->aConf[\'medias\'],\'\',$item)."</option>\n";
			}
		} ?>
		</select></div>';
	}	



	##############################################################################
	# AdminArticlePreview
	##############################################################################
	# Gestion du lien dans la prévisualisation d'un article	
	public function AdminArticlePreview(){
		echo '<?php	$art["folder"] = plxUtils::strCheck($_POST["folder"]);?>';
	}

	##############################################################################
	# AdminArticlePostData
	##############################################################################
	# Gestion du lien posté dans l'article et upload éventuel de l'image
	public function AdminArticlePostData(){
		echo '<?php	$folder = plxUtils::strCheck($_POST["folder"]);?>';
		
	}

	##############################################################################
	# AdminArticleParseData
	##############################################################################
	# Ajout de la variable $url_link (édition d'un article)
	public function AdminArticleParseData(){
		echo '<?php 
			if (!isset($result["folder"])){
				$folder="";
			}else{
				$folder=$result["folder"];
			}
		?>';
	}

	##############################################################################
	# AdminArticleInitData (création d'un article)
	##############################################################################
	# Ajout de la variable $url_link lors de l'initialisation
	public function AdminArticleInitData(){
		echo '<?php	$folder = "";?>';
	}

	########################################
	# Private methods
	########################################
	/**
	 *   Génère automatiquement la miniatures de l'image passée en argument
	 *	 aux dimensions spécifiées ou par défaut (100px)
	 *	 Les miniatures ne sont créées que si elles n'existent pas encore;
	 * 	 si elles existent, seul le chemin est renvoyé.
	 *
	 * @author bronco@warriordudimanche.com
	 * @copyright open source and free to adapt (keep me aware !)
	 * @version 2.0
	 * @param string $img chemin vers le fichier image
	 * @param integer $width largeur maximum du thumbnail généré
	 * @param integer $height hauteur maximum du thumbnail généré
	 * @param string $add_to_thumb_filename suffixe à ajouter au fichier thumbnail
	 * @param boolean $crop_image true=redimensionne et recadre l'image aux dimensions width/$height, false, redimensionne avec proportions
	*/
	private function auto_thumb($img,$width=null,$height=null,$add_to_thumb_filename='_THUMB_',$crop_image=false){
		// initialisation
		$DEFAULT_WIDTH='100';
		$DEFAULT_HEIGHT='100';
		$DONT_RESIZE_THUMBS=true;

		if (!$width){$width=$DEFAULT_WIDTH;}
		if (!$height){$height=$DEFAULT_HEIGHT;}
		$recadrageX=0;$recadrageY=0;
		$motif='#\.(jpe?g|png|gif)#i'; // Merci à JéromeJ pour la correction  ! 
		$rempl=$add_to_thumb_filename.'_'.$width.'x'.$height.'.$1';
		$thumb_name=preg_replace($motif,$rempl,$img);
		// sortie prématurée:
		if (!file_exists($img)){return 'auto_thumb ERROR: '.$img.' doesn\'t exists';}
		if (file_exists($thumb_name)){return $thumb_name;} // miniature déjà créée
		if ($add_to_thumb_filename!='' && preg_match($add_to_thumb_filename,$img) && $DONT_RESIZE_THUMBS){return false;} // on cherche à traiter un fichier miniature (rangez un peu !)

		// redimensionnement en fonction du ratio
		$taille = getimagesize($img);
		$src_width=$taille[0];
		$src_height=$taille[1];
		if (!$crop_image){ 
			// sans recadrage: on conserve les proportions
			if ($src_width<$src_height){
				// portrait
				$ratio=$src_height/$src_width;
				$width=$height/$ratio;
			}else if ($src_width>$src_height){
				// paysage
				$ratio=$src_width/$src_height;
				$height=$width/$ratio;
			}
		}else{
			// avec recadrage: on produit une image aux dimensions définies mais coupée
			if ($src_width<$src_height){
				// portrait
				$recadrageY=round(($src_height-$src_width)/2);
				$src_height=$src_width;
			}else if ($src_width>$src_height){
				// paysage
				$recadrageX=round(($src_width-$src_height)/2);
				$src_width=$src_height;
			}
		}



		// en fonction de l'extension
		$fichier = pathinfo($img);
		$extension=str_ireplace('jpg','jpeg',$fichier['extension']);
		
		
		$fonction='imagecreatefrom'.$extension;
		$src  = $fonction($img);  // que c'est pratique ça ^^ !
		
		// création image
		$thumb = imagecreatetruecolor($width,$height);
		
		// gestion de la transparence 
		// (voir fonction de Seebz: http://code.seebz.net/p/imagethumb/)
		if( $extension=='png' ){imagealphablending($thumb,false);imagesavealpha($thumb,true);}
		if( $extension=='gif'  && @imagecolortransparent($img)>=0 ){
			$transparent_index = @imagecolortransparent($img);
			$transparent_color = @imagecolorsforindex($img, $transparent_index);
			$transparent_index = imagecolorallocate($thumb, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
			imagefill($thumb, 0, 0, $transparent_index);
			imagecolortransparent($thumb, $transparent_index);
		}
		
		imagecopyresampled($thumb,$src,0,0,$recadrageX,$recadrageY,$width,$height,$src_width,$src_height);
		imagepng($thumb, $thumb_name);
		imagedestroy($thumb);
		
		return $thumb_name;
	}


	/**
	 *   Génère automatiquement une galerie d'images à partir d'un dossier
	 *
	 * @author bronco@warriordudimanche.com
	 * @copyright open source and free to adapt (keep me aware !)
	 * @version 2.0
	 * @param string $path chemin vers le dossier d'images
	 * @param string $title titre de la galerie
	 * @param integer $width largeur maximum des thumbnails générés
	 * @param integer $height hauteur maximum des thumbnails générés
	 * @param boolean $crop true=redimensionne et recadre les images aux dimensions width/$height, false, redimensionne avec proportions
	 * @param boolean $style applique ou pas les css par défaut
	*/
	public function galerie($path=null,$title=null,$width=100,$height=100,$crop=false,$infos=false){
		$plxMotor = plxMotor::getInstance();
		if (!$path || $path == PLX_ROOT){return false;}
		$liste=array_merge(glob($path.'/*.png'),glob($path.'/*.gif'),glob($path.'/*.jpg'),glob($path.'/*.jpeg'));
		$thumb_name='_THUMB_';$crop_name='_CROPPED_';$prop_name='_PROPORTION_';$content='';
		if (!empty($liste)){	

			$content.= '<div class="gallery">';
			if ($title){$content.= '	<h1 class="title">'.$title.'</h1>';}
					
						foreach($liste as $image){	
							$i=basename($image);
							if (stripos($i,$thumb_name)===false&&stripos($i,$crop_name)===false&&stripos($i,$prop_name)===false){				
								$content.=  "
									<a href='".$plxMotor->urlRewrite($image)."' class='photo' lightbox='image'><img src='".$plxMotor->urlRewrite($this->auto_thumb($image,$width,$height,'_THUMB_',$crop))."' /></a>";
							}
						}
			
			$content.=  '<div style="clear:both"></div></div>';

		}else{$content.=  '<p class="error">Pas d\'image dans <em>'.$path.'</em></p>';}
		return $content;
	}
}





/* Pense-bete:
 * Récuperer des parametres du fichier parameters.xml
 *	$this->getParam("<nom du parametre>")
 *	$this-> setParam ("param1", 12345, "numeric")
 *	$this->saveParams()
 *
 *	plxUtils::strCheck($string) : sanitize string
 *
 * 
 * Quelques constantes utiles: 
 * PLX_CORE
 * PLX_ROOT
 * PLX_CHARSET
 * PLX_CONFIG_PATH
 * PLX_PLUGINS
 * PLX_CONFIG_PATH
 * PLX_ADMIN (true si on est dans admin)
 * PLX_CHARSET
 * PLX_VERSION
 * PLX_FEED
 *
 * Appel de HOOK dans un thème
 *	eval($plxShow->callHook("showLinkedURL","param1"))  ou eval($plxShow->callHook("ThemeEndHead",array("param1","param2")))
 *	ou $retour=$plxShow->callHook("ThemeEndHead","param1"));
 */
