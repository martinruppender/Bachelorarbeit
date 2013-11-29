<?php

App::uses('File', 'Utility');
App::import('Controller', 'Folders');
App::import('Controller', 'Extracts');

class ConvertersController extends AppController{

	private $outputfolder;
	private $folder;
	//static private $tempFolder;
	//static private $fileName;

	public function convert($file){

		/*Erstellen der Pfade für Zwischenspeicher und Ausgabeordner*/
		$outputfolder = 'C:'.DS.'PPTX-Konverter';
		$tempFolder = 'C:'. DS .'PPTX-TMP';
		$fileName = substr($file['name'], 0,-5) .'.zip';

		$folder = new FoldersController;

		$folder->folderMkdir($outputfolder);
		$folder->folderMkdir($tempFolder);
			
		/*Kopieren und entpacken der gleadenen Datei*/
		if (move_uploaded_file($file['tmp_name'], $tempFolder.DS.$fileName)) {

			$extracts = new ExtractsController;
			$extracts->extract($tempFolder,$fileName);

			$folder->copyMedia($tempFolder.DS.'ppt'.DS.'media', $outputfolder.DS.'media');


		}
	}

}

/*

$datei = new File($outputfolder.DS.'presentation.html', true, 0644);
$datei->create();
$slides = scandir($tempFolder.DS.'slides');

$datei->write('
		<div class="reveal">
		<div class="slides">
		<section>Single Horizontal Slide</section>');

foreach ($slides as $slide){
if($slide[0] != '_'){
if(is_dir($slide) == false){
$name = substr($slide, 0,-4);
$datei->write('<section>Vertical '.$name.'</section>');
}
}
}

$datei->write('	</section>
		</div>
		</div>');*/