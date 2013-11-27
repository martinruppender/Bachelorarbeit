<?php

App::uses('Controller', 'Extracts');
App::uses('File', 'Utility');

class CopiesController extends AppController{

	public function copyMedia($src,$dst){

		$dir = opendir($src);

		if( !file_exists($dst) ){
			mkdir($dst);
		}
		
		copy(APP.'/webroot/files/css.zip',$dst . '/' . $file);
		
		/*
		$extract = new ExtractsController();
		$extract->extract($dst, 'css.zip');
		*/
		
		/*Durchschauen aller Unterferzeichnisse und dateien die sich in $src befinden mit ausnahme von '.' und '..'*/
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				/*Prüfen ob es sich bei dem Pfad um eine Datei oder einen Ordner handelt*/
				if ( is_dir($src . '/' . $file) ) {
					/*recursiver Aufruf für den Fall das es ein Ordner ist*/
					copyMedia($src . '/' . $file,$dst . '/' . $file);
				}
				else {
					/*Kopieren der Datei*/
					copy($src . '/' . $file,$dst . '/' . $file);
				}
			}
		}
		closedir($dir);
	}
}