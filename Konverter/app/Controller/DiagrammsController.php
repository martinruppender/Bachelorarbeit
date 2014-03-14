<?php

class DiagrammsController extends AppController{

	public static function getDiagramms(){

		$css = 'kreis {
		width: 100px;
		height: 100px;
		border-radius: 50px;
		background-color: #ff00ff
	}';
		$html = 'kreis';

		return array($html, $css);
	}
}