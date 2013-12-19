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

		$inputsildes = $this->text($inputsildes, $xml, $namespaces);

		$inputsildes = $this->images($inputsildes, $xmlreal);

		$inputsildes = $inputsildes.'</section>';

		return $inputsildes;
	}

	private function images($inputsildes, $xmlreal){

		$phototype = array("jpg","jpeg","jpe","png","iwf","svg", "svgz","gif" );

		//$videotype = array("mp4", "webm", "ogv", "m4v" );
		//$audiotype = array("mp3", "wav", "ogg");

		foreach ($xmlreal->children() as $child) {
			foreach ($child->attributes() as $element => $target ){
				if($element == "Target"){
					if(in_array(substr((string)$target,-4),$phototype) || in_array(substr((string)$target,-3),$phototype)){
						$media = substr((string)$target,3);
						$inputsildes = $inputsildes.'<img src="'.$media.'" alt="Bild">';
					}
				}
			}
		}

		return $inputsildes;
	}

	private function text($inputsildes, $xml, $namespaces){

		$node = $xml->xpath('//a:p');

		$openDIV = false;

		foreach ($node as $subnode){

			$child = $subnode->children($namespaces['a']);

			foreach ($child as $key=>$node){

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
					
					$ba ='';
					$ia ='';
					$ua ='';
					$be ='';
					$ie ='';
					$ue ='';

					foreach ($node->children($namespaces['a'])as $key1=>$node1){
						
						$s = null;
						
						foreach ($node1->attributes() as $k=>$v){
							if(strlen((string)$k) == 1){
								$s = $k;
								
								if($key1 =='rPr'){
									if($s == 'b'){
										$ba = '<b>';
										$be = '</b>';
									}
										
										
									if($s == 'i'){
										$ia = '<i>';
										$ie = '</i>';
									}
									if($s == 'u'){
										$ua = '<u>';
										$ue = '</u>';
									}
								}
							}
						}
						
						if($key1 =='t'){
							$text = (string)$node1;
							$text = $this->sonderzeichen($text);
							$text = $ba.$ia.$ua.$text.$ue.$ie.$be;
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
		}
		return $inputsildes;
	}

	private function sonderzeichen($text){

		$text = ereg_replace("<","&lt;", $text);
		$text = ereg_replace(">","&gt;", $text);
		return $text;
	}
}