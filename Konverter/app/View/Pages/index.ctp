<?php
	/*�ffnet ein Eingabefeldbereich*/
	echo $this->Form->create('Course', array( 'type' => 'file'));
	/*Erstellt das eigendliche Eingabefeld als Fileupload-Fenster mit Label Button zum �ffnen des Auswahldialoges und einer Anzeige der ausgew�hlten Datei*/
	echo $this->Form->input('pptx_path', array('type' => 'file','label' => 'PPTX-Datei'));
	/*Beendet das Eingabeformular und erstellt einen Button zum beginn des Pcozesses im Controller*/
	echo $this->Form->end('Datei Upload');
?>
Die Ausgabe erfolgt im Verzeichnis C: