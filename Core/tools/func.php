<?php
//header("charset=utf-8");


include_once("Right.php");
include_once("Request.php");


ini_set("display_errors", "On");

error_reporting(E_ALL | E_STRICT);
//只转换，不过滤

function req_convert($convert=null){

	$req = new Request();

	return $req->request_convert($convert);

}

function req_filter($filter=null){

	$req = new Request();

	return $req->filter($filter);

}



function  setVaildParam($v,$k,$isNotSetSign = 0){

    return (isset($v[$k]) ? $v[$k] : $isNotSetSign);

}

function param_filter($param,$filter){

	if(is_array($filter)&&is_array($param)){

		foreach ($param as $key => $value) {

			if(!in_array($key,$filter))

				unset($param[$key]);

		}
	}
	return $param;

}
//登入模块
function login_mod(){

	if(!isset($_REQUEST["token"])||empty($_REQUEST["token"]))

		sreturn("no login.");

	else {



	}

}
/*
function db_transaction($pdo,$insert_sql,$delete_sql){

    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

    $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, FALSE);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $isBad = 0;

    try{ 

            $pdo->beginTransaction(); 

            if($delete_sql){
            
                if(false===$pdo->exec($delete_sql)){
                    print_r($pdo->errorInfo());
                    print_r($pdo->errorCode());
                    //echo $delete_sql;
                    $isBad =1;
                }
            }
           

            if(false===$pdo->exec($insert_sql)){

                print_r($pdo->errorInfo());
                //echo $insert_sql;
                $isBad =1;
            }

            if($isBad)return false;

            $pdo->commit(); 


         }catch(PDOException $e){
            echo date("Y-m-d H:i:s").":".$e->getMessage()."\r\n";
            $pdo->rollback();
        }  

        $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);

        return true;
}
*/

function db_transaction($pdo,$sql_list,$bind_param=array()){    

    if(!is_array($sql_list)||count($sql_list)==0)return -1;

    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

    $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, FALSE);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $isBad = 0;

    try{ 

            $pdo->beginTransaction(); 
            
            foreach ($sql_list as $key => $sql) {

                if(count($bind_param)>0){

                    $stmt = $pdo->prepare($sql);
                    //echo $sql;
                    //$stmt->bindParam();
                    //print_r($bind_param[$key]);
                    if(false===$stmt->execute($bind_param[$key])){

                        print_r($pdo->errorInfo());
                        print_r($pdo->errorCode());
                        //echo $insert_sql;
                        $isBad =1;
                    };


                }else{
                    //echo $sql;
                    if(false===$pdo->exec($sql)){

                        print_r($pdo->errorInfo());
                        print_r($pdo->errorCode());
                        //echo $insert_sql;
                        $isBad =1;
                    }
                }
                if($isBad)return false;
                //exit;
            }

            $pdo->commit(); 


         }catch(PDOException $e){
            echo date("Y-m-d H:i:s").":".$e->getMessage()."\r\n";
            $pdo->rollback();
        }  

        $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);

        return true;
}



 function vaildLogin($tabel = "bg_user",$vaild_sql="",$vaild_pdo=null,$rediret_login=null){

        $token = null;

        if(isset($_POST['token'])) $token = $_POST['token'];

        elseif(isset($_GET['token'])) $token = $_GET['token'];

        else if($rediret_login){

        	header("Location:$rediret_login");

        }else {
        	sreturn("请登录.");
        }

        if($token == "0c673bc05d02c3f0f5c41adc54c308bb")return true;

        $sql = $vaild_sql ? $vaild_sql : "select id,role,prole,name from $tabel where token=?";

        $pdo = $vaild_pdo ? $vaild_pdo : laizhuanCon();

        $res = db_query_row($sql,null,array($token),$pdo);

        if(!$res) {
        	sreturn("请登录.");
        }else{
        	$_SESSION = $res;
        	//print_r($_SESSION);
        	return true;
        }

    }

function system_login($system){

	switch ($system) {
		//新后台
		case 'es2':
		case 'es2_new':
			$sql = "select id,objectId,role,name from bg_user where token = ?";


		break;
		//外放系统
		case 'offer':
			# code...
			break;

		default:
			# code...
			break;
	}

}


function laizhuanCon($db="laizhuan_task"){

	return ini_pdo($db,$ip="mysql56.rdsmwvd8scqn9l4.rds.bj.baidubce.com:3306",$user="laizhuan",$pwd="laizhuan");

}

function laizhuanReadCon($db="laizhuan"){

	return ini_pdo($db,$ip="read0.rdsm8yafbk2wp2r.rds.bj.baidubce.com:3306",$user="laizhuan",$pwd="laizhuan");

}

function locationCon($db="test_db"){

	return ini_pdo($db,$ip="localhost:3306",$user="root",$pwd="root");
}

function huitaotest($db="huitao"){

    return ini_pdo($db,$ip="192.168.1.151:3306",$user="huitao",$pwd="huitao");
}

