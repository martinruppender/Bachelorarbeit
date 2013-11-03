<?php

class Page extends AppModel {

	public $validate = array(
			'pptx_path' => array(
					'extension' => array(
							'rule' => array('extension', array('pptx')),
							'message' => 'Only pptx files',
					),
					'upload-file' => array(
							'rule' => array('uploadFile'),
							'message' => 'Error uploading file'
					)
			)
	);
}

?>