<?php
$host = 'mysql:host=jpitem.cuepafz8wa6l.ap-northeast-1.rds.amazonaws.com;dbname=shopping';
$userdb = 'root';
$passdb = 'hbwl123!#';
// $host = 'mysql:host=127.0.0.1;dbname=laitin';
// $userdb = 'root';
// $passdb = '';
try {
	$conn = null;
	$conn = new PDO($host,$userdb,$passdb);
	$conn->exec('SET NAMES UTF8');
	echo "Connected\n";
} catch (Exception $e) {
	die("Unable to connect: " . $e->getMessage());
}

try {
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$conn->beginTransaction();
	//查出uid_log中所有 当前天数-7天内的 状态是预估的收入记录
	$list = $conn->query("SELECT id,uid,price FROM gw_uid_log WHERE createdAt < date_sub(curdate(),interval 7 day) AND status = 1")->fetchAll(PDO::FETCH_ASSOC);
	if(empty($list)) die;
	//遍历结果
	//1.将查到的所有id放入一个变量中用于更新uid_log的状态,
	//2.拼接所有用户objectId 和对应的预收入金额,update到uid中对应的记录
	$str = '';
	$str2 = '';
	// D($list);die;
	$temp = [];
	foreach ($list as $kk => $vv) {
		$id_lists[] = "'{$vv['id']}'";
		if(array_key_exists($vv['uid'],$temp)){
			$temp[$vv['uid']] = $temp[$vv['uid']] + $vv['price'];
		}else{
			$temp[$vv['uid']] = $vv['price'];
		}
	}
	// D($temp);die;
	foreach ($temp as $k => $v) {
		$str .= " WHEN '{$k}' THEN {$temp[$k]} ";
		$str2 .= "('{$k}','亲您有一笔预估收入已转到余额'),";
	}
	$id_list = implode(',',$id_lists);
	$str2 = rtrim($str2,',');
	//更新条件内uid_log状态
	//根据id,update 每个用户的price
	//添加消息
	$sql1 = "UPDATE gw_uid_log
				SET status = 2 WHERE id IN ($id_list)";
	$sql2 = "UPDATE gw_uid
				SET price = price + CASE objectId".
					$str
			."ELSE 0 END";
	$sql3 = "INSERT INTO gw_message (uid,content)
				VALUES $str2";
	// echo $sql3;die;
	$conn->exec($sql1);
	$conn->exec($sql2);
	$conn->exec($sql3);
	$conn->commit();
	$conn = null;
} catch (PDOException $e) {
	$conn->rollBack();
	// $conn = null;
	print "Error!: " . $e->getMessage() . "<br/>";
	die();
}

function D($arr=[])
{
	echo '<pre>';
	print_r($arr);
}
?>