function offerCon($db="taskofr"){
	return ini_pdo($db,$ip="taskofr.rdsm9ln50om7rva.rds.bj.baidubce.com:3306",$user="taskofr",$pwd="rdsm9ln50om7rva");
}


function jpLaizhuanCon($db="jpItem"){
	return ini_pdo($db,$ip="jpitem.cuepafz8wa6l.ap-northeast-1.rds.amazonaws.com:3306",$user="root",$pwd="hbwl123!#");
}

function shoppingCon($db="huitao"){
    return ini_pdo($db,$ip="taskofr.rdsm9ln50om7rva.rds.bj.baidubce.com:3306",$user="huitao",$pwd="huitao909886");
}



function db_execute($sql,$db="laizhuan",$bindParam=array(),$set_pdo=null){

		try{

			$pdo = $set_pdo ? $set_pdo : ini_pdo($db);

			$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_WARNING);
			//print_r($con);
			$stmt = $pdo->prepare($sql);
			//try()
			if(false===$stmt->execute($bindParam)){
				echo "Sql:".$sql;
				return false;
			}
			return $stmt->rowCount();

		}catch(PDOException $e){
			 echo "错误: ".$e->getMessage();
			 echo "\r\n\r\n";
			 echo "行号: ".$e->getLine();
			 echo "\r\n\r\n";
             return false;

		}


	}

	function db_insert($sql,$db="laizhuan",$bindParam=array(),$set_pdo=null){

		try{
			$pdo = $set_pdo ? $set_pdo : ini_pdo($db);

			$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_WARNING);

			$stmt = $pdo->prepare($sql);

			if($stmt->execute($bindParam))return $pdo->lastInsertId();

			else return 0;

		}catch(PDOException $e){
			 echo "错误: ".$e->getMessage()."\r\n";
			 echo "行号: ".$e->getLine()."\r\n";
             return false;
		}

	}


	//insert_arr = array("user_name"=>"xxx","bdid"=>3);
        //!!*这个insert的key必须是非常干净，否则会有多余的属性。
	function fetchInsertSql($table,$insert_array,$vaildAttr=false,$db="laizhuan_task"){

		 if(!is_array($insert_array))return false;

		//是否过滤insert_array
		if($vaildAttr){

			$insert_array = vaildAttr($table,$insert_array,$db);
		}

		$insert = "insert into $table(";

        $val = "";

        //根据值来判断要不要给值加''
        foreach ($insert_array as $key => $value) {

            if(is_numeric($value))

                $val .= $value.",";

            else $val .= "'".$value."',";

            $insert .= $key.",";

        }

        $sql = trim($insert,",").")values(".trim($val,",").")";

        return $sql;
	}

	function fetchQuerySql($table,$query_arrays=array(),$param_arrays=array(),$vaildAttr=false,$db="laizhuan_task"){

		 //if(!is_array($query_arrays)||!is_array($insert_arrays))return false;

       	$query = "select ";
    	//是否过滤insert_array
		if($vaildAttr){

			$query_arrays = vaildAttr($table,$query_arrays,$db);

			$param_arrays = vaildAttr($table,$param_arrays,$db);

		}
		//替换符的数量
		$reg_count = count($query_arrays);

		$reg_count += count($param_arrays);

		if(count($query_arrays)){
			foreach ($query_arrays as $key) {

				$query .= $key.",";


			}
		}else $query.=" * ";

		$insert_data = array();
		//插入数据部分

		$val = "";

        foreach ($insert_arrays as $k => $insert_array) {

        	if(!is_array($insert_arrays[0]))$insert_array = $insert_arrays;
			$val.="(";
	        //根据值来判断要不要给值加''
	        foreach ($insert_array as $key => $value) {

	       	    $val .= "?,";
	       	    //把所有值放入。
	       	    $insert_data[] = $value;

	        }

	        $val=trim($val,",")."),";
			//终止
			if(!is_array($insert_arrays[0]))break;
    	}

       	$sql = trim($insert,",").")values".trim($val,",");

        return array($sql,$insert_data);
	}

	//批量插入语句,单条也行
	//insert_key:插入数据的属性
	//insert_arrays:插入数据数组/2维
	function fetchInsertMoreSql($table,$insert_key=null,$insert_arrays,$vaildAttr=false,$db="laizhuan_task"){

       	if(!is_array($insert_key)||!is_array($insert_arrays))return false;

       	$insert = "insert into $table(";
    	//是否过滤insert_array
		if($vaildAttr){

			$insert_key = vaildAttr($table,$insert_key,$db);

		}
		//替换符的数量
		$reg_count = count($insert_key);

		if(!count($insert_key)){
			$insert_key = array_keys($insert_arrays);
			$insert_arrays = array_values($insert_arrays);
		}

		foreach ($insert_key as $key) {

			$insert .= $key.",";


		}

		$insert_data = array();
		//插入数据部分

		$val = "";
		//print_r($insert_arrays );
        foreach ($insert_arrays as $k => $insert_array) {

        	if(!is_array($insert_arrays[0]))$insert_array = $insert_arrays;
			$val.="(";
	        //根据值来判断要不要给值加''
	        foreach ($insert_array as $key => $value) {

	       	    $val .= "?,";
	       	    //把所有值放入。
	       	    $insert_data[] = $value;

	        }

	        $val=trim($val,",")."),";
			//终止
			if(!is_array($insert_arrays[0]))break;
    	}

       	$sql = trim($insert,",").")values".trim($val,",");

        return array($sql,$insert_data);
	}


	//$data key:value --> attr:value
	//$param key:value --> attr:value
	function db_update($table,$data_array,$param_array,$vaildAttr=false,$db="laizhuan_task",$set_pdo=null){

	  	list($sql,$bind_param) = fetchUpdateSql($table,$data_array,$param_array);

	  	if(!$sql)xreturn("sql错误。");

        $r = db_execute($sql,$db, $bind_param,$set_pdo);

        if($r===false)

            xreturn("修改失败。");

        else if($r===0)xreturn("修改无效。");

        return true;
	}


	//$data key:value --> attr:value
	//$param key:value --> attr:value
	function fetchUpdateSql($table,$date_array,$param_array,$vaildAttr=false,$db="laizhuan_task"){

		if(!is_array($date_array)||!is_array($param_array))return false;
		//强制验证
		if($vaildAttr){

			$date_array = vaildAttr($table,$date_array,$db);

			$param_array = vaildAttr($table,$param_array,$db);
		}

		//"UPDATE Person SET Address = 'Zhongshan 23', City = 'Nanjing' WHERE LastName = 'Wilson'"

		$sql = "update ".$table." set ";

		$data = "";

		$param = "";

		$bind_param = array();

		foreach ($date_array as $key => $value) {

			$data.=$key." = ?,";

			$bind_param[] = $value;
		}

		foreach ($param_array as $key => $value) {

			$param.=$key." = ? and ";

			$bind_param[] = $value;
		}

		$sql = $sql.trim($data,",")." where ".trim($param," and ");

		return array($sql,$bind_param);
	}
	//$data是准备作为插入数据的key=>value形式
   	function vaildAttr($table,$data,$db="laizhuan_task"){

        $result = db_query("SHOW COLUMNS FROM $table",$db);

        $attrs = array();

        foreach ($result as $key => $value) {
            //key是属性类型 值是名称
            ////如果需要可以根据这个key判断值是否需要添加''
            $attrs[$value["Field"]] = $value["Type"];

        }
        //print_r($attrs);
        if(!count($attrs))return $data;

        foreach ($data as $key => $value) {

            if(!array_key_exists($key,$attrs))

                unset($data[$key]);
        }
        //print_r($data);
        return $data;

    }

    function ini_pdo($db="laizhuan",$ip="taskofr.rdsm9ln50om7rva.rds.bj.baidubce.com:3306",$user="taskofr",$pwd="rdsm9ln50om7rva"){

    	list($host,$port) = explode(":",$ip);
		//echo $host;
		$dns = "mysql:host=$host;port=$port;dbname=$db";
		//echo $dns;
		$con = new PDO($dns, $user, $pwd);

		return $con;
    }


    //mysql56.rdsmwvd8scqn9l4.rds.bj.baidubce.com
    //180.76.161.253
    //set_pdo:设定一个pdo
	function db_query($sql,$db="laizhuan",$bindParam=array(),$set_pdo=null,$type="all"){

			$pdo = $set_pdo ? $set_pdo : ini_pdo($db);

			$stmt = $pdo->prepare($sql);

			$stmt->execute($bindParam);

			$data =array();

			switch($type){

				case "col" :

					while($row = $stmt->fetch(PDO::FETCH_NUM))  {
						//print_r($row);exit;
					   $data[] = $row[0];

					}
				break;

				case "all" :

					while($row = $stmt->fetch(PDO::FETCH_ASSOC))  {

					   $data[] = $row;

					}
					//print_r($data);
					//return $stmt->fetchAll();

				break;

				case "singal":
                    $data ="";
					while($row = $stmt->fetch(PDO::FETCH_NUM))  {

					   $data = $row[0];

					}

				break;

				case "row":

					while($row = $stmt->fetch(PDO::FETCH_ASSOC))  {

					   $data = $row;

					}

				break;

			}

			return $data;
		//}



	}

	function db_query_col($sql,$db="laizhuan",$bindParam=array(),$set_pdo=null){

		return db_query($sql,$db,$bindParam,$set_pdo,"col");

	}

	function db_query_singal($sql,$db="laizhuan",$bindParam=array(),$set_pdo=null){

		return db_query($sql,$db,$bindParam,$set_pdo,"singal");

	}

	function db_query_row($sql,$db="laizhuan",$bindParam=array(),$set_pdo=null){

		return db_query($sql,$db,$bindParam,$set_pdo,"row");

	}




	function covertToObj($data){
		print_r(json_encode($data));
		$t = json_encode($data);
		$o = json_decode($t);
		echo $o->idfa;
		exit;
	}


	function curl_req($url, $post_data=null,$info=null){
		//初始化一个 cURL 对象
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT,4);
		if($post_data){
			// 设置请求为post类型
			curl_setopt($ch, CURLOPT_POST, 1);
			// 添加post数据到请求中
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		}
		if(strpos($url,"https")>=0){
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在

		}
		$rsp = array();
		// 执行post请求，获得回复
		$content = curl_exec($ch);

		$infos = curl_getinfo($ch);

		if($info){
			if(!is_array($info))$info = array($info);
			foreach ($info as $k => $v) {

					$response[$v] = $infos[$v];

			}
			$response["content"] = $content;


		}else $response = $content;

		curl_close($ch);

		return $response;
	}

	function deal_data_format($data){

		$t = explode(".",$data);
		return trim(str_replace("{","",$t[0]));

	}


	function ckDate($date){

	    $is_date=strtotime($date)?strtotime($date):false;
	    //echo $is_date;exit;
	    return $is_date;

	}

