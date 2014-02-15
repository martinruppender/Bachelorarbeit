<?php

App::uses('File', 'Utility');

class FoldersController extends AppController{

	public static function folderMkdir($stct){

		/*Prüft ob das Verzeichniss bereits existiert. Existiert es nicht wird es neu erstellt*/
		if(!file_exists($stct)){
			mkdir($stct);
		}
	}

	public static function copyMedia($stcf,$stct){

		$dir = opendir($stcf);

		/*Durchschauen aller Unterferzeichnisse und dateien die sich in $src befinden mit ausnahme von '.' und '..'*/
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				/*Prüfen ob es sich bei dem Pfad um eine Datei oder einen Ordner handelt*/
				if ( is_dir($stcf . '/' . $file) ) {
					/*recursiver Aufruf für den Fall das es ein Ordner ist*/
					copyMedia($stcf . '/' . $file,$stct . '/' . $file);
				}
				else {
					/*Kopieren der Datei*/
					copy($stcf . '/' . $file,$stct . '/' . $file);
				}
			}
		}
		closedir($dir);
	}

	public static function folderRemove($stct){

		if (! is_dir($stct)) {
			throw new InvalidArgumentException("$stct must be a directory");
		}
		if (substr($stct, strlen($stct) - 1, 1) != '/') {
			$stct .= '/';
		}
		$files = glob($stct . '*', GLOB_MARK);
		foreach ($files as $file) {
			if (is_dir($file)) {
				self::deleteDir($file);
			} else {
				unlink($file);
			}
		}
		rmdir($stct);
	}
}