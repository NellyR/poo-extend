<?php

class Page extends Moteur{
	
	private $selectPagesT;
	private $tab_pages=array();
	private $tab_traitees=array();
	private $contenu="";
	public $niv_tableau=0;
	public $selectPage;
	public $updateContenuPage;
	private $bid=0;
	private $selectMere;
	private $addPage;
	
	public function __construct(){
		parent::__construct();
		$this->selectPagesT = $this->Base->prepare("SELECT id_page, nom_page, link_pagemere FROM tbl_pages WHERE link_rubrique=? ORDER BY link_pagemere ASC, ordre ASC");
		$this->addRub = $this->Base->prepare("INSERT INTO tbl_rubriques (nom_rubrique) VALUES (?)");
		$this->selectPage = $this->Base->prepare("SELECT id_page, nom_page, link_rubrique, contenu, lim_gauche, lim_droite, etat, nom_rubrique, redirection FROM tbl_pages LEFT JOIN tbl_rubriques ON link_rubrique=id_rubrique WHERE id_page=?");
		$this->updateContenuPage= $this->Base->prepare("UPDATE tbl_pages SET contenu=:contenu WHERE id_page=:id");
	}
	
	// Ajout d'un fichier externe...
	public function AjoutScript($cheminscript){
		$this->FichiersCSS .= '<script type="text/javascript" src="'.$cheminscript.'"></script>';
	}
	
	public function ajoutRubrique($nom){
		$this->addRub->execute(array($nom));
	}
	
	// @desc : Afficher la liste des supports (sous forme de dossiers)
	// @param $supp : envoi d'une rubrique supplémentaire (avec l'id 0)
	// @return string
	public function rubriques($supp=""){
		// Liste des supports :
		$this->selectRub= $this->Base->prepare('SELECT id_rubrique, nom_rubrique FROM tbl_rubriques');
		$tab_rubriques= $this->tabIdValue($this->selectRub, "id_rubrique", "nom_rubrique", $supp);
		$html="<div>";
		$i=0;
		$l=1;
		foreach($tab_rubriques as $rubrique){
			$html.="<a href='index.php?page=pages/page.arbo&rub=".$rubrique["id"]."' class='imgdossier' id='lien".$l."'>".$rubrique["value"]."</a> ";
			if($i==3){
				$html.="</div><div style='clear:both;'>&nbsp;</div><div>";
				$i=0;
			}else $i++;
			$l++;
		}
		$html.="<a href='#' class='imgdossierplus' id='afficheajout'>Ajout rubrique</a></div>";
		return $html;
	}

	// @desc : Afficher la liste des supports (sous forme de dossiers)
	// @param $supp : envoi d'une rubrique supplémentaire (avec l'id 0)
	// @return string
	public function rubriques2($supp=""){
		// Liste des supports :
		$this->selectRub= $this->Base->prepare('SELECT id_rubrique, nom_rubrique FROM tbl_rubriques');
		$tab_rubriques= $this->tabIdValue($this->selectRub, "id_rubrique", "nom_rubrique", $supp);
		$html="<div>";
		$i=0;
		$l=1;
		foreach($tab_rubriques as $rubrique){
			$html.="<a href='index.php?page=pages/page.arbo&rub=".$rubrique["id"]."&ar=1' class='imgdossier' id='lien".$l."'>".$rubrique["value"]."</a> ";
			if($i==3){
				$html.="</div><div style='clear:both;'>&nbsp;</div><div>";
				$i=0;
			}else $i++;
			$l++;
		}
		$html.="</div>";
		return $html;
	}
	
	public function infosPage($id){
		return $this->lectureTable($this->selectPage, 0, $id);
	}
	
