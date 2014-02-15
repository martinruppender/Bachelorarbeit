<?php

class TextController extends AppController{
	
	public static function  getText($node, $namespaces){
		return  TextController::text($node, $namespaces);
	}
	
	private static function text($node, $namespaces){
	
		$openDIV = false;
		$text ='';
	
		//Durchlaufe alle Elemente in Textknoten
		foreach ($node as $key=>$node){
			
			if($key=='pPr'){
				
				//�ffne Feld fals ein Textpositionselement vorhanden ist
				if((string)$node->attributes() == 'r'){
					$text = '<div align="right">';
					$openDIV = true;
				}
	
				if((string)$node->attributes() == 'ctr'){
					$text= '<div align="center">';
					$openDIV = true;
				}
			}
	
			//Pr�fe auf Textelement
			if($key=='r'){
	
				$startTags ='';
				$endTags ='';
	
				foreach ($node->children($namespaces['a'])as $key1=>$node1){
	
					//Pr�fen auf Formatierungselement
					if($key1 =='rPr'){
	
						//Pr�fen auf Fontelement und ensprechende �nderungen sowie �bergabe in HTML
						if($node1->children($namespaces['a'])){
							$font = $node1->children($namespaces['a']);
	
							$startTags = '<font';
	
							//Pr�fen ob Latain ge�ndert wurde
							if(array_key_exists('latin', $font)){
								$latin = $font->latin[0]->attributes();
								$startTags = $startTags.' face="'.$latin.', Arial"';
							}
	
							//Pr�fen ob Farbe ge�ndert wurde
							if(array_key_exists('solidFill', $font)){
	
								if(array_key_exists('srgbClr',$font->solidFill->children($namespaces['a']))){
									$colour = $font->solidFill->srgbClr[0]->attributes();
									$startTags = $startTags.' color="#'.(string)$colour['val'].'">';
								}
								if(array_key_exists('schemeClr',$font->solidFill->children($namespaces['a']))){
									$colour = $font->solidFill->schemeClr[0]->children($namespaces['a']);
									#$colour = $colour[0]->attributes();
									#(string)$colour['val'];
									$startTags = $startTags.' color="#000000">';
								}
	
							}else{
								$startTags = $startTags.'>';
							}
	
							$endTags = '</font>';
						}
	
						//Pr�fen ob Text kursiv, Fett, unterstrichen, durchgestrichen ist oder die Gr��e ge�ndert wurde und �bergabe in HTML
						foreach ($node1->attributes() as $k=>$v){
	
							if($key1 =='rPr'){
								if($k == 'b'){
									$startTags = $startTags.'<b>';
									$endTags = '</b>'.$endTags;
								}
	
								if($k == 'i'){
									$startTags = $startTags.'<i>';
									$endTags = '</i>'.$endTags;
								}
								if($k == 'u'){
									$startTags = $startTags.'<u>';
									$endTags = '</u>'.$endTags;
								}
								if($k == 'strike'){
									if($v == 'sngStrike'){
										$startTags = $startTags.'<s>';
										$endTags = '</s>'.$endTags;
									}
								}
								if($k == 'sz'){
									$startTags= '<span style="font-size:'.substr($v,0,-2).'pt">'.$startTags;
									$endTags = $endTags.'</span>';
								}
							}
						}
					}
					//Auslesen des Reintextes und Pr�fen auf Sonderzeichen
					if($key1 =='t'){
						$line = (string)$node1;
						$line = TextController::sonderzeichen($line);
						$text = $text.$startTags.$line.$endTags;
					}
				}
			}
			//Text auf Zeilenumbruch pr�fen
			if($key== 'br'){
				$text = $text.'<br>';
			}
		}
		//Schlie�en der Positionsdiv fals vorhanden
		if($openDIV == true){
			$text = $text.'</div>';
			$openDIV=false;
		}
		$text = $text.'<br>';
	
		return $text;
	}
	
	private static function sonderzeichen($text){
	
		//Text auf nicht verarbeitbare Zeichen pr�fen und durch Zeichenreferenz ersetzen
		$text = ereg_replace("<","&lt;", $text);
		$text = ereg_replace(">","&gt;", $text);
		return $text;
	}
}