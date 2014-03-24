<?php

class ExtractsController extends AppController{

	public function extract($tempFolder, $fileName){
		
		$zip = new ZipArchive;
		$zip->open($tempFolder . DS . $fileName);
		$zip->extractTo($tempFolder);
		$zip->close();
	}
	
	public function download($folder){
	
		$zip = new ZipArchive;
		$zip->open($this->webroot.'files'.DS.'reveal.zip');
		$zip->extractTo($folder);
		$zip->close();
	}
}