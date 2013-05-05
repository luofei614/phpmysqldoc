<?php

/**
 * 生成数据库文档，
 * @param Mysqli $mysqli
 * @param String/Array<String> $dbName 数据库实例的名
 */
function pmd_generateDoc($mysqli, $dbName) {
	$info = '你需要能够直视Markdown的浏览器，推荐用Chrome的扩展Markdown Reader（带目录功能）';
	$author = 'php script wrote by Cupen<Cupenoruler@foxmail.com>';
	$date = date('Y-m-d H:i:s');
	$strBuilder = pmd_generateDocHead($info, $author, $date);
	$strBuilder .= pmd_generateDocBody($mysqli, $dbName);
	return $strBuilder;
}

/**
 * 生成文档头，
 * @param String $instr 文档简介
 * @param String $author 文档作者署名
 * @param String $dateStr 文档生成日期
 * @return String
 */
function pmd_generateDocHead($instr, $author, $dateStr) {
	$strBuilder = markdownLine(htmlspecialchars("@instruction $instr"));
	$strBuilder .= markdownLine(htmlspecialchars("@author $author"));
	$strBuilder .= markdownLine(htmlspecialchars("@date $dateStr"));
	$strBuilder .= markdownLine(htmlspecialchars("- - -"));
	return $strBuilder;
}

/**
 * 生成文档内容，$dbName为数据库的实例名
 */
function pmd_generateDocBody($mysqli, $dbName) {
	$strBuilder = '';
	if(!is_array($dbName)){
		$dbName = array($dbName);
	}
	// working
	foreach ($dbName as $tmpDbName) {
		$strBuilder .= pmd_generateMdByDatabase($mysqli, $tmpDbName );
	}
	return $strBuilder;
}

/**
 * 依数据库的注释生成Markdown文本
 */
function pmd_generateMdByDatabase($mysqli, $dbName ){
	$strBuilder = markdownLine("# ".$dbName);
	$tablesInfoArr = pmd_getTableInfo($mysqli, $dbName);
	foreach ($tablesInfoArr as $tabName => $tabComment) {
		$tabComment = $tabComment ? $tabComment : 'havn\'t comment';
		$strBuilder .= markdownLine("## $tabName -- $tabComment",true );

		$fieldInfoArr = pmd_getFieldsInfo($mysqli, $dbName, $tabName);
		// var_dump($fieldInfoArr);
		$strBuilder .= pmd_formatFieldInfoAsHTML($fieldInfoArr);
	}
	return $strBuilder;
}

/**
 * 使用mysqli扩展获取连接
 * 见 http://www.php.net/manual/zh/class.mysqli.php
 * @return Mysqli
 */
function pmd_getMysqli($host,$port, $user, $password, $database = 'information_schema') {
	$mysqli = mysqli_connect($host, $user, $password, $database, $port);
	$errStr = mysqli_connect_error();
	if ($errStr) {
		die('Connect error '.mysqli_connect_errno().": $errStr");
	} else {
		return $mysqli;
	}
}

/**
 * 获取数据库$dbName的表信息（表名，表注释)
 */
function pmd_getTableInfo($mysqli, $dbName){
	$result = array();
	$sql = "Select TABLE_NAME, TABLE_COMMENT From `information_schema`.`TABLES` Where table_schema = '$dbName'";
	$queryRS = mysqli_query($mysqli, $sql);
	while($row = mysqli_fetch_assoc($queryRS)) {
		$result[$row['TABLE_NAME']] = $row['TABLE_COMMENT'];
	}
	return $result;
}

/**
 * 获取数据库$dbName表$tabName的字段信息（字段名，字段类型，字段注释)
 */
function pmd_getFieldsInfo($mysqli, $dbName, $tabName) {
	$result = array();
	$sql = "Select
			COLUMN_NAME,
			DATA_TYPE,
			COLUMN_COMMENT
		From
			`information_schema`.`COLUMNS`
		Where
			table_schema = '$dbName'
			And table_name ='$tabName'";
	
	$queryRS = mysqli_query($mysqli, $sql);
	while( $row = mysqli_fetch_assoc($queryRS) ) {
		$tmp = (object)array();
		$tmp->Name = $row['COLUMN_NAME'];
		$tmp->Type = $row['DATA_TYPE'];
		$tmp->Comment = $row['COLUMN_COMMENT'];

		$result[] = $tmp;
	}
	return $result;
}


/**
 * 格式化到table标签的HTML片段
 */
function pmd_formatFieldInfoAsHTML($fieldInfoArr){
	$strBuilder = '<table>';
	foreach ($fieldInfoArr as $tmpfieldInfo) {
		$tmpfieldInfo->Comment = htmlentities($tmpfieldInfo->Comment);
		$tmpfieldInfo->Comment = str_replace("\n", "<br/>", $tmpfieldInfo->Comment);
		$tmpfieldInfo->Comment = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $tmpfieldInfo->Comment);
		$tmpfieldInfo->Comment = str_replace(" ", "&nbsp;", $tmpfieldInfo->Comment);
		
		// echo $tmpfieldInfo->Comment."\n<br/>";
		// $tmpfieldInfo->Comment = "<code>$tmpfieldInfo->Comment<code/>";
		// 
		$formatStr = "<tr>";
		$formatStr .= sprintf("<td>%s</td> <td>%s</td> <td>%s</td>",$tmpfieldInfo->Name, $tmpfieldInfo->Type, $tmpfieldInfo->Comment);
		$formatStr .= "</tr>";

		$strBuilder .= markdownLine("$formatStr");
	}
	$strBuilder .= markdownLine('</table>');
	return $strBuilder;
}

function writeToFile($text, $filePath = "phpmysqldoc.md"){
	$file = fopen($filePath, "w");
	fwrite($file, $text);
	fclose($file);
}

/**
 * 给这行文字加上Markdwon的行结尾表示
 */
function markdownLine($str, $ifNeedEscape = false){
	$ifNeedEscape && $str = markdownStr($str);
	return $str."  \n";
}

function markdownStr($str){
	return str_replace('_', '\_', $str);
}
?>
