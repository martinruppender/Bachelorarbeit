<?php
/**
 * Anweisungen die mit // Auskommentiert sind sind als Alternaivlösung mit Helper gedacht.
 */

App::import('Controller', 'Converters');

class PagesController extends AppController {

	public function index() {

		/*Abfangen der vom Button ausgelösten Action auf der Indexseite*/
		if ($this->request->is('post')) {

			$uploadData = array_shift($this->request->data['Course']);
			
			/*Abfrage auf Dateigröße > = und eventuellen Fehlercode*/
			if ( $uploadData['size'] == 0 || $uploadData['error'] !== 0) {
				$this->Session->setFlash('Keine Datei Ausgewählt.');
				return false;
			}

			$converter = New ConvertersController();
			$converter->convert($uploadData);
		}
	}
}