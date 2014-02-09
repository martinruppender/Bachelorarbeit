<?php

App::uses('File', 'Utility');
App::import('Controller', 'Messages');

class WritersController extends Appcontroller{

	public function writeDatas($outputfolder, $tempFolder, $fileName){

		$slides = scandir($tempFolder);
		natsort($slides);

		$fileHTML = file_get_contents($outputfolder.DS.'index.html', "r+");
		$fileCSS = file_get_contents($outputfolder.DS.'css'.DS.'konverter.css', "r+");
		$fileHTML = ereg_replace("inputtitel",$fileName, $fileHTML);

		$inputsildes = '';
		$css = '';

		foreach ($slides as $slide){
			if($slide[0] != '_'){
				if(is_dir($slide) == false){
					$output = $this->converter($outputfolder.DS.'TMP'.DS.'ppt'.DS.'slides',$slide);
					$inputsildes = $inputsildes.$output[0];
					$css = $css.$output[1];
				}
			}
		}

		$fileHTML = ereg_replace("inputsildes",$inputsildes, $fileHTML);
		$fileCSS = ereg_replace("css",$css, $fileCSS);
		file_put_contents($outputfolder.DS.'index.html', $fileHTML);
		file_put_contents($outputfolder.DS.'css'.DS.'konverter.css', $fileCSS);
	}

	private function converter($path,$slide){

		$css = '';
		$xmlreal = new SimpleXMLElement(file_get_contents($path.DS.'_rels'.DS.$slide.'.rels'));
		$xml = new SimpleXMLElement(file_get_contents($path.DS.$slide));

		$namespaces = $xml->getNamespaces(true);

		foreach ($namespaces as $key=>$value){
			$xml->registerXPathNamespace($key, $value);
		}

		$node = $xml->xpath('//p:cSld');
		$child = $node[0]->children($namespaces['p']);
		if(isset($child->bg)){
			$child = $child->bg->bgPr;
			$child = $child->children($namespaces['a']);
			$backgroundslide = (string) $child->solidFill->srgbClr->attributes();

		}else{
			$backgroundslide = 'FFFFFF';
		}
		$css = $css.'.'.substr($slide,0,-4).'{background-color: #'.$backgroundslide.'; position:absolute; width: 25.4cm; height: 19.05cm; }';

		$inputsildes = '<section><div class="'.substr($slide,0,-4).'">';

		$node = $xml->xpath('//p:spTree');

		$subnode = $node[0]->children($namespaces['p']);

		$spNr = 0;
		$picNr = 0;

		foreach ($subnode as $key=>$node){

			if($key == 'sp'){

				if(isset($node->spPr->xfrm)){
					$size = $node->spPr->children($namespaces['a'])->xfrm->ext->attributes();
					$pos = $node->spPr->children($namespaces['a'])->xfrm->off->attributes();

				}else{
					$size = array(0,0);
					$pos = array(0,0);
				}

				$background ='';

				if(!isset($node->spPr->noFill)){

					$background = 'background-color: #'.(string)$node->spPr->children($namespaces['a'])->solidFill->srgbClr->attributes();
				}

				$css = $css.'.'.substr($slide,0,-4).'sp'.$spNr.'{position:absolute; top:'.round($pos[1]/360000,2).'cm; left:'.round($pos[0]/360000,2).'cm; height:'.round($size[1]/360000,2).'cm; width:'.round($size[0]/360000,2).'cm; '.$background.'}';

				$inputsildes = $inputsildes.'<div class="'.substr($slide,0,-4).'sp'.$spNr++.'">';

				foreach ($node->txBody->children($namespaces['a']) as $key1=>$node1){
					if($key1 == 'p'){
						$inputsildes = $inputsildes.$this->text($node1, $namespaces);
					}
				}

				$inputsildes = $inputsildes.'</div>';
			}

			if($key == 'pic'){

				if(isset($node->spPr)){
					$size = $node->spPr->children($namespaces['a'])->xfrm->ext->attributes();
					$pos = $node->spPr->children($namespaces['a'])->xfrm->off->attributes();
				}else{
					$size = array(0,0);
					$pos = array(0,0);
				}

				$css = $css.'.'.substr($slide,0,-4).'pic'.$picNr.'{position:absolute; top:'.round($pos[1]/360000,2).'cm; left:'.round($pos[0]/360000,2).'cm; height:'.round($size[1]/360000,2).'cm; width:'.round($size[0]/360000,2).'cm}';

				$inputsildes = $inputsildes.'<div class="'.substr($slide,0,-4).'pic'.$picNr++.'">';

				if(isset($node->nvPicPr->nvPr->children($namespaces['a'])->audioFile)){

					$inputsildes = $inputsildes.$this->images($xmlreal, $node->blipFill->children($namespaces['a'])->blip);
					$inputsildes = $inputsildes.$this->audio($xmlreal,$node->nvPicPr->nvPr->children($namespaces['a'])->audioFile);

				}else{
					$inputsildes = $inputsildes.$this->images($xmlreal, $node->blipFill->children($namespaces['a'])->blip);
				}

				$inputsildes = $inputsildes.'</div>';
			}

			if($key == 'graphicFrame'){

				if(isset($node->xfrm)){
					$size = $node->xfrm->children($namespaces['a'])->ext->attributes();
					$pos = $node->xfrm->children($namespaces['a'])->off->attributes();
				}else{
					$size = array(0,0);
					$pos = array(0,0);
				}
					
				$css = $css.'.'.substr($slide,0,-4).'gFrame'.$picNr.'{position:absolute; top:'.round($pos[1]/360000,2).'cm; left:'.round($pos[0]/360000,2).'cm; height:'.round($size[1]/360000,2).'cm; width:'.round($size[0]/360000,2).'cm}';
					
				$inputsildes = $inputsildes.'<div class="'.substr($slide,0,-4).'gFrame'.$picNr++.'">';

				$inputsildes = $inputsildes.$this->diagram($xmlreal, $path, $node->children($namespaces['a'])->graphic->graphicData->children($namespaces['c']));
					
				$inputsildes = $inputsildes.'</div>';
			}

		}

		$inputsildes = $inputsildes.'</div></section>';

		$output = array($inputsildes, $css);

		return $output;
	}

