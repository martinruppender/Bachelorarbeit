<?php

class DiagrammController extends AppController{

	public static function getDiagramms($path, $slide){

		$xmlreal = new SimpleXMLElement(file_get_contents($path.DS.'_rels'.DS.$slide.'.rels'));
		$diagramm = array();

		foreach ($xmlreal->children() as $child) {

			if(substr((string)$child['Target'],3,8) == 'diagrams'){
				array_push($diagramm,substr($path,0,-6).substr((string)$child['Target'],3));
			}
		}

		//Dateienpfade sortieren und keys anpassen
		natsort($diagramm);
		$zw = array();
		foreach ($diagramm as $key => $value){
			array_push($zw, $value);
		}
		$diagramm = $zw;

		//Daten einlesen
		$colors =		new SimpleXMLElement(file_get_contents($diagramm[0]));
		$data = 		new SimpleXMLElement(file_get_contents($diagramm[1]));
		$drawing =		new SimpleXMLElement(file_get_contents($diagramm[2]));
		$layout =		new SimpleXMLElement(file_get_contents($diagramm[3]));
		$quickStyle =	new SimpleXMLElement(file_get_contents($diagramm[4]));

		$drawing = NodesController::registerNamespaces($drawing);
		$namespaces = $drawing->getNamespaces(true);
		$drawing = $drawing->xpath('//dsp:spTree');
		$drawing = $drawing[0]->children($namespaces['dsp']);

		$css = '';
		$html = '';
		$i = 0;
		foreach ($drawing as $key => $value){
			if($key == 'sp'){

				$gr = $value->spPr->children($namespaces['a'])->xfrm->ext->attributes();
				$pos =  $value->spPr->children($namespaces['a'])->xfrm->off->attributes();
				$colormap = ColorController::getColor(substr($path,0,-6));
				$background = '';
				$border = '';
				if(!is_null($value->spPr->children($namespaces['a'])->prstGeom->attributes)){

					switch ((string)$value->spPr->children($namespaces['a'])->prstGeom->attributes()->prst) {
						case 'rect':
							$background = ColorController::getBackground($value->spPr->children($namespaces['a']), $colormap['theme1'], '');
							if(!isset($value->spPr->children($namespaces['a'])->ln->noFill)){
								$border = 'border: 1px solid '.subStr(ColorController::getBackground($value->spPr->children($namespaces['a'])->ln, $colormap['theme1'], ''),-7).';';
							}
							$text = '';
							foreach ($value->txBody->children($namespaces['a']) as $key1=>$node1){
								//Textknotenfiltern und Texteditor aufrufen
								if($key1 == 'p'){
									if($text == ''){
										if(substr($text.TextController::text($node1, $namespaces, $colormap),4) == '<br>'){
											$text = substr($text.TextController::text($node1, $namespaces, $colormap),4);
										}else{
											$text = $text.TextController::text($node1, $namespaces, $colormap);
										}
									}else{
										$text = $text.TextController::text($node1, $namespaces, $colormap);
									}
								}
							}
								
							$css = $css.'.'.subStr($slide,0,-4).'rectangle'.$i.' {
							position: absolute;
							text-align: center;
							margin:'.round($pos[1]/360000,2).'cm '.round($pos[0]/360000,2).'cm;
							width: '.round($gr[0]/360000,2).'cm;
							height: '.round($gr[1]/360000,2).'cm;
							'.$border.'
							'.$background.'
					}';
							$html = $html.'<div class="'.subStr($slide,0,-4).'rectangle'.$i.'">'.$text.'</div>';
							$i++;
							break;

						case 'triangle':
							$background = '#'.$colormap['theme1'][(string)$value->spPr->children($namespaces['a'])->gradFill->gsLst->gs->schemeClr->attributes()->val];
							$css = $css.'.'.subStr($slide,0,-4).'triangle'.$i.'  {
							position: absolute;
							margin:'.round($pos[1]/360000,2).'cm '.round($pos[0]/360000,2).'cm;
							width: 0;
							height: 0;
							border-left: '.round($gr[0]/720000,2).'cm solid transparent;
							border-right: '.round($gr[0]/720000,2).'cm solid transparent;
							border-bottom: '.round($gr[0]/360000,2).'cm solid '.$background.'
					}';
							$html = $html.'<div class="'.subStr($slide,0,-4).'triangle'.$i.'"></div>';
							$i++;
							break;

						case'ellipse':
							$background = ColorController::getBackground($value->spPr->children($namespaces['a']), $colormap['theme1'], '');
							if(!isset($value->spPr->children($namespaces['a'])->ln->noFill)){
								$border = 'border: 1px solid '.subStr(ColorController::getBackground($value->spPr->children($namespaces['a'])->ln, $colormap['theme1'], ''),-7).';';
							}
							$text = '';
							foreach ($value->txBody->children($namespaces['a']) as $key1=>$node1){
								//Textknotenfiltern und Texteditor aufrufen
								if($key1 == 'p'){
									if($text == ''){
										if(substr($text.TextController::text($node1, $namespaces, $colormap),4) == '<br>'){
											$text = substr($text.TextController::text($node1, $namespaces, $colormap),4);
										}else{
											$text = $text.TextController::text($node1, $namespaces, $colormap);
										}
									}else{
										$text = $text.TextController::text($node1, $namespaces, $colormap);
									}
								}
							}
							$css = $css.'.'.subStr($slide,0,-4).'circle'.$i.' {
							position: absolute;
							text-align: center;
							margin:'.round($pos[1]/360000,2).'cm '.round($pos[0]/360000,2).'cm;
							width: '.round($gr[0]/360000,2).'cm;
							height: '.round($gr[1]/360000,2).'cm;
							'.$border.'
							border-radius: '.round($gr[1]/360000,2).'cm;'.
							$background.'
					}';
							$html = $html.'<div class="'.subStr($slide,0,-4).'circle'.$i.'">'.$text.'</div>';
							$i++;

							break;

						case'roundRect':
							if(!isset($value->spPr->children($namespaces['a'])->solidFill)){
								//debug($value->spPr->children($namespaces['a'])->gradFill->gsLst->gs->schemeClr);
								$background = 'background-color: #'.$colormap['theme1'][(string)$value->spPr->children($namespaces['a'])->gradFill->gsLst->gs->schemeClr->attributes()->val];
							}else{
								$background = ColorController::getBackground($value->spPr->children($namespaces['a']), $colormap['theme1'], '');
							}
							if(!isset($value->spPr->children($namespaces['a'])->ln->noFill)){
								$border = 'border: 1px solid '.subStr(ColorController::getBackground($value->spPr->children($namespaces['a'])->ln, $colormap['theme1'], ''),-7).';';
							}
							$text = '';
							foreach ($value->txBody->children($namespaces['a']) as $key1=>$node1){
								//Textknotenfiltern und Texteditor aufrufen
								if($key1 == 'p'){
									if($text == ''){
										if(substr($text.TextController::text($node1, $namespaces, $colormap),4) == '<br>'){
											$text = substr($text.TextController::text($node1, $namespaces, $colormap),4);
										}else{
											$text = $text.TextController::text($node1, $namespaces, $colormap);
										}
									}else{
										$text = $text.TextController::text($node1, $namespaces, $colormap);
									}
								}
							}
							$css = $css.'.'.subStr($slide,0,-4).'roundRect'.$i.' {
							position: absolute;
							text-align: center;
							margin:'.round($pos[1]/360000,2).'cm '.round($pos[0]/360000,2).'cm;
							width: '.round($gr[0]/360000,2).'cm;
							height: '.round($gr[1]/360000,2).'cm;
							'.$border.'
							border-radius: '.(round($gr[1]/360000,2)/3).'cm;'.
							$background.'
					}';
							$html = $html.'<div class="'.subStr($slide,0,-4).'roundRect'.$i.'">'.$text.'</div>';
							$i++;
							break;
					}
				}
			}
		}

		return array($html, $css);
	}
}