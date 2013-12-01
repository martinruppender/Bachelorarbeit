<?php

App::uses('File', 'Utility');

class WritersController extends Appcontroller{

	public function writeDatas($outputfolder, $tempFolder, $fileName){

		$slides = scandir($tempFolder);
		natsort($slides);

		$file = file_get_contents($outputfolder.DS.'index.html', "r+");
		$file = ereg_replace("inputtitel",$fileName, $file);
		
 		foreach ($slides as $slide){
	 		if($slide[0] != '_'){
	 			if(is_dir($slide) == false){
	 				$inputsildes = $this->converter($outputfolder.DS.'TMP'.DS.'ppt'.DS.'slides',$slide);
				}
			}
		}
		
		$file = ereg_replace("inputsildes",$inputsildes, $file);
		
		file_put_contents($outputfolder.DS.'index.html', $file);
	}
	
	private function converter($path,$slide){
				
		$inputsildes = '<section>';
		
		$fileXML = $path.DS.$slide;
		$file_relsXML = $path.DS.'_rels'.DS.$slide.'.rels';
		
		$file = simplexml_load_file($fileXML);
		$file_relsXML = simplexml_load_file($file_relsXML);

		foreach ($file->interpret as $element){
			
		}
		
		$inputsildes = $inputsildes.'</section>';
		
		return $inputsildes;
	}
}