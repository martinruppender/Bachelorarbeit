<?php

class MediaController extends AppController{
	
	public static function getImages($xmlreal, $node){
		return MediaController::images($xmlreal, $node);
	}
	
	public static function getAudio($xmlreal, $node){
		return MediaController::audio($xmlreal, $node);
	}
	
	private static function images($xmlreal, $node){
	
		$id = (string) $node[0]->attributes('r', true);
	
		$phototype = array("jpg","jpeg","jpe","png","iwf","svg", "svgz","gif" );
	
		foreach ($xmlreal->children() as $child) {
	
			$children = $child->attributes();
			if($children->Id == $id){
				$target = $children->Target;
				if(in_array(substr((string)$target,-4),$phototype) || in_array(substr((string)$target,-3),$phototype)){
					$media = substr((string)$target,3);
					return '<img src="'.$media.'" alt="Bild">';
				}
			}
		}
	}
	
	private static function audio($xmlreal, $node){
	
		$id = (string) $node[0]->attributes('r', true);
	
		$videotype = array("mp3","ogg", "wav");
	
		foreach ($xmlreal->children() as $child) {
	
			$children = $child->attributes();
			if($children->Id == $id){
				$target = $children->Target;
				if(in_array(substr((string)$target,-3),$videotype)){
					$media = substr((string)$target,3);
					return '<audio controls><source src="'.$media.'" type="audio/mpeg">Your browser does not support the audio element.</audio>';
				}else{
					$media = substr((string)$target,3);
					MessagesController::setAudio($media);
	
					return '<audio controls><source src="'.substr($media,-3).'mp3" type="audio/mpeg">Your browser does not support the audio element.</audio>';
				}
			}
		}
	}
}