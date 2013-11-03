<?php
/**
 * Anweisungen die mit // Auskommentiert sind sind als Alternaivlösung mit Helper gedacht.
 */
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
App::import('Controller', 'Copies');
class PagesController extends AppController {

	//public $helpers = array('Extract');

	public function index() {

		/*Erstellen der Pfade für Zwischenspeicher und Ausgabeordner*/
		$outputfolder = 'C:'.DS.'PPTX-Konverter';
		$uploadFolder = 'C:'. DS .'PPTX-TMP';
		$fileName = $uploadData['name'].'.zip';
		$uploadPath =  $uploadFolder . DS . $fileName;
		$copies = new CopiesController();
		
		/*Abfangen der vom Button ausgelösten Action auf der Indexseite*/
		if ($this->request->is('post')) {
						
			/*Speichert die geladene Datei in einer Variablen zwischen.*/
			$uploadData = array_shift($this->request->data['Course']);
			debug($uploadData);
							
			/*Abfrage auf Dateigröße > = und eventuellen Fehlercode*/
			if ( $uploadData['size'] == 0 || $uploadData['error'] !== 0) {
				$this->Session->setFlash('Keine Datei Ausgewählt.');
				return false;
			}

			/*Erstellen des Ordners in dem die HTML Datei geschreiebn werden sollen falls dieser noch nicht vorhanden ist*/
			if(!file_exists($outputfolder)){
				mkdir($outputfolder);
			}
			
			/*Erstellen des Ordners in dem die Datei zwischengespeichert wird falls dieser noch nicht vorhanden ist*/
			if( !file_exists($uploadFolder) ){
				mkdir($uploadFolder);
			}
			
			/*Kopieren und entpacken der gleadenen Datei*/
			if (move_uploaded_file($uploadData['tmp_name'], $uploadPath)) {
				//$this->Extract->extract($uploadData);
				
				$this->set('pptx_path', $fileName);
				$zip = new ZipArchive;
				$zip->open($uploadFolder . DS . $fileName);
				$zip->extractTo($uploadFolder);
				$zip->close();
				
				/*Kopeiren von Media-Daten die nicht geändert werden*/
				$copies->copyMedia($uploadFolder.DS.'ppt\media', $outputfolder.DS.'media');
				
			}	
		}
	}
}
