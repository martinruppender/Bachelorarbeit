<?php

class CopiesController extends AppController{

	public function copyMedia($src,$dst){

		$dir = opendir($src);

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