	// Analyse la chaîne de caractères $contenu :
	// return array();
	public function analyseContenu($contenu){
		// Je découpe mon contenu en différents § :
		$paragraphes=split("/*indic_par_", $contenu);
		$tab=array();
		$i=0;
		foreach($paragraphes as $par){
			// Je récupère le n° du § parcouru :
			$num=substr($par,0,strpos($par,"_"));
			// S'il existe bien, je le stocke dans un tableau :
			if($num!=""){
				$tab[$i]["no"]=$num;
				// Je récupère l'identifiant du type de paragraphe :
				$pos_id=strpos($par,"l")+2; // Position de l'id.
				$num=substr($par,$pos_id,strpos($par,"*")-$pos_id);
				// Je le stocke aussi dans mon tableau
				$tab[$i]["type"]=$num;
				$i++;
			}
		}	
		return $tab;
	}
	
	public function ajoutPage($titre, $pm){
		$this->addPage = $this->Base->prepare("INSERT INTO tbl_pages (nom_page, link_pagemere) VALUES (:titre, :pm)");
		$this->addPage->execute(array(":titre"=>$titre, ":pm"=>$pm));
		return $this->Base->lastInsertId();
	
	}
	
	public function listeSoeurs($pm){
		$req = $this->Base->prepare("SELECT id_page, nom_page FROM tbl_pages WHERE link_pagemere=?");
		$row=$this->lectureTable($req,1,$pm);
		if(empty($row)) return "";
		else return $row;
	}
	
