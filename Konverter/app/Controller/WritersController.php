<?php

App::uses('File', 'Utility');

class WritersController extends Appcontroller{

	public function writeDatas($outputfolder, $tempFolder, $fileName){

		$slides = scandir($tempFolder);
		natsort($slides);

		$file = file_get_contents($outputfolder.DS.'index.html', "r+");
		$file = ereg_replace("inputtitel",$fileName, $file);

		$inputsildes = '';

		foreach ($slides as $slide){
			if($slide[0] != '_'){
				if(is_dir($slide) == false){
					$inputsildes = $inputsildes.$this->converter($outputfolder.DS.'TMP'.DS.'ppt'.DS.'slides',$slide);
				}
			}
		}

		$file = ereg_replace("inputsildes",$inputsildes, $file);
		file_put_contents($outputfolder.DS.'index.html', $file);
	}

	private function converter($path,$slide){

		$inputsildes = '<section>';

		$xmlreal = new SimpleXMLElement(file_get_contents($path.DS.'_rels'.DS.$slide.'.rels'));
		$xml = new SimpleXMLElement(file_get_contents($path.DS.$slide));

		$namespaces = $xml->getNamespaces(true);

		foreach ($namespaces as $key=>$value){
			$xml->registerXPathNamespace($key, $value);
		}

		$node = $xml->xpath('//p:spTree');

		foreach ($node as $subnode){
			$child = $subnode->children($namespaces['p']);
			foreach ($child as $key=>$node){
				if($key == 'sp'){
					
					$size = $node->spPr->children($namespaces['a'])->xfrm->ext->attributes();
					$pos = $node->spPr->children($namespaces['a'])->xfrm->off->attributes();
					#debug($size);
					$inputsildes = $inputsildes.'<div style="position:absolute; top:'.round($pos[1]/360000,2).'cm; left:'.round($pos[0]/360000,2).'cm; height:'.round($size[1]/360000,2).'cm; width:'.round($size[0]/360000,2).'cm">';

					foreach ($node as $subKey => $subNode){

						if($subKey == 'txBody'){

							$child = $subNode->children($namespaces['a']);

							foreach ($child as $key1=>$node1){
								if($key1 == 'p'){
									$inputsildes = $this->text($inputsildes, $node1, $namespaces);
								}
							}
						}
					}
					$inputsildes = $inputsildes.'</div>';
				}

			}
			if($key == 'pic'){
				$inputsildes = $inputsildes.'<div>';
					
				$inputsildes = $this->images($inputsildes, $xmlreal, $node);

				$inputsildes = $inputsildes.'</div>';
			}
		}

		$inputsildes = $inputsildes.'</section>';

		return $inputsildes;
	}

	private function images($inputsildes, $xmlreal, $node){

		$node = $node->blipFill;
		$namespaces = $node->getNamespaces(true);
			
		$node = $node->children($namespaces['a']);
		$node = $node->blip;

		$id = (string) $node[0]->attributes('r', true);

		$phototype = array("jpg","jpeg","jpe","png","iwf","svg", "svgz","gif" );

		//$videotype = array("mp4", "webm", "ogv", "m4v" );
		//$audiotype = array("mp3", "wav", "ogg");

		foreach ($xmlreal->children() as $child) {

			$children = $child->attributes();
			if($children->Id == $id){
				$target = $child->Target;
				if(in_array(substr((string)$target,-4),$phototype) || in_array(substr((string)$target,-3),$phototype)){
					$media = substr((string)$target,3);
					$inputsildes = $inputsildes.'<img src="'.$media.'" alt="Bild">';
				}
			}
		}

		return $inputsildes;
	}

	private function text($inputsildes, $node, $namespaces){

		$openDIV = false;

		foreach ($node as $key=>$node){

			if($key=='pPr'){

				if((string)$node->attributes() == 'r'){
					$inputsildes = $inputsildes.'<div align="right">';
					$openDIV = true;
				}

				if((string)$node->attributes() == 'ctr'){
					$inputsildes = $inputsildes.'<div align="center">';
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
						$text = (string)$node1;
						$text = $this->sonderzeichen($text);
						$text = $startTags.$text.$endTags;
					}
				}

				$inputsildes = $inputsildes.$text;
			}

			if($key== 'br'){
				$inputsildes = $inputsildes.'<br>';
			}

		}
		if($openDIV == true){
			$inputsildes = $inputsildes.'</div>';
			$openDIV=false;
		}
		$inputsildes = $inputsildes.'<br>';

		return $inputsildes;
	}

	private function sonderzeichen($text){

		$text = ereg_replace("<","&lt;", $text);
		$text = ereg_replace(">","&gt;", $text);
		return $text;
	}
}