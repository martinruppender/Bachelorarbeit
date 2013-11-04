<?php

App::uses('File', 'Utility');

class ConvertersController extends AppController{

	public function convert($tempFolder, $outputfolder){

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
				</div>');
	}
}
