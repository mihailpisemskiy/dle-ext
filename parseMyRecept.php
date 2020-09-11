<?php
ini_set("display_errors",1);
error_reporting(E_ALL);

mysql_connect('HOSTNAME','HOSTUSERNAME','HOSTPASSWORD');
mysql_select_db('DBNAME');

$author = "authorName"; //автор новости
$category_robots = '2,77'; //категория новости автодобавления
$group_id = 30187757;
$count = 50;
$_REQUEST['issetText'] = true;

function check_id($id){ // проверка на уникальность ID
	$query = mysql_query("SELECT * FROM groupvkMyRecept WHERE id='$id'");
	//echo mysql_num_rows($query);
	if (mysql_num_rows($query)>0){
		return false;
	}else{
		return true;
	}
}
function insert2db($id,$img_anons,$text,$img_attach,$title){ //добавляем в базу запись
	$img_attach = implode(",", $img_attach);
	$query = mysql_query("INSERT INTO groupvkMyRecept (id,img_anons,text,img_attach,zagolovok) VALUES ($id,'$img_anons','$text','$img_attach','$title')");
}

function uploadImg($url, $nameImg){
//if (!file_put_contents('./public_html'.$nameImg, file_get_contents($url))){
if (!file_put_contents('./public_html'.$nameImg, file_get_contents($url))){
	echo "<p>Изображение не загрузилось.</p>";
}
}

function generateNameImg(){
	$nameImg='/images/30187757/'.time().'-'.rand(10000,1000000).'.jpg';
	return $nameImg;
}

function cutTitle($text){
	$pieces = explode("<br>", $text);
	//var_dump($pieces[0]);
	return $pieces[0];
}

$ch = file_get_contents("http://api.vk.com/method/wall.get?owner_id=-$group_id&count=$count");
$result = json_decode($ch);
foreach ($result->response as $art_info){
	if((is_int($art_info->id))and (check_id($art_info->id)) and (isset($art_info->id))){ //Антидубль
		if (isset($_REQUEST['issetText'])){ //Брать посты, в которых есть текст
			if (!$art_info->text<>''){
				continue;
			}
		}
		//Очищаем массив картинок
		if (isset($arr_img)){
			unset($arr_img);
		}
		$arr_img = array();
		//Цикл по всем картинка поста
		foreach ($art_info->attachments as $image){
			if ($image->type=="photo"){ //тип Фото
				$nameImg = generateNameImg(); //генерируем уникальное имя картинки
				uploadImg($image->photo->src_big,$nameImg); //загружаем картинку к себе
				$arr_img[] = $nameImg; //добавляем в массив картинок
			}
		}
		$title = cutTitle(substr($art_info->text,0,100));//делаем заголовок, длинной не более 100 символов
		insert2db($art_info->id,$arr_img[0],$art_info->text,$arr_img,$title);//заносим в базу
		insert2dbDLE($author,$arr_img,$title,$art_info->text,$category_robots);
	}
}

function insert2dbDLE($author,$arr_img,$title,$text,$category_robots){ //добавляем в базу запись
	$today = date("Y-m-d H:i:s");
	$anons_img = 'http://4to-prigotovit.ru'.$arr_img[0];
	$table='';
	$tableImg = tableImg($arr_img,$title);
	$text = str_replace($title.'<br>','',$text);
	$text = str_replace("🍴 Больше рецептов: http://povar.ru",'',$text);
	$short_story = '<!--dle_image_begin:'.$anons_img.'|left--><img src="'.$anons_img.'" style="float:left;" alt="" title=""  /><!--dle_image_end-->';
	$full_story = '<a href="'.$anons_img.'" rel="highslide" class="highslide "><img width="175px" src="'.$anons_img.'" style="float:left;" alt="'.$title.'" title="'.$title.'"></a>'.$text.$tableImg;
	//$short_story = 'test';
	$query = mysql_query("INSERT INTO gfh21_post 
	(
		autor,
		date,
		short_story,
		full_story,
		title,
		category
	) 
	VALUES 
	(
		'$author',
		'$today',
		'$short_story',
		'$full_story',
		'$title',
		'$category_robots'
	)");
}

function tableImg($arr_img,$title){
$countRow = 4; //количество картинок в ряд
$countArr = count($arr_img)-1;
$colgroup = ceil($countArr/$countRow); //сколько раз по $countRow в массиве картинок
$imgNach = 1;
for ($i=1;$i<=$colgroup;$i++){
	if ($i==1){
		$table = '<table border="0"><tbody>';
	}
	$table.= "<tr>";
	for ($j=1;$j<=$countRow;$j++){
		if (isset($arr_img[$imgNach])){
		if ($arr_img[$imgNach]<>''){
			$table.= '<td><a href="'.$arr_img[$imgNach].'" rel="highslide" class="highslide"><img width="170px" src="http://4to-prigotovit.ru'.$arr_img[$imgNach].'" alt="'.$title.'" title="'.$title.'" style="float: left;" /></a></td>';
			$imgNach++;
		}
		}
	}
	$table.= "</tr>";
	if ($i==$colgroup){
		$table.= '</tbody></table>';
	}
}
if (isset($table)){
	return $table;
}else{
	return false;
}
}
//insert2dbDLE($author,'images/1398672392-183600.jpg','Коричные булочки к завтраку ','Коричные булочки к завтраку <br><br>Ингредиенты: ',$category_robots);
?>