	// Formatage puis affichage de la chaîne de caractères $contenu :
	public function affichageContenu($contenu, $page){
		$tab=$this->analyseContenu($contenu);
		foreach($tab as $div){
			//$monconteneur="<li id='mod_".$div["no"]."' class='groupItem'><div class='mod".$div["no"]."' style='clear:both;'><div class='zonedrag itemHeader'><a href='#' id='suppr_".$div["no"]."' class='suppr'><img src='moteur/skin/pic_supp.png' border='0' /></a><a href='index.php?page=pages/page.modifie.para&par=".$div["no"]."&id=".$page."'><img src='moteur/skin/modif.gif' border='0' /></a></div><div class='itemContent'>";
			$monconteneur="<li id='mod_".$div["no"]."'><div class='mod".$div["no"]."'><div class='zonedrag'><a href='#' id='suppr_".$div["no"]."' class='suppr'><img src='moteur/skin/pic_supp.png' border='0' /></a><a href='index.php?page=pages/page.modifie.para&par=".$div["no"]."&id=".$page."'><img src='moteur/skin/modif.gif' border='0' /></a></div><div class='cont type".$div['type']."'>";
			$contenu=ereg_replace("\/\*indic_par_".$div["no"]."_tpl_".$div["type"]."\*\/",$monconteneur,$contenu);
			$contenu=ereg_replace("\/\*end_par_".$div["no"]."\*\/","</div></div><div style='clear:both'>&nbsp;</div></li>",$contenu);

			// Image entourée d'un lien
			$contenu=str_replace("/*lien:page".$page."-".$div["no"]."_","<a href='",$contenu);
			$contenu=str_replace("finlien_page".$page."-".$div["no"]."*/","'>",$contenu);
			$contenu=str_replace(" fin_imglien*/"," /></a>",$contenu);
			$contenu=str_replace("/*imagelien:page".$page."-".$div["no"],"<img border='0' src='../img/pages/page".$page."-".$div["no"],$contenu);
			
			// Images
			if(ereg("\/\*image:(.*) img_align:centre fin_img\*\/",$contenu)==true){
				$contenu=str_replace("/*image:","<div align='center'><img src='../img/pages/",$contenu);
				$contenu=str_replace(" img_align:centre","' align='center' class='photo_m'",$contenu);
				$contenu=str_replace(" fin_img*/"," /></div> ",$contenu);
			}else{
				$contenu=str_replace("/*image:","<img src='../img/pages/",$contenu);
				$contenu=str_replace(" img_align:droite","' align='right' class='photo_d'",$contenu);
				$contenu=str_replace(" img_align:gauche","' align='left' class='photo_g'",$contenu);
				$contenu=str_replace(" fin_img*/"," /> ",$contenu);
			}
			
			//Flash :
			if(file_exists("../img/pages/page".$page."-".$div["no"].".swf")){
				list($largeur,$hauteur,$type,$attr)=getimagesize("../img/pages/page".$page."-".$div["no"].".swf");
				// Largeur maximum (avec marges) :
				if(($largeur)>440){
					$hauteur=($hauteur/$largeur)*440;
					$largeur=440;
				}
				// Attention, on peut aussi avoir d'autres largeurs !!
				$contenu=str_replace("/*flash:page".$page."-".$div["no"].".swf flash_align:gauche fin_flash*/", "<img src='pages/images/flash.gif' align='left' style='width:".$largeur."px; height:".$hauteur."px; border:1px solid #666' alt='animation flash'>",$contenu);
				$contenu=str_replace("/*flash:page".$page."-".$div["no"].".swf flash_align:droite fin_flash*/", "<img src='pages/images/flash.gif' align='right'  style='width:".$largeur."px; height:".$hauteur."px; border:1px solid #666' alt='animation flash'>",$contenu);
				$contenu=str_replace("/*flash:page".$page."-".$div["no"].".swf flash_align:centre fin_flash*/", "<div align='center' style='text-align:center'><img src='pages/images/flash.gif'  style='width:".$largeur."px; height:".$hauteur."px; border:1px solid #666' alt='animation flash'></div>",$contenu);
			}
			
			//Vidéo :
			if(file_exists("../img/pages/page".$page."-".$div["no"].".flv")){	
				$contenu=str_replace("/*video:page".$page."-".$div["no"].".flv video_align:gauche fin_video*/", "<div align='left' style='float:left'><img src='pages/images/video.gif'></div>",$contenu);
				$contenu=str_replace("/*video:page".$page."-".$div["no"].".flv video_align:droite fin_video*/", "<div align='right' style='float:right'><img src='pages/images/video.gif'></div>",$contenu);
				$contenu=str_replace("/*video:page".$page."-".$div["no"].".flv video_align:centre fin_video*/", "<div align='center' style='text-align:center'><img src='pages/images/video.gif'></div>",$contenu);
			}
			// Son
			if(file_exists("../img/pages/page".$page."-".$div["no"].".mp3")){
				$contenu=str_replace("/*legende_".$div["no"].":", "<div class='legende' id='".$div["no"]."'>", $contenu);
				$contenu=str_replace("fin_legende_".$div["no"]."*/", "</div><!--id".$div["no"]."-->", $contenu);
				ereg("<div class='legende' id='".$div["no"]."'>(.*)</div><!--id".$div["no"]."-->",$contenu, $req);
				$contenu=str_replace("<div class='legende' id='".$div["no"]."'>".$req[1]."</div><!--id".$div["no"]."-->","", $contenu);
				$contenu=str_replace("/*son:page".$page."-".$div["no"].".mp3 son_align:gauche fin_son*/", "<div class='monson'><div class='legende' id='".$div["no"]."'>".$req[1]."</div><img src='pages/images/son_factice.gif' class='lecteur' style='width:183px; height:58px'></div>",$contenu);
				$contenu=str_replace("/*son:page".$page."-".$div["no"].".mp3 son_align:droite fin_son*/", "<div class='monson'><div class='legende' id='".$div["no"]."'>".$req[1]."</div><img src='pages/images/son_factice.gif' class='lecteur' style='width:183px; height:58px'></div>",$contenu);
				$contenu=str_replace("/*son:page".$page."-".$div["no"].".mp3 son_align:centre fin_son*/", "<div class='monson'><div class='legende' id='".$div["no"]."'>".$req[1]."</div><img src='pages/images/son_factice.gif' class='lecteur' style='width:183px; height:58px'></div>",$contenu);
				
			}

			
			
			// Sur deux colonnes : colonne 1
			$contenu=ereg_replace("\[col_1\]","<div class='col_1'>",$contenu);
			// Sur 2 colonnes : colonne 2
			$contenu=ereg_replace("\[col_2\]","<div class='col_2'>",$contenu);
			// Sur 2 colonnes : fin de colonne
			$contenu=ereg_replace("\[\/col_1\]","</div>",$contenu);
			$contenu=ereg_replace("\[\/col_2\]","</div>",$contenu);

			// Textes en gras :
			$contenu=ereg_replace("\[b\]","<b>",$contenu);
			$contenu=ereg_replace("\[\/b\]","</b>",$contenu);
			// Titres :
			$contenu=ereg_replace("\[h([1-4])\]","<h\\1>",$contenu);
			$contenu=ereg_replace("\[\/h([1-4])\]","</h\\1>",$contenu);			
			// Textes en italique :
			$contenu=ereg_replace("\[i\]","<em>",$contenu);
			$contenu=ereg_replace("\[\/i\]","</em>",$contenu);
			// Liens :
			$contenu=ereg_replace("\[lien=#","<a href='",$contenu);
			$contenu=ereg_replace("#]","'>",$contenu);
			$contenu=ereg_replace("\[\/lien\]","</a>",$contenu);
			
			// Gestion des slashes et des sauts de ligne... :
			$contenu=str_replace("\n","<br/>", $contenu);
			$contenu=str_replace("\'","'", $contenu);
			$contenu=str_replace('\"','"', $contenu);
			
		}			
		return $contenu;
	}
	
