<?php



class ConvertersController extends AppController{

	public static function convert($file){

		/*Erstellen der Pfade für Zwischenspeicher und Ausgabeordner*/
		$fileName = substr($file['name'], 0,-5);

		$outputfolder = 'C:'.DS.$fileName;
		$tempFolder = 'C:'.DS.$fileName.DS.'TMP';

		FoldersController::folderMkdir($outputfolder);
		FoldersController::folderMkdir($tempFolder);
			
		/*Kopieren und entpacken der gleadenen Datei*/
		if (move_uploaded_file($file['tmp_name'], $tempFolder.DS.$fileName.'.zip')) {

			$extract = new ExtractsController();
			
			$extract->extract($tempFolder,$fileName.'.zip');
			$extract->download($outputfolder);

			FoldersController::copyMedia($tempFolder.DS.'ppt'.DS.'media', $outputfolder.DS.'media');
			
			WritersController::writeDatas($outputfolder, $tempFolder.DS.'ppt', $fileName);
		}
	}
}