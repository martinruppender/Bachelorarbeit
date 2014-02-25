<?php

App::uses('File', 'Utility');
App::import('Controller','Messages');
App::import('Controller','Text');
App::import('Controller','Media');
App::import('Controller','Diagramms');
App::import('Controller','Color');

class WritersController extends Appcontroller{

	private static $colormap;

	public static function writeDatas($outputfolder, $tempFolder, $fileName){

		WritersController::$colormap = ColorController::getColor($tempFolder);

		//Einlesen aller Slides im Ordner Silde
		$slides = scandir($tempFolder.DS.'slides');
		//Alphanummerische Sortierung der Slides zwecks korrekter Wiedergabe der PP-Seiten
		natsort($slides);

		//Enlesen der zu editirenden HTML und CSS Files
		$fileHTML = file_get_contents($outputfolder.DS.'index.html', "r+");
		$fileCSS = file_get_contents($outputfolder.DS.'css'.DS.'konverter.css', "r+");

		$fileHTML = ereg_replace("inputtitel",$fileName, $fileHTML);

		$inputsildes = '';
		$css = '';

		//Durchgehen der einzelenen Slides und Hinhalt in HTML/CSS fähigen String konvertieren
		foreach ($slides as $slide){
			if($slide[0] != '_'){
				if(is_dir($slide) == false){
					$output = WritersController::converter($outputfolder.DS.'TMP'.DS.'ppt'.DS.'slides',$slide);
					$inputsildes = $inputsildes.$output[0];
					$css = $css.$output[1];
				}
			}
		}

		//Schreiben der HTML/CSS fähigen Stings in die zu editirenden Dateien und speichern.
		$fileHTML = ereg_replace("inputsildes",$inputsildes, $fileHTML);
		$fileCSS = ereg_replace("css",$css, $fileCSS);
		file_put_contents($outputfolder.DS.'index.html', $fileHTML);
		file_put_contents($outputfolder.DS.'css'.DS.'konverter.css', $fileCSS);
	}

