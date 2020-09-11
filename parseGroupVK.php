<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />

<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="description" content="" />

<title>Парсер групп Вконтакте</title>
</head>
<body>
<form action="parseGroupVK.php" method="POST" name="parseVK">
<p><label>ID группы:</label><input name="group_id" value="<?=$_REQUEST['group_id']?>"></input></p>
<p><label>Кол-во записей:</label><input name="count" value="<?=$_REQUEST['count']?>"></input></p>
<p><label>Только одну картинку:</label><input type="checkbox" checked name="one_pic" value="<?=$_REQUEST['one_pic']?>"></input></p>
<p><label>Только с текстом:</label><input type="checkbox" checked name="issetText" value="<?=$_REQUEST['issetText']?>"></input></p>
<p><strong>По умолчанию группа VPOVARE, количество записей 10.</strong></p>
<input type="submit" value="Погнали!">
</form>

<?php
//include_once('setup.php');

if (!isset($_REQUEST['count'])or($_REQUEST['count']=="")){ // по умолчанию 10 записей берём
	$count = 10;
}else{
	$count = $_REQUEST['count'];
}
if (!isset($_REQUEST['group_id'])or($_REQUEST['group_id']=="")){ // по умолчанию берём группу vpovare
	$group_id = 18464856;
}else{
	$group_id = $_REQUEST['group_id']+0;
}

//ini_set("display_errors",1);
//error_reporting(E_ALL);
$ch = file_get_contents("http://api.vk.com/method/wall.get?owner_id=-$group_id&count=$count");
// id VPOVARE - 18464856
// id my_group - 18865139
$result = json_decode($ch);

foreach ($result->response as $art_info) 
{
	if (isset($_REQUEST['issetText'])){
		if (!$art_info->text<>''){
			continue;
		}
	}
	//echo "<br />".$art_info->id;
	echo "<br /><span><strong>".$art_info->attachment->photo->src_big."</strong><br />"; // URL картинок
	echo "<br />";
	echo "<img src='".$art_info->attachment->photo->src_big."'/><br />"; // URL картинок
	echo $art_info->text.'<br />';
	if (!isset($_REQUEST['one_pic'])){
	foreach ($art_info->attachments as $image){
		if ($image->type=="photo"){
			echo "<br />".$image->photo->src_big;
		}
	}}
	echo "</span><br />========================================<br />";
}
/*
foreach ($result->response as $art_info){
	if((is_int($art_info->id))and (check_id($art_info->id)) and (isset($art_info->id))){
		if (isset($arr_img)){
			unset($arr_img);
		}
		$arr_img = array();
		foreach ($art_info->attachments as $image){
			if ($image->type=="photo"){
				$arr_img[] = $image->photo->src_big;
			}
		}
		insert2db($art_info->id,$art_info->attachment->photo->src_big,$art_info->text,$arr_img);
	}
}
*/
//var_dump($result);
//print $result->{'text'};
?>
</body>
</html>