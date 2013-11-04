<?php

class ExtractsController extends AppController{

	public function extract($tempFolder, $fileName){
		
		$this->set('pptx_path', $fileName);
		$zip = new ZipArchive;
		$zip->open($tempFolder . DS . $fileName);
		$zip->extractTo($tempFolder);
		$zip->close();
	}
}