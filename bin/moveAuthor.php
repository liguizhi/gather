<?PHP
$shard = $argv[1];
if($shard == 105) {
	$s = 1041;
	$e = 1042;
} else {
	$s = ($shard-1)*10 + 1;
	$e = $s + 10;
}
// switch ($shard) {
//     case 1:
//         $s = 1;
//         $e = 100;
//         break;
//     case 2:
//         $s = 101;
//         $e = 200;
//         break;
//     case 3:
//         $s = 201;
//         $e = 300;
//         break;
//     case 4:
//         $s = 301;
//         $e = 400;
//         break;
//     case 5:
//         $s = 401;
//         $e = 500;
//         break;
//     case 6:
//         $s = 501;
//         $e = 600;
//         break;
//     case 7:
//         $s = 601;
//         $e = 700;
//         break;
//     case 8:
//         $s = 701;
//         $e = 800;
//         break;
//     case 9:
//         $s = 801;
//         $e = 900;
//         break;
//     case 10:
//         $s = 901;
//         $e = 1000;
//         break;
//     case 11:
//         $s = 1001;
//         $e = 1040;
//         break;
//     default:
//         break;
// }
for($i=$s; $i<$e; $i++){
	$newList = array();
	$dbo = new PDO('mysql:dbname=college;charset=utf8;host=localhost', 'root', 'root');
	$begin = 1000*($i-1) + 221559;
	$end = $begin + 1000;
	$sql = "SELECT a.authorId, a.authorName, a.authorNamePinyin, a.authorUrl,d.image,d.details from authors_bak as a, authorsDesc_bak as d WHERE a.authorId=d.authorId and (a.authorId >= $begin) and (a.authorId < $end)";
	echo $sql;
	$res = $dbo->query($sql);
	$list = $res->fetchAll(PDO::FETCH_ASSOC);
	if($list) {
		foreach ($list as $key => $value) {
			$newList[$value['authorId']] = $value;
		}
		$idArray = array_keys($newList);
		$inStr = join(',',$idArray);
	}
	$descListSql = "SELECT d.image,d.details from authorsDesc_bak as d where d.authorId in($inStr)";
	$res = $dbo->query($descListSql);
	$descList = $res->fetchAll(PDO::FETCH_ASSOC);
	if($descList) {
		foreach ($list as $key => $value) {
			$newList[$value['authorId']]['image'] = $value['image'];
			$newList[$value['authorId']]['details'] = $value['details'];
		}
	}
	// var_dump($newList);exit;
	foreach ($newList as $key => $authorInfo) {
		$newAuthor = parseAuthor($authorInfo);
		if(isset($newAuthor['authorsName']) && $newAuthor['authorsName']){
			$data['image']            = $authorInfo['image'];
			$data['authorNamePinyin'] = $authorInfo['authorNamePinyin'];
			$data['authorUrl']        = $authorInfo['authorUrl'];
			$data['authorName']       = $newAuthor['authorsName'];
			$data['collegeName']      = $newAuthor['collegeName'];
			$data['departmentName']   = $newAuthor['departmentName'];
			$data['details']          = $newAuthor['details'];
			saveData($data);
			$data['authorId'] = $authorInfo['authorId'];
			writeSuccLog($data, $shard);
		}
	}
	// exit;
}


function saveData($data) {
	$pdo = new PDO('mysql:dbname=college;charset=utf8;host=localhost', 'root', 'root');
	$authorData = array(
		':authorName'       => $data['authorName'],
		':authorNamePinyin' => $data['authorNamePinyin'],
		':authorUrl'        => $data['authorUrl'],
		':collegeName'      => $data['collegeName'],
		':departmentName'   => $data['departmentName'],
		);
	$authorDesc = array(
		':image' => $data['image'],
		':details' => $data['details'],
		);
	$pdo->beginTransaction();
	try {
		// $authorSql = '';
		$authorSql = "insert into authors (authorName,authorNamePinyin, authorUrl,collegeName,departmentName) "
						." values (:authorName,:authorNamePinyin,:authorUrl,:collegeName,:departmentName)";
		$stm = $pdo->prepare($authorSql);
		$stm->execute($authorData);
		$authorId = $pdo->lastInsertId();
		if($authorId) {
			$authorDesc[':authorId'] = $authorId;
		}
		$descSql = 'insert into authorsDesc (authorId, image, details)'
					." values (:authorId,:image,:details)";
		$stm = $pdo->prepare($descSql);
		$flag = $stm->execute($authorDesc);
		if($flag) {
			$pdo->commit();
		}
		else {
			$pdo->rollback();
		}
	} catch (PDOException  $e) {
		$pdo->rollback();
        $info = $e->getMessage();
        writeExcepLog($info);
	}
	$pdo=null;
	return true;
}
function writeSuccLog($info, $shard) {
	file_put_contents('/phpStudy/WWW/myGather/college/log/success'.$shard.'.log', date('Y-m-d H:i:s').$info['authorId'].":".$info['authorName'].":".$info['collegeName']."\r\n",FILE_APPEND);
}

function writeExcepLog($info){
	file_put_contents('/phpStudy/WWW/myGather/college/log/exception.log', date('Y-m-d H:i:s').$info."\r\n",FILE_APPEND);
}
function parseAuthor($authorInfo) {
	$newInfo = array();
	$orgDetail = isset($authorInfo['details']) ? $authorInfo['details'] : '';
	if($orgDetail) {
		$part = explode('；状态：未知.', $orgDetail);
		if(count($part) > 1){
			$head = $part[0];
			$newInfo['details'] = strip_tags($part[1]);
		} else {
			$part = explode('；状态：在职.', $orgDetail);
			if(count($part) > 1) {
				$head = $part[0];
				$newInfo['details'] = strip_tags($part[1]);
			} else {
				$head = $part[0];
			}
		}
		$newInfo = parseHead($head);
		$newInfo['details'] = isset($part[1]) ? strip_tags($part[1]) : '';
	}
	return $newInfo;
}

function parseHead($head) {
	$info = array(
		'authorsName' => '',
		'collegeName' => '',
		'departmentName' => '',
		);
	$headArray = explode('；', $head);
	if($headArray){
		foreach ($headArray as $key => $value) {
			$item = explode('：', $value);
			switch ($item[0]) {
				case '姓名':
					# code...
					$info['authorsName'] = $item[1];
					break;
				case '大学':
					# code...
					$info['collegeName'] = $item[1];
					break;
				case '院系':
					$info['departmentName'] = $item[1];
					break;
				default:
					# code...
					break;
			}
		}
	}
	return $info;
}
// strip_tags(str);

/**
CREATE TABLE  IF NOT EXISTS `authors` (
  `authorId` int(11) NOT NULL AUTO_INCREMENT,
  `authorName` varchar(100) NOT NULL DEFAULT '' COMMENT '作者名',
  `authorNamePinyin` varchar(255) DEFAULT NULL COMMENT '作者名字的拼音',
  `authorUrl` varchar(255) NOT NULL DEFAULT '' COMMENT '作者url',
  `collegeName` varchar(100) NOT NULL DEFAULT '' COMMENT '大学名',
  `departmentName` varchar(100) NOT NULL DEFAULT '' COMMENT '院系名称',
  `isDelete` tinyint(1) DEFAULT '0' COMMENT '是否删除：0：否 1：删除',
  PRIMARY KEY (`authorId`),
  KEY `authorUrl` (`authorUrl`),
  KEY `authorName` (`authorName`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `authorsDesc` (
  `authorId` int(11) NOT NULL,
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `details` longtext COMMENT '详细介绍',
  PRIMARY KEY (`authorId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='作者详细信息表' 

*/