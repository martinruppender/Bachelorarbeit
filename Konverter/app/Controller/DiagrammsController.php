<?php

class DiagrammsController extends AppController{

	public static function getDiagramms($xmlreal, $path, $node, $size){

		$id =  (string)$node->attributes('r', true);
		$diagrammdatas = '';
		foreach ($xmlreal->children() as $child) {

			$children = $child->attributes();
			if($children->Id == $id){
				$target = $children->Target;
				$chart =  new SimpleXMLElement(file_get_contents(substr($path,0,-7).substr($target,2)));
				$namespace = $chart->getNamespaces(true);
				$chart = $chart->children($namespace['c'])->chart->plotArea;
				if(isset($chart->barChart)){

					$diagrammdatas = DiagrammsController::barChart($chart->barChart);
				}

				if(isset($chart->pieChart)){

					$diagrammdatas = DiagrammsController::pieChart($chart->pieChart);
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
	}

	private static function barChart($labels){

		$label = array();
		$datas = array();

		$namespace = $labels->getNamespaces(true);
		$flag = false;

		foreach ($labels->children($namespace['c']) as $key=>$lab){

			if($key == 'ser'){

				if($flag == false){

					foreach ($lab->cat->strRef->strCache->children($namespace['c']) as $key1=>$lab1){
							
						if($key1 == 'pt'){

							array_push($label,(string)$lab1->v);
						}
					}
					$flag = true;
				}
				$data = array();
				foreach ($lab->val->numRef->numCache->children($namespace['c']) as $key1=>$lab1){

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
				$values = '{fillColor : "rgba(220,220,220,0.5)",
				strokeColor : "rgba(220,220,220,1)",
				data : ['.$val.']
			}';
			}else{
				$values = $values.',{fillColor : "rgba(220,220,220,0.5)",
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

	private static function pieChart($labels){

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
