<?php

class MessagesController extends AppController{

	public static function setImage($file){

		$image =  Configure::read('iamge');

		if($image == null){
			$image = array($file);
		}else{
			array_push($image, $file);
		}
		Configure::write('image', $audio);
	}

	public static function setAudio($file){

		$audio =  Configure::read('audio');

		if($audio == null){
			$audio = array($file);
		}else{
			array_push($audio, $file);
		}
		Configure::write('audio', $audio);
	}

	public function message(){

		$image = Configure::read('image');
		$audio = Configure::read('audio');
		$message;
		
		if(is_array($image)||is_array($audio)){
			$files ='';
			foreach ($audio as $picture){
				$files = $files.$picture."\n";
			}
			$message = "Die Dateien:\n".$files."muessen konvertiert werden";
		}else{
			$message = 'Konvertiereung erfolgreich beendet';
		}
		
		return $message;
	}
}