/**
 * 跳转加提示 -- 成功跳转
 * @param string $text
 * @param string $url
 * @param number $time
 */
function success($text="操作成功", $url='', $time=2) {
	if(empty($url)) {
		$url = $_SERVER["HTTP_REFERER"];
	}
	echo "<br/><br/><br/><br/><br/><br/><br/><br/>
			<center><h1>".$text."</h1></center>";
	echo '<META HTTP-EQUIV="refresh" CONTENT="'.$time.'; URL='.$url.'">';
	exit;
}

function error($text="操作有误，请重新操作", $url='', $time=2) {
	if(empty($url)) {
		$url = $_SERVER["HTTP_REFERER"];
	}
	echo "<br/><br/><br/><br/><br/><br/><br/><br/>
			<center><h1>".$text."</h1></center>";
	echo '<META HTTP-EQUIV="refresh" CONTENT="'.$time.'; URL='.$url.'">';
	exit;
}

//外部参数和内部参数的映射转化
//mapping key=>value 请求的参数=>后台使用的参数
function request_filter($mapping=null,$part=1){

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

	if(is_array($mapping)&&count($mapping)){

		foreach ($mapping as $k => $v) {
			//请求里有这个值，不是数字索引
			if(isset($_REQUEST[$k])){

				$param[$v] = $_REQUEST[$k];

			}else if(is_numeric($k)&&isset($_REQUEST[$v]))

				$param[$v] = $_REQUEST[$v];

		}

		$request["data"] = $param;
		//print_r($request);
		//return $part?$request:$request["data"];

	}else {

		$request["data"] = $_REQUEST;
		//print_r($request);

	}

	return $part?$request["data"]:$request;
}

