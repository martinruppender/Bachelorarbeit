<?php

App::import('Controller','Color');

class DiagrammsController extends AppController{

	private static $colormap;

	public static function getDiagramms($xmlreal, $path, $node, $size, $colormap){

		DiagrammsController::$colormap = $colormap;
		$flag;

		$id =  (string)$node->attributes('r', true);
		if($id != ''){
			$diagrammdatas = '';
			foreach ($xmlreal->children() as $child) {

				$children = $child->attributes();
				if($children->Id == $id){
					$target = $children->Target;
					$chart =  new SimpleXMLElement(file_get_contents(substr($path,0,-7).substr($target,2)));
					$namespace = $chart->getNamespaces(true);
					$chart = $chart->children($namespace['c'])->chart->plotArea;
					if(isset($chart->barChart)){

						$diagrammdatas = DiagrammsController::barChart($chart->barChart, $colormap);
					}

					if(isset($chart->pieChart)){

						$diagrammdatas = DiagrammsController::pieChart($chart->pieChart, $colormap);
					}
				}
			}

			$name = substr($target,10,-4);
			return '<canvas id="'.$name.'" width="'.round($size[0]/9525).'" height="'.round($size[1]/9525).'"></canvas>
			<script>
			'.$diagrammdatas[1].'
			var ctx = document.getElementById("'.$name.'").getContext("2d");
			var myNewChart = new Chart(ctx).'.$diagrammdatas[0].';
			</script>';
		}else{
			return '';
		}
	}

	private static function barChart($labels, $colormap){

		$label = array();
		$datas = array();
		$colors;

		$namespace = $labels->getNamespaces(true);

		foreach ($labels->ser->children($namespace['c']) as $key=>$lab){

			if($key == 'spPr'){
				if(array_key_exists('srgbClr',$lab->children($namespace['a'])->solidFill)){
					$color = (string)$lab->children($namespace['a'])->solidFill->srgbClr->attributes()->val;
					$r = hexdec(substr($color,0,2));
					$g = hexdec(substr($color,2,2));
					$b = hexdec(substr($color,4,2));
					$colors = array($r, $g, $b);
				}elseif(array_key_exists('schemeClr',$lab->children($namespace['a'])->solidFill)){
					$colors  = ColorController::calculatNewColor($lab->children($namespace['a'])->solidFill->schemeClr, $namespace, DiagrammsController::$colormap['theme1']);
				}
			}

			if($key == 'cat'){

				foreach ($lab->strRef->strCache->children($namespace['c']) as $key1=>$lab1){

					if($key1 == 'pt'){

						array_push($label,(string)$lab1->v);
					}
				}
			}
			if($key == 'val'){
				$data = array();
				foreach ($lab->numRef->numCache->children($namespace['c']) as $key1=>$lab1){

					if($key1 == 'pt'){

						array_push($data,(string)$lab1->v);
					}
				}
				array_push($datas,$data);
			}
		}

		$chartlabels = null;
		foreach ($label as $lab){
			if($chartlabels == null){
				$chartlabels = '"'.$lab.'"';
			}else{
				$chartlabels = $chartlabels.',"'.$lab.'"';
			}
		}

		$values = null;
		foreach ($datas as $data){
			$val = null;
			foreach ($data as $value){
				if($val == null){
					$val = $value;
				}else{
					$val = $val.','.$value;
				}
			}

			if($values == null){
				$values = '{fillColor : "rgba('.$colors[0].','.$colors[1].','.$colors[2].',1)",
				strokeColor : "rgba(220,220,220,1)",
				data : ['.$val.']
			}';
			}else{
				$values = $values.',{fillColor : "rgba('.$colors[0].','.$colors[1].','.$colors[2].',1)",
				strokeColor : "rgba(220,220,220,1)",
				data : ['.$val.']
			}';
			}
		}

		$data = 'var data = {
		labels : ['.$chartlabels.'],
		datasets : ['.$values.']
	}';

		return array('Bar(data);',$data);
	}

	private static function pieChart($labels, $colormap){

		/**
		 *@todo Labels
		 */

		$label = array();
		$colour = array();
		$datas = null;
		$options = '';
		$namespace = $labels->getNamespaces(true);


		foreach ($labels->ser->children($namespace['c']) as $key=>$value){

			if($key == 'dPt'){
				array_push($colour,(string)$value->spPr->children($namespace['a'])->solidFill->srgbClr->attributes()->val);
			}

			if($key == 'val'){
				foreach ($value->numRef->numCache->children($namespace['c']) as $key1=>$value1){

					if($key1 == 'pt'){
						array_push($label,(string)$value1->v);
					}
				}
			}
		}

		$i = 0;

		foreach ($label as $value){

			if($datas == null){
				$datas = '{
				value: '.$value.',
				color:"#'.$colour[$i++].'"
			}';
			}
			else{
				$datas = $datas.',{
				value: '.$value.',
				color:"#'.$colour[$i++].'"
			}';
			}
		}
		$datas = 'var data = ['.$datas.']';

		return array('Pie(data);',$datas);
	}

	/**
	 * @todo witerer Charts einbinden
	 */

}
