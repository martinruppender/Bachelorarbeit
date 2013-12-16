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

		$xmlFile  = $path.DS.$slide;
		$relsXMLFile = $path.DS.'_rels'.DS.$slide.'.rels';

		$relsFile = simplexml_load_file($relsXMLFile);

		/*
		$file = file_get_contents($xmlFile, "r+");
		$file = ereg_replace(":","_", $file);
		file_put_contents($xmlFile, $file);

		$file = simplexml_load_file($xmlFile);

		*/

		$phototype = array("jpg","jpeg","jpe","png","iwf","svg", "svgz","gif" );
		//$videotype = array("mp4", "webm", "ogv", "m4v" );
		//$audiotype = array("mp3", "wav", "ogg");
		
		foreach ($relsFile->children() as $child) {
			foreach ($child->attributes() as $element => $target ){
				if($element == "Target"){
					if(in_array(substr((string)$target,-4),$phototype) || in_array(substr((string)$target,-3),$phototype)){
						$media = substr((string)$target,3);
						$inputsildes = $inputsildes.'<img src="'.$media.'" alt="Bild">';
					}
				}
			}
		}

		$inputsildes = $inputsildes.'</section>';

		return $inputsildes;
	}
}