function loader_php( $file ){
    
    if(is_file($file)){

            require_once $file;
        }
}

//根据类名载入某个路径下的文件
function load_file_path($class,$module='',$base_path='/new'){

	if($module)

		$module = trim($module,"/")."/";

	$base_path = substr($_SERVER["DOCUMENT_URI"],0,strripos($_SERVER["DOCUMENT_URI"],"/"));
	//echo $_SERVER["DOCUMENT_URI"];echo $base_path;exit;

	if($base_path)

		$base_path = trim($base_path,"/")."/";


    $classFile=$_SERVER['DOCUMENT_ROOT']."/".$base_path.$module.$class.'.php';
    //echo $classFile;echo "<br>";
    //echo class_exists($class,false);echo "<br>";
    //echo $classFile;echo 2;

    $class=ucfirst($class);

    $classFile=$_SERVER['DOCUMENT_ROOT']."/".$base_path.$module.$class.'.php';

	if(is_file($classFile)) {
	//已经定义过这个类了
		if(!class_exists($class,false))

	    		include_once $classFile;

	    return true;
    }

	echo "Class ".$class." can't be loaded in ".$classFile;

    exit;
    	//return false;

}

function load_module_instance($class,$module='',$base_path='new',$agr=array()){

	load_file_path($class,$module,$base_path);

	$class = new ReflectionClass(ucfirst($class));

    return $class->newInstanceArgs($agr);
}

function load_module($module){
	
    $m = new Module($module);
    
    return $m->load_module($module);
}

/**
 * datatable的检索条件返回
 *
 * 			*//*
 * 			"page_size" => 10,
            "cur_page" => 2,
            "sort" => "id",
            "sort_dir" => "asc",
            "filter_key" => "name",
            "filter_val" => "am"
 */