	private function images($xmlreal, $node){

		$id = (string) $node[0]->attributes('r', true);

		$phototype = array("jpg","jpeg","jpe","png","iwf","svg", "svgz","gif" );

		foreach ($xmlreal->children() as $child) {

			$children = $child->attributes();
			if($children->Id == $id){
				$target = $children->Target;
				if(in_array(substr((string)$target,-4),$phototype) || in_array(substr((string)$target,-3),$phototype)){
					$media = substr((string)$target,3);
					return '<img src="'.$media.'" alt="Bild">';
				}
			}
		}
	}

	private function audio($xmlreal, $node){

		$id = (string) $node[0]->attributes('r', true);

		$videotype = array("mp3","ogg", "wav");

		foreach ($xmlreal->children() as $child) {

			$children = $child->attributes();
			if($children->Id == $id){
				$target = $children->Target;
				if(in_array(substr((string)$target,-3),$videotype)){
					$media = substr((string)$target,3);
					return '<audio controls><source src="'.$media.'" type="audio/mpeg">Your browser does not support the audio element.</audio>';
				}else{
					$media = substr((string)$target,3);
					MessagesController::setAudio($media);

					return '<audio controls><source src="'.substr($media,-3).'mp3" type="audio/mpeg">Your browser does not support the audio element.</audio>';
				}
			}
		}
	}

	private function diagram($xmlreal, $path, $node){
		$id =  (string)$node->attributes('r', true);

		$label = null;
		foreach ($xmlreal->children() as $child) {
				
			$children = $child->attributes();
			if($children->Id == $id){

				$target = $children->Target;
				$chart =  new SimpleXMLElement(file_get_contents(substr($path,0,-7).substr($target,2)));
				$namespace = $chart->getNamespaces(true);
				$chart = $chart->children($namespace['c'])->chart->plotArea;

				$labels = $chart->barChart->ser->cat->strRef->strCache;

				foreach ($labels->children($namespace['c']) as $key=>$lab){
						
					if($key == 'pt'){

						if($label == null){
							$label = array((string)$lab->v);
						} else{
							array_push($label,(string)$lab->v);
						}
					}
				}
			}
		}
		
		$chartlabels = null;
		foreach ($label as $lab){
			if($chartlabels == null){
				$chartlabels = '"'.$lab.'"';
			}else{
				$chartlabels = $chartlabels.',"'.$lab.'"';
			}
			
		}
		
		$name = substr($target,10,-4);
		return '<canvas id="'.substr($target,10,-4).'" width="400" height="400"></canvas>
				<script>
					var barChartData = {
						labels : ['.$chartlabels.'],
						datasets : [{
							fillColor : "rgba(220,220,220,0.5)",
							strokeColor : "rgba(220,220,220,1)",
							data : [4.3,2.5,3.5,4.5]
							},
							{
							fillColor : "rgba(151,187,205,0.5)",
							strokeColor : "rgba(151,187,205,1)",
							data : [2.4,4.4,1.8,2.8]
							},
							{
							fillColor : "rgba(141,187,225,0.5)",
							strokeColor : "rgba(151,187,215,1)",
							data : [2,2,3,5]
							}
						]
					}
				var myLine = new Chart(document.getElementById("'.$name.'").getContext("2d")).Bar(barChartData);
				</script>';
	}

	private function text($node, $namespaces){

		$openDIV = false;
		$text ='';

		foreach ($node as $key=>$node){

			if($key=='pPr'){

				if((string)$node->attributes() == 'r'){
					$text = '<div align="right">';
					$openDIV = true;
				}

				if((string)$node->attributes() == 'ctr'){
					$text= '<div align="center">';
					$openDIV = true;
				}
			}

			if($key=='r'){

				$startTags ='';
				$endTags ='';

				foreach ($node->children($namespaces['a'])as $key1=>$node1){

					if($key1 =='rPr'){

						if($node1->children($namespaces['a'])){
							$font = $node1->children($namespaces['a']);

							$startTags = '<font';

							if(array_key_exists('latin', $font)){
								$latin = $font->latin[0]->attributes();
								$startTags = $startTags.' face="'.$latin.', Arial"';
							}

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
					if($key1 =='t'){
						$line = (string)$node1;
						$line = $this->sonderzeichen($line);
						$text = $text.$startTags.$line.$endTags;
					}
				}
			}

			if($key== 'br'){
				$text = $text.'<br>';
			}
		}
		if($openDIV == true){
			$text = $text.'</div>';
			$openDIV=false;
		}
		$text = $text.'<br>';

		return $text;
	}

	private function sonderzeichen($text){

		$text = ereg_replace("<","&lt;", $text);
		$text = ereg_replace(">","&gt;", $text);
		return $text;
	}
}