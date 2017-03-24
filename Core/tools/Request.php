<?php
	//include_once("func.php");
	//用户信息类
	class Request{

		//public $mapping
		//date参数
		public $param;
		//array("type"=>get/post,"data"=>array())
		public $request;

		public function __construct($mapping=null){
			//print_r($_SERVER);exit;
			//这个如果是有值，只保留mapping里的key，空取request
			$this->request = request_filter($mapping,0);

			if(isset($this->request["data"]))$this->param = $this->request["data"];

			$_REQUEST = $this->param;// = $this->request_convert($mapping);
		
		//print_r($param);
		}

		//外部参数和内部参数的映射转化&过滤
		//mapping key=>value 请求的参数=>后台使用的参数
		function request_filter($filter=null){
			
			$request = array();
			
			if(!isset($_SERVER['REQUEST_METHOD']))return array();

			switch($_SERVER['REQUEST_METHOD']){

				case "GET": 
					$request["type"] = strtolower($_SERVER['REQUEST_METHOD']);
				break;

				case "POST": 
					$request["type"] = strtolower($_SERVER['REQUEST_METHOD']);
				break;

			}

			$param = array();

			if(is_array($filter)&&count($filter)){

				foreach ($filter as $k => $v) {
					//请求里有这个值，不是数字索引
					if(isset($_REQUEST[$k])&&!is_numeric($k))
						
						$param[$v] = $_REQUEST[$k];

				}

				$request["data"] = $param;
				//print_r($request);
				return $request;

			}else {
				
				$request["data"] = $_REQUEST;
				//print_r($request);
				return $request;
			}
		}

		function filter($filter=null){
			//print_r($this->param);
			$param = array();

			if(is_array($filter)){

				foreach ($filter as $k => $v) {
					//请求里有这个值，不是数字索引
					if(isset($this->param[$k]))
						
						$param[$v] = $this->param[$k];

					else if(is_numeric($k)&&isset($this->param[$v])){

						$param[$v] = $this->param[$v];
					}

				}

			}
		
			return $param;
		}

		//外部参数和内部参数的映射转化
		function request_convert($mapping=null){

			//$request = array();

			$param = array();

			if(is_array($mapping)){

				foreach ($mapping as $k => $v) {
					//请求里有这个值，不是数字索引
					if(isset($this->param[$k])&&!is_numeric($k))
						
						$this->param[$v] = $this->param[$k];

				}

				//$this->param = $param;
				//print_r($request);
			}
			//return $request;
			//
			$_REQUEST = $this->param;

			return $this->param;

		}


	}

	

	//print_r($_SERVER);


?>