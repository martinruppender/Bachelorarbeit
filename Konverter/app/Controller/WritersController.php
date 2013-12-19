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
		
		$startTag='';
		$endTag='';
		$lineBreak='';

		foreach ($node as $subnode){

			$child = $subnode->children($namespaces['a']);
			
			$openDIV = false;
			
			foreach ($child as $key=>$node){
				if($key='pPr'){
					
					if((string)$node[0]['algn'] == 'r'){
						$startTag = '<div align="right">';
						$openDIV = true;
					}
					
					if((string)$node[0]['algn'] == 'ctr'){
						$startTag = '<div align="center">';
						$openDIV = true;
					}
					if($openDIV == true){
						$endTag = '</div>';
					}
				}
				
				if($key= 'br'){
					$lineBreak='<br>';
				}
				
				debug($key);
				debug($node);
			}
			

			
			/*
			if($subnode->xpath('a:pPr') != null){
				$openDIV = false;

				$positionOfText =$subnode->xpath('a:pPr');
				if((string)$positionOfText[0]['algn'] == 'r'){
					$startTag = '<div align="right">';
					$openDIV = true;
				}
				if((string)$positionOfText[0]['algn'] == 'ctr'){
					$startTag = '<div align="center">';
					$openDIV = true;
				}
				if($openDIV == true){
					$endTag = '</div>';
				}
			}else{
				$endTag = '<br>';
			}
			*/

			$textNodes =$subnode->xpath('a:r');
			foreach ($textNodes as $textNode){
				$text = $textNode->xpath('a:t');

				$text = (string)$text[0];
				$text = ereg_replace("<","&lt;", $text);
				$text = ereg_replace(">","&gt;", $text);

				$inputsildes = $inputsildes.$startTag.$text.$endTag.$lineBreak;
			}
		}
		return $inputsildes;
	}
}