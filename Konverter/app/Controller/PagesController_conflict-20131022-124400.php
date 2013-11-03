<?php

class PagesController extends AppController {

	public $helpers = array('Extract');

	public function index() {

		if ($this->request->is('post')) {
				
			$uploadData = array_shift($this->request->data['Course']);
			$this->Extract->extract($uploadData);
		}
	}
}