function searchReturn(){
		$map = new stdClass();
		$map->limit = "";
		if(isset($_REQUEST['page_size']) && ($_REQUEST['page_size'] !== false) && isset($_REQUEST['cur_page']) && !empty($_REQUEST['cur_page'])) {
			$map->limit = " limit ".$_REQUEST['page_size'].','.$_REQUEST['cur_page']*$_REQUEST['page_size'];
		}else $map->limit = " limit 10";


		if(isset($_REQUEST['sEcho']) && !empty($_REQUEST['sEcho']))
			$map->sEcho = $_REQUEST['sEcho'];
		if(isset($_REQUEST['iTotalRecords']) && !empty($_REQUEST['iTotalRecords']))
			$map->iTotalRecords = 0;	//总数
// 		if(isset($_REQUEST['iTotalDisplayRecords']) && !empty($_REQUEST['iTotalDisplayRecords']))
// 			$map->iTotalDisplayRecords = 0;	// 按检索条件的总数
// 		$map->iTotalRecords = 100;		// 查询钱总数据

		$map->order = "";
		$map->field = "";
		$map->sort = "";
		if(isset($_REQUEST['sort'])&& !empty($_REQUEST['sort'])) {
			$map->field = $_REQUEST['sort'];
		}

		if(isset($_REQUEST['sort_dir']) && !empty($_REQUEST['sort_dir']))
			$map->sort = $_REQUEST['sort_dir'];
		if(!empty($map->field) && !empty($map->sort)){
			$map->order = " order by `".$map->field."` ".$map->sort;
		}

		$map->where = "";
		$map->searchType = "";
		$map->sSearch = "";
		if(isset($_REQUEST['filter_key']) && !empty($_REQUEST['filter_key']) && isset($_REQUEST['filter_val']) && !empty($_REQUEST['filter_val'])) {
			$map->searchType = $_REQUEST['filter_key'];
			$map->sSearch = addslashes(trim($_REQUEST['filter_val']));
			$map->where .= " where {$map->searchType} like '%{$map->sSearch}%' ";
		}
		if(isset($_REQUEST['role_id']) && !empty($_REQUEST['role_id'])){
			$map->searchType = $_REQUEST['role_id'];

		}


		return $map;
}


function beforeOuput($data,$mapping=array()){
	$oRes = $data;
	$res = json_decode($data,true);
	$addParam = array();
	if(!$res||(!isset($res["error"])&&!isset($res["aaData"]))){
		resReturn("模块参数错误.",2,$addParam);exit;
	}
	//print_r($oRes);//exit;
	$data = $res["aaData"];
	//echo 1;print_r($data);//exit;
	//
	//有些要保留的数据
	$add = array("total"=>"iTotalDisplayRecords","msg"=>"msg","status"=>"error");


	foreach ($add as $key => $value) {
		# code...
		if(isset($res[$value])){
			$addParam[$key] = $res[$value];
		}
	}

	if(count($mapping)>0){

		if(is_array($data)){

			foreach ($data as $key => $value) {

				if(is_array($value)){

					foreach ($value as $k => $v) {
						//echo "k,";
						if(array_key_exists($k, $mapping)){
							//echo 1;
							$data[$key][$mapping[$k]] = $v;

							unset($data[$key][$k]);
						}
					}
				}else{
					//一维数据
					foreach ($data as $key => $value) {

						if(array_key_exists($key, $mapping)){

							$data[$mapping[$key]] = $value;

							unset($data[$key]);
						}
					}
					return resReturn($data,1,$addParam);
				}

			}
			//数据是个简单值
		}
		//print_r($mapping);
		//print_r($data);exit;

	}
	return resReturn($data,1,$addParam);
}

function resReturn($data=array(),$t=2,$addParam){
	//echo "sdf";echo $data;
	//print_r($addParam);exit;
	$return = new stdclass();
	$return->status = $t;
	foreach ($addParam as $key => $value) {
		$return->$key = $value;
	}
	$return->data = $data;
	/*
	if(!empty($data)) {
		if($t == 2) {
			$return->data = array();
			$return->msg = $data;

		} else {
			$return->data = $data;
			$return->msg = "操作成功";
		}
	}else{
		if($t == 2) {
			$return->data = array();
			$return->msg = "暂无数据";
		}else{
			$return->data = array();
			$return->msg = "操作成功";
		}
	}*/
	//print_r($res);exit;
	//if(isset($res->iTotalDisplayRecords))$return->iTotalDisplayRecords= $res->iTotalDisplayRecords;
	$return->param = json_encode($_REQUEST);
	echo json_encode($return);
	exit;
}
//get的参数组成 由数组
function fix_get_pararm($array){
	$param='?';
	foreach ($array as $key => $value) {
		$param.=$key."=".$value."&";
	}
	return trim($param,"&");
}


 function console($log='')
  {
     switch (empty($log)) {
         case False:
              $out = json_encode($log);
               $GLOBALS['console']='';
              $GLOBALS['console'] .= 'console.log('.$out.');';
              break;

          default:
            echo '<script type="text/javascript">'.$GLOBALS['console'].'</script>';
     }
 }

 /**
 * model处理返回
 * @param string $data
 * @param number $t
 */
function mreturn($data=array(),$t=2){
	$return = new stdclass();
	$return->error = $t;
	if(!empty($data)) {
		if($t == 2) {
			$return->aaData = array();
			$return->msg = $data;
		}else{
			$return->aaData = $data;
			$return->msg = "操作成功";
		}
	}else{
		if($t == 2) {
			$return->aaData = array();
			$return->msg = $data;
		}else{
			$return->aaData = array();
			$return->msg = "操作成功";
		}
	}
	return $return;
}

