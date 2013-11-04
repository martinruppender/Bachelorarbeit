<?php
/**
 * Anweisungen die mit // Auskommentiert sind sind als Alternaivlösung mit Helper gedacht.
 */
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
App::import('Controller', 'Copies');
App::import('Controller', 'Extracts');
App::import('Controller', 'Removes');
App::import('Controller', 'Converters');

class PagesController extends AppController {

	//public $helpers = array('Extract');

	public function index() {

		/*Erstellen der Pfade für Zwischenspeicher und Ausgabeordner*/
		$outputfolder = 'C:'.DS.'PPTX-Konverter';
		$tempFolder = 'C:'. DS .'PPTX-TMP';
	
		/*Abfangen der vom Button ausgelösten Action auf der Indexseite*/
		if ($this->request->is('post')) {
						
			/*Speichert die geladene Datei in einer Variablen zwischen.*/
			$uploadData = array_shift($this->request->data['Course']);
			
			$fileName = substr($uploadData['name'], 0,-5) .'.zip';
			$uploadPath =  $tempFolder . DS . $fileName;
							
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
			if( !file_exists($tempFolder) ){
				mkdir($tempFolder);
			}
			
			/*Kopieren und entpacken der gleadenen Datei*/
			if (move_uploaded_file($uploadData['tmp_name'], $uploadPath)) {

				$extract = new ExtractsController();				
				$extract->extract($tempFolder, $fileName);
				
				/*Kopeiren von Media-Daten die nicht geändert werden*/
				$copies = new CopiesController();
				$copies->copyMedia($tempFolder.DS.'ppt\media', $outputfolder.DS.'media');

				/*
				$converter = New ConvertersController();
				$converter->convert($tempFolder.DS.'ppt', $outputfolder);
				*/
				/*
				$remove = new RemovesController();
				$remove->deleteDir($tempFolder);
				*/
			}	
		}
	}
}