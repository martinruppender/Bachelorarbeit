<?php

class TextController extends AppController{

	public static function text($node, $namespaces, $colormap){

		$openDIV = false;
		$text ='';

		if(array_key_exists('t', $node->r)){
			//Durchlaufe alle Elemente in Textknoten
			foreach ($node as $key=>$node){
					
				if($key=='pPr'){

					//Öffne Feld fals ein Textpositionselement vorhanden ist
					if((string)$node->attributes() == 'r'){
						$text = '<div align="right">';
						$openDIV = true;
					}

					if((string)$node->attributes() == 'ctr'){
						$text= '<div align="center">';
						$openDIV = true;
					}
				}

				//Prüfe auf Textelement
				if($key=='r'){

					$startTags ='';
					$endTags ='';


					foreach ($node->children($namespaces['a'])as $key1=>$node1){

						//Prüfen auf Formatierungselement
						if($key1 =='rPr'){

							//Prüfen auf Fontelement und ensprechende Änderungen sowie übergabe in HTML
							if($node1->children($namespaces['a'])){
								$font = $node1->children($namespaces['a']);

								$startTags = '<font';

								//Prüfen ob Latain geändert wurde
								if(array_key_exists('latin', $font)){
									$latin = $font->latin[0]->attributes();
									$startTags = $startTags.' face="'.$latin.', Arial"';
								}

								//Prüfen ob Farbe geändert wurde
								if(array_key_exists('solidFill', $font)){

									if(array_key_exists('srgbClr',$font->solidFill->children($namespaces['a']))){
										$color = $font->solidFill->srgbClr[0]->attributes();
										$startTags = $startTags.' color="#'.(string)$color['val'].'">';
									}
									if(array_key_exists('schemeClr',$font->solidFill->children($namespaces['a']))){
										$colors  = ColorController::calculatNewColor($font->solidFill->schemeClr, $namespaces, $colormap['theme1']);
										$color = dechex($colors[0]).dechex($colors[1]).dechex($colors[2]);
										$startTags = $startTags.' color="#'.$color.'">';
									}

								}else{
									$startTags = $startTags.'>';
								}

								$endTags = '</font>';
							}

							//Prüfen ob Text kursiv, Fett, unterstrichen, durchgestrichen ist oder die Größe geändert wurde und übergabe in HTML
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
						//Auslesen des Reintextes und Prüfen auf Sonderzeichen
						if($key1 =='t'){
							$line = (string)$node1;
							$line = TextController::sonderzeichen($line);
							$text = $text.$startTags.$line.$endTags;
						}
					}
				}
				//Text auf Zeilenumbruch prüfen
				if($key== 'br'){
					$text = $text.'<br>';
				}
			}
			//Schließen der Positionsdiv fals vorhanden
			if($openDIV == true){
				return $text.'</div>';
			}else{
				return '<br>'.$text;
			}
		}
		return '';
	}

	private static function sonderzeichen($text){

		//Text auf nicht verarbeitbare Zeichen prüfen und durch Zeichenreferenz ersetzen
		$text = ereg_replace("<","&lt;", $text);
		$text = ereg_replace(">","&gt;", $text);
		return $text;
	}
}