//fracment

<?php

App::uses('File', 'Utility');
App::import('Controller', 'Folders');
App::import('Controller', 'Extracts');
App::import('Controller', 'Writers');

class ConvertersController extends AppController{

	public function convert($file){

		/*Erstellen der Pfade für Zwischenspeicher und Ausgabeordner*/
		$fileName = substr($file['name'], 0,-5);

		$outputfolder = 'C:'.DS.$fileName;
		$tempFolder = 'C:'.DS.$fileName.DS.'TMP';
		
		$folder = new FoldersController;

		$folder->folderMkdir($outputfolder);
		$folder->folderMkdir($tempFolder);
			
		/*Kopieren und entpacken der gleadenen Datei*/
		if (move_uploaded_file($file['tmp_name'], $tempFolder.DS.$fileName.'.zip')) {

			$extracts = new ExtractsController;
			$extracts->extract($tempFolder,$fileName.'.zip');
			$extracts->download($outputfolder);

			$folder->copyMedia($tempFolder.DS.'ppt'.DS.'media', $outputfolder.DS.'media');

			$writer = new WritersController();
			$writer->writeDatas($outputfolder, $tempFolder.DS.'ppt'.DS.'slides', $fileName);
		}
	}
}