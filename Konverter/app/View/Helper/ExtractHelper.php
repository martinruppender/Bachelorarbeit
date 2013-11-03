<?php
App::uses('AppHelper', 'View/Helper');

class ExtractHelper extends AppHelper{

	function extract($uploadData){
		
		if ( $uploadData['size'] == 0 || $uploadData['error'] !== 0) {
			$this->Session->setFlash('Keine Datei Ausgewählt.');
			return false;
		}

		$uploadFolder = 'C:'. DS .'PPTX-Konverter';
		$fileName = $uploadData['name'].'.zip';
		$uploadPath =  $uploadFolder . DS . $fileName;

		if( !file_exists($uploadFolder) ){
			mkdir($uploadFolder);
		}

		if (move_uploaded_file($uploadData['tmp_name'], $uploadPath)) {
			$this->set('pptx_path', $fileName);
			$zip = new ZipArchive;
			$zip->open($uploadFolder . DS . $fileName);
			$zip->extractTo($uploadFolder);
			$zip->close();
			return true;
		}

		return false;
	}
}