	// Suppression d'un paragraphe
	public function supprimPara($idpara, $id){
		$row=$this->infosPage($id);
		//expression régulière qui supprime tout ce qui est contenu entre /*indic_par_$idpara*/ et /*end_par_$idpara*/
		$cont=ereg_replace("\/\*indic_par_".$idpara."_tpl_(.*)\/\*end_par_".$idpara."\*\/","",$row["contenu"]);
		// on met la base de données à jour :
		$this->updateContenuPage->execute(array(":contenu"=>$cont, ":id"=>$id));
	}
	
	public function selectmere($mere){
		$this->selectMere= $this->Base->prepare('SELECT nom_page FROM tbl_pages WHERE id_page=?');
		$row=$this->lectureTable($this->selectMere,0,$mere);
		return $row["nom_page"];
	}
	
	// Modification de l'ordre des paragraphes
	// $id (int) : page concernée
	// $ serie (array) : ordre des paragraphes 
    public function ordrePara($serie, $id){
		// Je vais chercher le contenu actuel
        $row=$this->infosPage($id);
        $contenu=$row["contenu"];
		// Je parcours le contenu actuel et j'empile les différents paragraphes dans l'ordre donné par $serie
        $texte="";
        foreach($serie as $s){
			$req="";
            //ereg("\/\*indic_par_".$s."(.*)\/\*end_par_".$s,$contenu,$req);
			ereg("\/\*indic_par_".$s."_(.*)\/\*end_par_".$s."\*\/",$contenu,$req);
			$string=$req[1];
			if(substr($string,0,3)=="tpl") $texte.="/*indic_par_".$s."_".$req[1]."/*end_par_".$s."*/";
			else $texte.="o".$string;
        }
        $this->updateContenuPage->execute(array(":contenu"=>$texte, ":id"=>$id));
		return $texte;
    }
	
	// @desc : Afficher la liste des supports (sous forme de checkbox)
	// @param $id : nom que l'on souhaite donner au select
	// @param $supp : envoi d'une rubrique supplémentaire (avec l'id 0)
	// @return string
	public function listeDeroulRub($id=""){
		// Liste des supports :
		$selectRub= $this->Base->prepare('SELECT id_rubrique, nom_rubrique FROM tbl_rubriques');
		$tab_rubriques= $this->tabIdValue($selectRub, "id_rubrique", "nom_rubrique");
		// Supports à présélectionner (éventuellement)
		//$precoch_liste=$this->infosRubActus($id);
		$html.=$this->listeSelect("rub", $tab_rubriques,"","id=\"selrub\"");
		return $html;
	}


/////////////////////////////////////////////////////////////
/////////////////// CREATION ARBORESCENCE ///////////////////
/////////////////////////////////////////////////////////////
	
