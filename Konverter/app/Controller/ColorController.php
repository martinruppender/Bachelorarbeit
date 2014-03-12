<?php
App::import('Controller','Media');

class ColorController extends AppController{

	public static function getColor($tempFolder){

		$colormap =  array();

		$themes= scandir($tempFolder.DS.'theme');
		foreach ($themes as $theme){
			if(is_dir($theme) == false){

				$xml = new SimpleXMLElement(file_get_contents($tempFolder.DS.'theme'.DS.$theme));

				$map = array();

				$namespaces = $xml->getNamespaces(true);

				//Registrieren der Namespaces im XML
				foreach ($namespaces as $key=>$value){
					$xml->registerXPathNamespace($key, $value);
				}
				$colors = $xml->children($namespaces['a'])->themeElements->clrScheme->children($namespaces['a']);

				foreach ($colors as $color){
					if($color->getname() !=  'dk1' && $color->getname() != 'lt1'){
						if($color->getname() == 'dk2'){
							$key = 'tx2';
						}elseif($color->getname() == 'lt2'){
							$key = 'bg2';
						}else{
							$key = $color->getname();
						}
						$val = (string)$color->children($namespaces['a'])->attributes()->val;
						$map[$key] = $val;
					}else{
						if($color->getname() == 'dk1'){
							$key = 'tx1';
						}else{
							$key = 'bg1';
						}
						$val = (string)$color->children($namespaces['a'])->attributes()->lastClr;
						$map[$key] = $val;
					}
				}
				$colormap[substr($theme,0,-4)] = $map;
			}
		}
		return $colormap;
	}

	public static function calculatNewColor($node, $namespaces, $colormap){

		$colors = $colormap[(string)$node->attributes()->val];
		$r = hexdec(substr($colors,0,2));
		$g = hexdec(substr($colors,2,2));
		$b = hexdec(substr($colors,4,2));

		$color = array($r,$g,$b);

		$color = ColorController::rgbToHsl($color);
		if($node->children($namespaces['a'])->count() > 0){
			if($node->children($namespaces['a'])->count() > 1){
				$lm = ((String)$node->lumOff->attributes()->val)/1000;
			}else{
				if(array_key_exists('lumMod',$node)){
					$lm = 100-(((String)$node->lumMod->attributes()->val)/1000);
					
				}else{
					$lm = $b;
				}
			}
			$color[2] = $lm;
		}

		$color= ColorController::hslToRgb($color);
		return $color;
	}

	public static function getBackground($node, $colormap, $xmlreal){

		$namespaces = $node->getNamespaces(true);

		if(array_key_exists('solidFill', $node)){

			if(array_key_exists('schemeClr',$node->solidFill->children($namespaces['a']))){
				$colors  = ColorController::calculatNewColor($node->solidFill->schemeClr, $namespaces, $colormap);
				$background = 'background-color: #'.ColorController::rgbToHex($colors);
				return $background;
			}
			if(array_key_exists('srgbClr',$node->solidFill->children($namespaces['a']))){
				$background = 'background-color: #'.(string)$node->solidFill->srgbClr->attributes();
				return $background;
			}
		}
		if(array_key_exists('blipFill', $node)){
			return 'background-image: url(../'.substr(MediaController::getImages($xmlreal, $node->children($namespaces['a'])->blip),10,-13).'); background-size: 100% 100%;';
		}
		if(array_key_exists('noFill', $node)){
			return '';
		}
	}

	private static function rgbToHex($colors){

		$r = dechex($colors[0]);
		$g = dechex($colors[1]);
		$b = dechex($colors[2]);

		if(strlen($r) == 1){
			$r = '0'.$r;
		}
		if(strlen($g) == 1){
			$g = '0'.$g;
		}
		if(strlen($b) == 1){
			$b = '0'.$b;
		}

		return $r.$g.$b;
	}

	private static function hslToRgb($color){

		$h = $color[0];
		$s = ($color[1]/100);
		$l = ($color[2]/100);

		$r;
		$g;
		$b;

		$c = ( 1 - abs( 2 * $l - 1 ) ) * $s;
		$x = $c * ( 1 - abs( fmod( ( $h / 60 ), 2 ) - 1 ) );
		$m = $l - ( $c / 2 );

		if ( $h < 60 ) {
			$r = $c;
			$g = $x;
			$b = 0;
		} else if ( $h < 120 ) {
			$r = $x;
			$g = $c;
			$b = 0;
		} else if ( $h < 180 ) {
			$r = 0;
			$g = $c;
			$b = $x;
		} else if ( $h < 240 ) {
			$r = 0;
			$g = $x;
			$b = $c;
		} else if ( $h < 300 ) {
			$r = $x;
			$g = 0;
			$b = $c;
		} else {
			$r = $c;
			$g = 0;
			$b = $x;
		}

		$r = ( $r + $m ) * 255;
		$g = ( $g + $m ) * 255;
		$b = ( $b + $m  ) * 255;

		return array( floor( $r ), floor( $g ), floor( $b ) );
	}

	private static function rgbToHsl($color) {
		$r = $color[0];
		$g = $color[1];
		$b = $color[2];

		$r /= 255;
		$g /= 255;
		$b /= 255;

		$max = max( $r, $g, $b );
		$min = min( $r, $g, $b );

		$h;
		$s;
		$l = ( $max + $min ) / 2;
		$d = $max - $min;

		if( $d == 0 ){
			$h = $s = 0; // achromatic
		} else {
			$s = $d / ( 1 - abs( 2 * $l - 1 ) );

			switch( $max ){
				case $r:
					$h = 60 * fmod( ( ( $g - $b ) / $d ), 6 );
					if ($b > $g) {
						$h += 360;
					}
					break;

				case $g:
					$h = 60 * ( ( $b - $r ) / $d + 2 );
					break;

				case $b:
					$h = 60 * ( ( $r - $g ) / $d + 4 );
					break;
			}
		}

		return array( round( $h, 2 ), round( ($s*100), 2 ), round( ($l*100), 2 ) );
	}

}