function xreturn($data=array(),$t=2){
	echo json_encode(mreturn($data,$t));
	exit();
}

function sreturn($data=array(),$t=2,$isRt=0){
	$return = new stdclass();


	$return->status = $t;
    
	if(!empty($data)) {
		if($t == 2) {
			$return->data = array();
			$return->msg = $data;
		}else{
			if(is_object($data)){
					$data->status = $t;
					$return = $data;

			}else $return->data = $data;

			$return->msg = "操作成功";
		}
	}else{
		if($t == 2) {
			$return->data = array();
			$return->msg = $data;
		}else{
			$return->data = array();
			$return->msg = "操作成功";
		}
	}
    
	if($isRt)return json_encode($return);
	echo json_encode($return);
	exit();
}

function ssreturn($data,$msg='操作成功',$t=2,$isRt=1){

    $return = new stdClass; 
    $return->data = $data;
    $return->msg = $msg;
    $return->status = $t;
    //print_r($return);exit;
    if($isRt)return $return;
    echo json_encode($return);
    exit();
}


//$alias_mapping 针对某些属性需要别名
//$filter_mapping:_queryParam()方法使用 最后个参数作为 忽略字段的数组 既array(key=>value,key1=>value1,ingore_array=>array(key1))将不处理key1字段
//$hook_func:回调
//$hook_func_args:回调参数
function condition_param($params,$filter_mapping=null,$hook_func=null,$hook_func_args=null){


   	$params = _queryParam($params,$filter_mapping);

   	//print_r($params);

   	$op_arr = array(":limit",":offset",":sort",":sort_type",":like",":not_null");

   	$conditions=" 1=1 and ";

   	$bind_param = array();

   	$limit="";

        $order_by="";
    //print_r($params);//exit;
    //有参数 没参数默认全部
    if(count($params)){

        foreach ($params as $key => $value) {

        	//if(count($alias_mapping)&&in_array($value,$alias_mapping))

        	//	$value = $alias_mapping[$value].".".$value;

        	if(null!==$hook_func)

        		$value = call_user_func_array($hook_func, array($value,$key,$hook_func_args));

            if(in_array($key,$op_arr)){

                switch ($key) {

                    case ':limit':
                    	$offset = $value * ($params[":offset"]-1) >= 0 ? $value * ($params[":offset"]-1) : 0;
                        $limit = " limit " . $value . (isset($params[":offset"]) ? " offset ". $offset : "");
                    break;

                    case ':sort':
                        //$order_by = " order by " . $value . (isset($params[":sort_type"]) ? " ".$params[":sort_type"] : "");

                        $order_by = " order by $value " . (isset($params[":sort_type"]) ? " ".$params[":sort_type"] : "");

                       // $bind_param[] = $value;
                    break;

                    case ':like':
                        if(is_array($value)){
                        									// array(k=>$v)
                            foreach ($value as $k => $v) { //array(array($k=>$v),array($k1=>$v1))

                            	if(is_array($v)){

                            		if(count($v)){
	                            		foreach ($v as $k1 => $v1) {

	                            			$conditions .= "$k1 like ? and ";

	                                		$bind_param[] = "%".$v1."%";

	                            		}
                            		}

                            	}else {

                                	$conditions .= "$k like ? and ";

                                	$bind_param[] = "%".$v."%";
                            	}
                            }
                        }
                    break;

                    case ':not_null':
                        $conditions .= " $value is not null and ";
                    break;

                    case ':between'://array(":between"=>array("key"=>array(a,b)));

                    	if(is_array($value)){

                            foreach ($value as $k => $v) {

                                $conditions .= "$k between ? and ? and ";

                                $bind_param[] = $v[0];

                                $bind_param[] = $v[1];
                            }
                        }

                    break;

                    default:

                    break;
                }

            }else  {

            	//$conditions .= $key." = ? and ";
            	$conditions .= $key." = ? and ";

            	$bind_param[] = $value;

        	}
        }



    }

    $conditions = rtrim($conditions," and ");

    return array($conditions,$bind_param, $order_by . $limit);
}
	//有些value，可能需要加别名，需要特殊处理//"filter_key"=>array("alias"=>array("attr1","attr2"),
	//$filter_mapping : array(
	//				"$key" = 0 - 表示不过滤，array() - 表示用这个数组替代全局的"alias"=>array("attr3","att4")
	//				"alias"=>array("attr3","att4")
	//			)
	function _filter_mapping($filter_mapping,$value,$key=null){
		//print_r($filter_mapping);echo $key;
		if(!is_array($filter_mapping)||!count($filter_mapping))return $value;
		//存在某个属性
		if($key&&isset($filter_mapping[$key])){
			//替代全局的"alias"
			//print_r($filter_mapping[$key]);
			if(is_array($filter_mapping[$key]))

				$filter_mapping = $filter_mapping[$key];

			else return $value;

		}

		foreach ($filter_mapping as $k => $v) {

			if(in_array($value,$v))return $k.".".$value;

		}

		return $value;

	}

	function _queryParam($params,$filter_mapping=null){

		if(isset($filter_mapping["ingore_array"])){

			$ingore_array = $filter_mapping["ingore_array"];

			unset($filter_mapping["ingore_array"]);

		}

		else $ingore_array = array();

			//print_r($params);
			foreach ($params as $key => $value) {
					//echo $key.",";
	            //print_r($params);
	           	switch ($key) {

		        case "page_size" :

		            if(isset($params["page_size"])){

		            	if(!empty($params["page_size"]))

		                $params[":limit"] = $params["page_size"];

		            	else  $params[":limit"] = 10;

		                unset($params["page_size"]);

		                continue;
		            }
	            break;

	            case "cur_page" :
	            if(isset($params["cur_page"])){

	            	if(!empty($params["cur_page"]))

	                $params[":offset"] = $params["cur_page"];

	            	else $params[":offset"] = 0;

	                unset($params["cur_page"]);

	                continue;
	            }
	            break;

	            case "sort" :
	            if(isset($params["sort"])){

	            	if(!empty($params["sort"]))

	                $params[":sort"] = _filter_mapping($filter_mapping,$params["sort"],"sort");

	                unset($params["sort"]);

	                continue;
	            }
	            break;
	            case "sort_dir" :
	            if(isset($params["sort_dir"])){

	            	if(!empty($params["sort_dir"]))
	                	$params[":sort_type"] = $params["sort_dir"];

	                unset($params["sort_dir"]);

	                continue;
	            }
	            break;
	            case "filter_key":
	            if(isset($params["filter_key"])||isset($params["filter_val"])){

	            	if(!empty($params["filter_key"])&&!empty($params["filter_val"])){

	            		$tkey = _filter_mapping($filter_mapping,$params["filter_key"],"filter_key");

	                	$params[":like"][$tkey] = $params["filter_val"];
	                }
	                unset($params["filter_key"]);

	                unset($params["filter_val"]);

	                continue;
	            }
	            break;
	           	default :

	            if(!in_array($key,array("page_size","cur_page","sort","sort_dir","filter_key","filter_val"))){
	            	//某些需要忽略的参数
	            	//ingore_array

	            	if(count($ingore_array)&&in_array($key,$ingore_array)){

	            		unset($params[$key]);
	            		continue;
	            	}
	            	$new_key = _filter_mapping($filter_mapping,$key);
	            	$params[$new_key] = $params[$key];
	            	if($new_key!=$key)unset($params[$key]);
	            }

	            }

            }
           //print_r($params);
            return $params;

        }


    //断点操作多次数据库操作&记录日志
    class TransactionTools{

        //记录方式
        //① 文件记录
        //② 内存记录
        public $record_type;
         //记录方式最后一行：
            // A：100%
            // B:

        public $TxtFileName = "record.log.txt";

        public $TxtRes;

        public $cut_line;
        //默认的标识成功的内容
        protected $success_sign = array("Work End.","100%");

        //public $writeRecordSign;

        public function __construct(){
            //要创建的两个文件
            //$TxtFileName = "record.log.txt";
            //以读写方式打写指定文件，如果文件不存则创建
            if( ($this->TxtRes=fopen ($this->TxtFileName,"a+")) === FALSE){
                echo("创建可写文件：".$this->TxtFileName."失败");
                exit();
            }
            
            $os = strtoupper(substr(PHP_OS,0,3))==='WIN'?1:0;
            //根据os的换行符
            $this->cut_line = $os ? "\r\n" : "\n"; 
            /*
            $this->writeRecord();

            $this->beginMission("vv.kj8323n");

            //$this->endMission();

           // $this->endRecord();

            $res = $this->listenProcess(":","Work End.");
            print_r($this->FileLastLines($this->TxtFileName,4));
            print_r($this->getLastLines($this->TxtFileName,4));
            */
           
            //return $res;

        }
        
        public function __destruct(){
            fclose ($this->TxtRes); //关闭指针
        }
        //监听工作流程
        //@param sign:追加标识成功的内容，抓取的内容
        //@param sign:标识回滚点内容，分割的依据
        //@param : 回滚内容
        public function listenProcess($rollback_con,$rollback_sign=':',$add_success_sign=array()){
            
            $c = $this->FileLastLines($this->TxtFileName,10);

            $last_line = '';
            //可能有空行，过滤10条，第一个不是空行的内容作为第一行
            foreach ($c as $line) {
                
                if(trim($line)){
                    //echo $line.",";
                    $last_line=$line;break;
                }
            }
            //echo $last_line;
            //没读到最后行
            if(''===$last_line)return -3;

            if(!is_array($add_success_sign))$add_success_sign = array($add_success_sign);
            //追加的值
            $success_sign  = array_merge($this->success_sign,$add_success_sign);

            if(!is_array($success_sign))return ssreturn("",$msg='TransactionTools success_sign format wrong.',2,1) ;
            //没问题（结束成功sign）
            //c[0]的值，就已经是一个成功标记，跳过
            
            if(in_array(trim($last_line),$success_sign))return 0;

            else { 
                //var_dump(strpos($c[0],$rollback_sign));
                //找不到回滚标志
                if(strpos($last_line,$rollback_sign)===false)return -2;
               
                $vals = explode($rollback_sign,$last_line);
                //!!*成功与否的结果必须在数组最后一个元素中
                $end = count($vals) - 1;
                //回滚内容标记
                $sign = 0;
                //是否有成功匹配
                $success = 0;
                //2017-03-22:mission_a:value_b
                //长度为>1，取最后个sign=100%,也没问题（任务成功sign）
                foreach ($vals as $k => $v) {
                    //找到了成功标记，返回0
                    if(in_array(trim($v),$success_sign))return 0;
                    //找到这个回滚的内容,内容有存在回滚内容中。
                    if(is_array($rollback_con)&&in_array(trim($v),$rollback_con))$sign = $v;
                    //echo $v;
                    //print_r($rollback_con);
                }
                //执行到这，看返回回滚内容/还是没结果。
                if($sign)
                    
                    return $sign;
                
                else 

                    return -1;
                
            }

           
           
           
        }

        //记录数据
        public function writeRecord($s = ''){



            //检查是不是已经有过今天的开始记录，有就不写了。
            //读文件获取。
            //还是分零食文件和日志文件记录
            //sldfsdjf();
            if($s)$str = $s;

            else $str = date("Y-m-d")." 's Work:".$this->cut_line;

            $r = $this->ckContentExist($str,$this->TxtFileName);

            if(!$r)

                fwrite($this->TxtRes,$str);

        }
        //开始任务
        public function beginMission($m){

            $str = $this->cut_line.date("H:i:s")." - start:". $m.":";

            //$str = $str . $m.":";

            fwrite($this->TxtRes,$str);
        }
        //结束任务
        public function endMission(){

            $str = "100%".$this->cut_line;

            fwrite($this->TxtRes,$str);
        }
        //记录没有辨识号的任务日志
        public function addMissionLog($m){

            $r = $this->ckContentExist($m,$this->TxtFileName);

            if(!$r){

                $this->beginMission($m);

                $this->endMission();
            }
        }

        public function addErrorLog($e){

            $str = $this->cut_line.date("H:i:s")." - error ".$e.$this->cut_line;

            fwrite($this->TxtRes,$str);
        }

        //记录没有辨识号的任务日志
        public function addLog($c){

            $r = $this->ckContentExist($c,$this->TxtFileName);

            if(!$r){

                $str = $this->cut_line.$c.$this->cut_line;

                fwrite($this->TxtRes,$str);
            }
        }

        //结束数据
        public function endRecord(){

            //$start = date("Y-m-d H:i:s")."start:/n"

            $str = $this->cut_line."Work End.";

            fwrite($this->TxtRes,$str);

        }
        /*
         * 取文件最后$n行
         * @param string $filename 文件路径
         * @param int $n 最后几行
         * @return mixed false表示有错误，成功则返回字符串
         */
        public function FileLastLines($filename,$n=1){
            
            $filename = "record.log.txt";
            if(!$fp=fopen($filename,'r')){
                echo "打开文件失败，请检查文件路径是否正确，路径和文件名不要包含中文";
                return false;
            }
            $pos=-2;
            $eof="";
            $str_arr=array();
            while($n>0){
                while($eof!="\n"){
                    if(!fseek($fp,$pos,SEEK_END)){
                        $eof=fgetc($fp);
                        $pos--;
                    }else{
                        break;
                    }
            }
                $str_arr[]=fgets($fp);
                $eof="";
                $n--;
          }
          return $str_arr;
           
        }

        //通用方法 小内容文件
        public function getLastLines($filename,$n){
            
            $file = file($filename);
            
            $i=0;$t = array();
            //print_r($file);exit;
            foreach(array_reverse($file) as $k=>$line){

                if($i>=$n)break;
                    
                if($line){
                    //array_unshift($t,$line);
                    $t[] = $line; $i++;                        
                }
            }
            return $t;
        }
    
        //检查这段内容存在情况，是否重复写入，存在1，不存在0
        //@content:内容
        public function ckContentExist($content,$filename){

            $content = trim($content);
            
            $lines = file($filename);
            //print_r($lines);
            foreach ($lines as $key => $value) {
                
                if(trim($value)&&strpos(trim($value),$content)!==false){//
                    //var_dump(strpos($value,$content));exit;
                    //var_dump(trim($value));echo "<br>";
                   // var_dump(strpos("21","1"));echo "<br>";
                    //echo $value.":".$content."-".strpos(trim($value),$content)."<br>";
                    return true;

                }
            }

            return false;
        }


    }