<?php

class NodesController extends AppController{

	static function registerNamespaces($node){

		$namespaces = $node->getNamespaces(true);

		//Registrieren der Namespaces im XML
		foreach ($namespaces as $key=>$value){
			$node->registerXPathNamespace($key, $value);
		}

		return $node;
	}
}