	public function listeArbo($rub=""){
		$req=$this->stockeResultats($rub);
		$this->pages_niveau1($req);
		$mont=$this->arborescence($req, $this->tab_pages);
		return $this->affiche_tableau($mont);
	}
	
	// Liste complète des actualités, renvoyée sous forme de tableau associatif :
	public function stockeResultats($rub){
		return $this->lectureTable($this->selectPagesT, 1,$rub);
	}
	
	// Fonction qui récupère les pages sans page-mère (pages de niveau 1) :
	public function pages_niveau1($req){
		$i=0;
		if(!empty($req)){
			foreach($req as $row){
				if(($row["id_page"]==$row["link_pagemere"] OR ($row["link_pagemere"]==0)) && !in_array($row["id_page"], $this->tab_traitees) ){
					$this->tab_pages[$i]["id_page"]=$row["id_page"];
					$this->tab_pages[$i]["nom_page"]="<a href='index.php?page=pages/page.modifie&id=".$row["id_page"]."&pm=".$row["link_pagemere"]."'>".$row["nom_page"]."</a>";
					$this->tab_pages[$i]["souspage"]=array();
					$this->tab_traitees[]=$row["id_page"];
					$i++;
				}
			}
		}else $this->contenu.="Cette rubrique est vide.";
		array_unique($this->tab_traitees);
	}
	
	// Fonction récursive qui remplit le tableau multidimensionnel pour créer l'arborescence
	// (passage de variables par référence)
	public function arborescence($rs,&$tableau){
		$i=0;
		foreach ($tableau as $valeur) {
			$j=0;
			foreach($rs as $row){
				if($valeur["id_page"]==$row["link_pagemere"] && !in_array($row["id_page"], $this->tab_traitees)){
					$tableau[$i]["souspage"][$j]["id_page"]=$row["id_page"];
					$tableau[$i]["souspage"][$j]["nom_page"]="<a href='index.php?page=pages/page.modifie&id=".$row["id_page"]."&pm=".$row["link_pagemere"]."'>".$row["nom_page"]."</a>";
					$tableau[$i]["souspage"][$j]["souspage"]=array();
					$this->tab_traitees[]=$row["id_page"];
					if(is_array($tableau[$i]["souspage"][$j])) $this->arborescence($rs,$tableau[$i]["souspage"]);
					$j++;
				}
			}
			$i++;		
		}
		return $tableau;
	}	
	 //Fonction d'affichage
	public function affiche_tableau($tableau){
		foreach ($tableau as $cle=>$valeur) {
			// récursivité (si le niveau suivant est encore un tableau...):
			if((is_array($valeur))&&(!empty($valeur))&&($valeur!="")) {
				$this->niv_tableau++;
				$this->contenu.="<ul id='".$valeur["id_page"];
				$this->contenu.="'>"; 
				$this->bid++;
				$this->affiche_tableau($valeur); 
				$this->bid--;
				$this->contenu.="</ul>"; 
			// ou affichage des pages (dernier niveau arborescence)
			}else{
				if(($cle!="id_page")&&(!empty($valeur))){
					if($this->bid<6){
						ereg("page.modifie\&(.*)\&pm", $valeur, $req);
						$this->contenu.="<li>".$valeur." <b><a href='index.php?page=pages/page.ajoutfille&".$req[1]."' id='".str_replace("id=","",$req[1])."' class='ajpage'>+</a></b>";
					}else $this->contenu.="<li>".$valeur;
					$this->contenu.="</li>\n";
				}
			} 
		}
		//$this->contenu=str_replace("<ul id=''", "<ul style='background-color:#CCCCCC;'", $this->contenu);
		return $this->contenu;
	}
	
	public function Affiche_souspages($id, $nombre){
		$req2=$this->Base->prepare("SELECT id_page, nom_page, link_pagemere FROM tbl_pages WHERE link_pagemere=?");
		$r2=$this->lectureTable($req2,1, $id);
		foreach($r2 as $row2){
			echo $row2["nom_page"];
			//if(is_array($r)) $Contenu.="tab";
			//else $Contenu.="pas un tab";
		}
	}
	
}

?>