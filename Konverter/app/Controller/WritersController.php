<?php

App::uses('File', 'Utility');
App::import('Controller','Messages');
App::import('Controller','Text');
App::import('Controller','Media');
App::import('Controller','Diagramms');
App::import('Controller','Color');
App::import('Controller','PPTXForms');
App::import('Controller', 'Nodes');

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

		//Laden der XML Inhalte in Variablen
		$xmlreal = new SimpleXMLElement(file_get_contents($path.DS.'_rels'.DS.$slide.'.rels'));
		$xml = new SimpleXMLElement(file_get_contents($path.DS.$slide));
		$xml = NodesController::registerNamespaces($xml);
		$namespaces = $xml->getNamespaces(true);

		//Auslesen aller Elemente welche Animiert sind
		$id = array();
		$timing = $xml->xpath('//p:timing');
		if($timing != null){
			$node = $timing[0]->children($namespaces['p'])->tnLst->par;
			$id = WritersController::findTimingIDs($node->children($namespaces['p']), $namespaces, $id);
		}

		//Elternknoten für Slideelemente setzen
		$node = $xml->xpath('//p:cSld');
		$node = $node[0]->children($namespaces['p']);

		//Hintergrundfarbe für die Seite setzen und in CSS übergeben
		if(isset($node->bg)){
			$backgroundnode = $node->bg->bgPr->children($namespaces['a']);
			$backgroundslide = ColorController::getBackground($backgroundnode, WritersController::$colormap['theme1'], $xmlreal);

		}else{
			$backgroundslide = 'background-color: #FFFFFF';
		}

		$css = '';
		$css = $css.'.'.substr($slide,0,-4).'{'.$backgroundslide.'; position:absolute; width: 25.4cm; height: 19.05cm; }';

		//Seite für HTML öffnen
		$inputsildes = '<section><div class="'.substr($slide,0,-4).'">';
		$node = $xml->xpath('//p:spTree');
		$subnode = $node[0]->children($namespaces['p']);

		//Objektnummerierung innheralb der Slide für CSS
		$slideNr = 0;
		$objekctNr = 0;
		$objecttyps = array('sp','cxnSp','graphicFrame','pic');
		//Einzele Slideinhalte auslesen und bestimmen
		foreach ($subnode as $key=>$node){

			if(in_array($key, $objecttyps)){

				$keyname = 'nv'.ucfirst($key).'Pr';

				//prüfen ob Element animiert ist
				$nodeID = (string)$node->$keyname->cNvPr->attributes()->id;
				$frag = '';
				if(in_array($nodeID, $id)){
					$frag = 'fragment';
				}

				$spPr = $node->spPr->children($namespaces['a']);
				//Position und Größe innerhalb der Slide bestimmen und an CSS übergeben
				if($key != 'graphicFrame' && $spPr->xfrm->count() > 0){
					$size = $spPr->xfrm->ext->attributes();
					$pos = $spPr->xfrm->off->attributes();
				}elseif($key == 'graphicFrame' && $node->xfrm->children($namespaces['a'])->count() > 0){
					$size = $node->xfrm->children($namespaces['a'])->ext->attributes();
					$pos = $node->xfrm->children($namespaces['a'])->off->attributes();
				}
				else{
					$size = array(0,0);
					$pos = array(0,0);
				}

				//Prüfen um was für ein Element es sich handelt
				switch ($key) {
					case "cxnSp":
						PPTXFormsController::getForm($node);
						break;

						//Textelement
					case "sp":
						//Füllfarbe des Feldes bestimmen und mit Position an CSS übergeben
						$background = ColorController::getBackground($node->spPr->children($namespaces['a']), WritersController::$colormap['theme1'], $xmlreal);
						$css = $css.'.'.substr($slide,0,-4).'sp'.$slideNr.'{position:absolute; top:'.round($pos[1]/360000,2).'cm; left:'.round($pos[0]/360000,2).'cm; height:'.round($size[1]/360000,2).'cm; width:'.round($size[0]/360000,2).'cm; '.$background.'}';
						//Div Erstellen und Text aus Slide einlesen und an HTML übergeben
						$text = '';
						$text = $text.'<div class="'.$frag.' '.substr($slide,0,-4).'sp'.$slideNr++.'">';

						foreach ($node->txBody->children($namespaces['a']) as $key1=>$node1){
							//Textknotenfiltern und Texteditor aufrufen
							if($key1 == 'p'){
								$text = $text.TextController::text($node1, $namespaces, WritersController::$colormap);
							}
						}
						if($frag == ''){
							$inputsildes = $inputsildes.$text.'</div>';
						}else{
							$k = array_search($nodeID, $id);
							$narray = array($k =>array($nodeID=>($text.'</div>')));
							$id = array_replace($id, $narray);
						}

						break;
						//Bild- oder Audioelement
					case "pic":
						$css = $css.'.'.substr($slide,0,-4).'pic'.$objekctNr.'{position:absolute; top:'.round($pos[1]/360000,2).'cm; left:'.round($pos[0]/360000,2).'cm; height:'.round($size[1]/360000,2).'cm; width:'.round($size[0]/360000,2).'cm}';
						$pic = '';

						//Feld erzeugen
						$pic = $pic.'<div class="'.$frag.' '.substr($slide,0,-4).'pic'.$objekctNr++.'">';
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
							$k = array_search($nodeID, $id);
							$narray = array($k =>array($nodeID=>($pic.'</div>')));
							$id = array_replace($id, $narray);
						}

						break;
						//Diagramm
					case "graphicFrame":
						$css = $css.'.'.substr($slide,0,-4).'gFrame'.$objekctNr.'{position:absolute; top:'.round($pos[1]/360000,2).'cm; left:'.round($pos[0]/360000,2).'cm; height:'.round($size[1]/360000,2).'cm; width:'.round($size[0]/360000,2).'cm}';
						$graf='';
						if(array_key_exists ('c',$namespaces)){
							//Feld erzeugen Diagramm in Converter übergeben in in HTML übergeben
							$graf = $graf.'<div class="'.$frag.' '.substr($slide,0,-4).'gFrame'.$objekctNr++.'">';
							$graf = $graf.DiagrammsController::getDiagramms($xmlreal, $path, $node->children($namespaces['a'])->graphic->graphicData->children($namespaces['c']), $size, WritersController::$colormap);
							if($frag == ''){
								$inputsildes = $inputsildes.$graf.'</div>';
							}else{
								$k = array_search($vID, $id);
								$narray = array($k =>array($vID=>($graf.'</div>')));
								$id = array_replace($id, $narray);
							}
						}
						break;
				}
			}
		}

		if($id != null){
			foreach ($id as $key => $nodeID){
				$inputsildes = $inputsildes.$id[$key][key($nodeID)];
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