	private static function converter($path,$slide){

		$css = '';
		$id = array();
		//Laden der XML Inhalte in Variablen
		$xmlreal = new SimpleXMLElement(file_get_contents($path.DS.'_rels'.DS.$slide.'.rels'));
		$xml = new SimpleXMLElement(file_get_contents($path.DS.$slide));

		$namespaces = $xml->getNamespaces(true);

		//Registrieren der Namespaces im XML
		foreach ($namespaces as $key=>$value){
			$xml->registerXPathNamespace($key, $value);
		}

		$timing = $xml->xpath('//p:timing');

		$node = $timing[0]->children($namespaces['p'])->tnLst->par;

		//if($node->children($namespaces['p'])->cTn->children($namespaces['p'])->count() != 0){
		$id = WritersController::findTimingIDs($node->children($namespaces['p']), $namespaces, $id);
		//}

		//Hintergrundfarbe für die Seite setzen und in CSS übergeben
		$node = $xml->xpath('//p:cSld');
		$child = $node[0]->children($namespaces['p']);
		if(isset($child->bg)){
			$child = $child->bg->bgPr->children($namespaces['a']);
			foreach ($child as $k => $bg){
				if($k == 'solidFill'){
					if(isset($bg->children($namespaces['a'])->srgbClr)){
						$backgroundslide = (string) $bg->children($namespaces['a'])->srgbClr->attributes();
					}elseif (isset($bg->children($namespaces['a'])->schemeClr)){

						$colors  = ColorController::calculatNewColor($bg->children($namespaces['a'])->schemeClr, $namespaces, WritersController::$colormap['theme1']);
						$backgroundslide = dechex($colors[0]).dechex($colors[1]).dechex($colors[2]);
					}
					$css = $css.'.'.substr($slide,0,-4).'{background-color: #'.$backgroundslide.'; position:absolute; width: 25.4cm; height: 19.05cm; }';
				}elseif($k == 'blipFill'){
					$css = $css.'.'.substr($slide,0,-4).'{background-image: url(../'.substr(MediaController::getImages($xmlreal, $bg->children($namespaces['a'])->blip),10,-13).'); background-size: 100% 100%; position:absolute; width: 25.4cm; height: 19.05cm; }';
				}
			}
		}else{
			$backgroundslide = 'FFFFFF';
			$css = $css.'.'.substr($slide,0,-4).'{background-color: #'.$backgroundslide.'; position:absolute; width: 25.4cm; height: 19.05cm; }';
		}

		//Seite für HTML öffnen
		$inputsildes = '<section><div class="'.substr($slide,0,-4).'">';
		$node = $xml->xpath('//p:spTree');
		$subnode = $node[0]->children($namespaces['p']);
		//Objektnummerierung innheralb der Slide für CSS
		$spNr = 0;
		$picNr = 0;
		//Einzele Slideinhalte auslesen und bestimmen
		foreach ($subnode as $key=>$node){

			//Prüfen ob es sich um ein Textelement handelt
			if($key == 'sp'){

				$vID = (string)$node->nvSpPr->cNvPr->attributes()->id;

				$frag = '';

				if(in_array($vID, $id)){
					$frag = 'fragment';
				}

				//Position und Größe innerhalb der Slide bestimmen
				if(isset($node->spPr->xfrm)){
					$size = $node->spPr->children($namespaces['a'])->xfrm->ext->attributes();
					$pos = $node->spPr->children($namespaces['a'])->xfrm->off->attributes();

				}else{
					$size = array(0,0);
					$pos = array(0,0);
				}

				//Füllfarbe des Feldes bestimmen und mit Position an CSS übergeben
				$background ='';

				if(!isset($node->spPr->noFill)){
						
					if(array_key_exists('schemeClr',$node->spPr->children($namespaces['a'])->solidFill->children($namespaces['a']))){
						
						$colors  = ColorController::calculatNewColor($node->spPr->children($namespaces['a'])->solidFill->schemeClr, $namespaces, WritersController::$colormap['theme1']);
						
						debug($colors);
						$background = 'background-color: #'.dechex($colors[0]).dechex($colors[1]).'0'.dechex($colors[2]);
						debug($background);
					}
					else{
						$background = 'background-color: #'.(string)$node->spPr->children($namespaces['a'])->solidFill->srgbClr->attributes();
					}
				}
				$css = $css.'.'.substr($slide,0,-4).'sp'.$spNr.'{position:absolute; top:'.round($pos[1]/360000,2).'cm; left:'.round($pos[0]/360000,2).'cm; height:'.round($size[1]/360000,2).'cm; width:'.round($size[0]/360000,2).'cm; '.$background.'}';

				//Div Erstellen und Text aus Slide einlesen und an HTML übergeben
				$text = '';
				$text = $text.'<div class="'.$frag.' '.substr($slide,0,-4).'sp'.$spNr++.'">';

				foreach ($node->txBody->children($namespaces['a']) as $key1=>$node1){
					//Textknotenfiltern und Texteditor aufrufen
					if($key1 == 'p'){
						$text = $text.TextController::text($node1, $namespaces, WritersController::$colormap);
					}
				}
				if($frag == ''){
					$inputsildes = $inputsildes.$text.'</div>';
				}else{
					$k = array_search($vID, $id);
					$narray = array($k =>array($vID=>($text.'</div>')));
					$id = array_replace($id, $narray);
				}
			}

			//Prüfen ob es sich um ein Bild- oder Audioelement handelt
			if($key == 'pic'){

				$vID = (string)$node->nvPicPr->cNvPr->attributes()->id;

				$frag = '';

				if(in_array($vID, $id)){
					$frag = 'fragment';
				}

				//Position und Größe innerhalb der Slide bestimmen und an CSS übergeben
				if(isset($node->spPr)){
					$size = $node->spPr->children($namespaces['a'])->xfrm->ext->attributes();
					$pos = $node->spPr->children($namespaces['a'])->xfrm->off->attributes();
				}else{
					$size = array(0,0);
					$pos = array(0,0);
				}
				$css = $css.'.'.substr($slide,0,-4).'pic'.$picNr.'{position:absolute; top:'.round($pos[1]/360000,2).'cm; left:'.round($pos[0]/360000,2).'cm; height:'.round($size[1]/360000,2).'cm; width:'.round($size[0]/360000,2).'cm}';

				$pic = '';

				//Feld erzeugen
				$pic = $pic.'<div class="'.$frag.' '.substr($slide,0,-4).'pic'.$picNr++.'">';
				//Unterscheidung ob es sich um ein Bild oder Bild mit Audio handelt entsprechenden Converter aufrufen und an HTML übergeben
				if(isset($node->nvPicPr->nvPr->children($namespaces['a'])->audioFile)){

					$pic = $pic.MediaController::getImages($xmlreal, $node->blipFill->children($namespaces['a'])->blip);
					$pic = $pic.MediaController::getAudio($xmlreal,$node->nvPicPr->nvPr->children($namespaces['a'])->audioFile);

				}else{
					$pic = $pic.MediaController::getImages($xmlreal, $node->blipFill->children($namespaces['a'])->blip);
				}

				if($frag == ''){
					$inputsildes = $inputsildes.$pic.'</div>';
				}else{
					$k = array_search($vID, $id);
					$narray = array($k =>array($vID=>($pic.'</div>')));
					$id = array_replace($id, $narray);
				}
			}

			//Prüfen ob es sich um ein Diagramm handelt
			if($key == 'graphicFrame'){

				$vID = (string)$node->nvGraphicFramePr->cNvPr->attributes()->id;

				$frag = '';

				if(in_array($vID, $id)){
					$frag = 'fragment';
				}

				//Position und Größe innerhalb der Slide bestimmen und an CSS übergeben
				if(isset($node->xfrm)){
					$size = $node->xfrm->children($namespaces['a'])->ext->attributes();
					$pos = $node->xfrm->children($namespaces['a'])->off->attributes();
				}else{
					$size = array(0,0);
					$pos = array(0,0);
				}

				$css = $css.'.'.substr($slide,0,-4).'gFrame'.$picNr.'{position:absolute; top:'.round($pos[1]/360000,2).'cm; left:'.round($pos[0]/360000,2).'cm; height:'.round($size[1]/360000,2).'cm; width:'.round($size[0]/360000,2).'cm}';
				$graf='';
				//Feld erzeugen Diagramm in Converter übergeben in in HTML übergeben
				$graf = $graf.'<div class="'.$frag.' '.substr($slide,0,-4).'gFrame'.$picNr++.'">';
				$graf = $graf.DiagrammsController::getDiagramms($xmlreal, $path, $node->children($namespaces['a'])->graphic->graphicData->children($namespaces['c']), $size, WritersController::$colormap);

				if($frag == ''){
					$inputsildes = $inputsildes.$graf.'</div>';
				}else{
					$k = array_search($vID, $id);
					$narray = array($k =>array($vID=>($graf.'</div>')));
					$id = array_replace($id, $narray);
				}
			}
		}

		if($id != null){
			foreach ($id as $key => $vID){
				$inputsildes = $inputsildes.$id[$key][key($vID)];
			}
		}
		//Schließen der Seite
		$inputsildes = $inputsildes.'</div></section>';

		//Rückgabe von HTML und CSS
		return array($inputsildes, $css);
	}

	private static function findTimingIDs($node, $namespaces, $id){

		if($node->getname() == 'spTgt'){
			$spid = (string) $node->attributes()->spid;
			if(!in_array($spid, $id)){
				array_push($id, $spid);
				return $id;
			}

		}elseif($node->count() != 0){
			foreach ($node->children($namespaces['p']) as $key=>$value){
				$id = WritersController::findTimingIDs($value[0], $namespaces, $id);
			}
		}
		return $id;
	}
}