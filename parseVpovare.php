<?php
//ini_set("display_errors",1);
//error_reporting(E_ALL);

mysql_connect('192.168.0.3','root','') or die ('<p>Ошибка подключения к БД.</p>');
mysql_select_db('4toprigotovit') or die ('<p>Ошибка выбора базы.</p>');


$author = "VKposter"; //автор новости
$category_robots = '2,77'; //категория новости автодобавления
$group_id = 18464856; //Айдишник группы вбыдлятне
$count = 10; //Количество записей
$_REQUEST['issetText'] = true; //Хуерга не нужная

$fucking_words = array ( //Массив гадких словечек в заголовках, записи с которыми не добавляем на сайт
	'?',
	'????',
	'club',
	'🎀 ',
	'✔',
	'🆕',
	']',
	'[',
	'}',
	'{',
	'CELLULES'
);

function check_id($id){ // проверка на уникальность ID
	$query = mysql_query("SELECT * FROM groupvk WHERE id='$id'");
	//echo mysql_num_rows($query);
	if (mysql_num_rows($query)>0){
		return false;
	}else{
		return true;
	}
}

function check_fucking_words($text,$fucking_words){ // Проверка входит ли слово в массив слов-паразитов
	foreach ($fucking_words as $word){
		if (stripos($text, $word)!== false){
			return true;
		}
	}
	return false;
}

function insert2db($id,$img_anons,$text,$img_attach,$title){ //добавляем в базу запись
	$img_attach = implode(",", $img_attach);
	$query = mysql_query("INSERT INTO groupvk (id,img_anons,text,img_attach,zagolovok) VALUES ($id,'$img_anons','$text','$img_attach','$title')");
}

function uploadImg($url, $nameImg){ // Загрузка картинки

if (!file_put_contents(dirname(__FILE__).$nameImg, file_get_contents($url))){
	echo "<p>Изображение не загрузилось.</p>";
	return false;
}
}

function create_path($path){ //Создаем каталоги для картинок
$arr = explode('/', $path);  
$curr=array(); 
foreach($arr as $key => $val){ if(!empty($val)){
    $curr[]=$val;
    mkdir(implode('/',$curr)."/", 0700);
}}
}

function generateNameImg($group_id,$news_id){ //Создаем имя картинки и готовим каталог для сохранения
	$file_path = dirname(__FILE__);
	$catalog = "/images/".$group_id."/";
	$subcat = date('Y/m/d/',time());
	//$subcat = $news_id.'/';
	$rand = rand(10000,1000000);
	$format = "jpg";
	create_path($file_path.$catalog.$subcat);
	$nameImg=$catalog.$subcat.$news_id.'-'.$rand.'.'.$format;
	
	return $nameImg;
}

function cutTitle($text){ //Берём первую строку текста для заголовка
	$pieces = explode("<br>", $text);
	//var_dump($pieces[0]);
	return $pieces[0];
}

$ch = file_get_contents("http://api.vk.com/method/wall.get?owner_id=-$group_id&count=$count");
$result = json_decode($ch);
$result = translate_ch($result);
foreach ($result->response as $art_info){
	if((is_int($art_info->id))and (check_id($art_info->id)) and (isset($art_info->id))){ //Антидубль
		$title = cutTitle(substr($art_info->text,0,100));//делаем заголовок, длинной не более 100 символов
		$fucking_news = check_fucking_words($title,$fucking_words);
		
		if (isset($_REQUEST['issetText'])){ //Брать посты, в которых есть текст
			if ((!$art_info->text<>'')or(iconv_strlen($art_info->text)<50)or($title=='')){
				$fucking_news = true;
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
				$nameImg = generateNameImg($group_id,$art_info->id); //генерируем уникальное имя картинки
				//print_r($nameImg);
				if (!$fucking_news){
					uploadImg($image->photo->src_big,$nameImg); //загружаем картинку к себе
					$arr_img[] = $nameImg; //добавляем в массив картинок
				}
			}
		}
		insert2db($art_info->id,$arr_img[0],$art_info->text,$arr_img,$title);//заносим в базу
		if (!$fucking_news){
			insert2dbDLE($author,$arr_img,$title,$art_info->text,$category_robots);
		}	
	}
}

function insert2dbDLE($author,$arr_img,$title,$text,$category_robots){ //добавляем в базу запись
	$today = date("Y-m-d H:i:s");
	$anons_img = 'http://4to-prigotovit'.$arr_img[0];
	$table='';
	$tableImg = tableImg($arr_img,$title);
	//$alt_name = translit_title($title);
	$text = str_replace($title.'<br><br>','',$text);
	$text = str_replace($title,'',$text);
	$text = str_replace("Ингредиенты:","Ингредиенты для ".mb_strtolower($title,'UTF-8').":",$text);
	//$text = str_replace("Больше рецептов: http://povar.ru","",$text);
	$text = str_replace("🍴 Больше рецептов: http://povar.ru",'',$text);
	//$text = ereg_replace("( )Больше рецептов: http://povar.ru", "", $text);
	//$text = preg_replace("/\?{4}/", " ", $text);
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
			$table.= '<td><a href="'.$arr_img[$imgNach].'" rel="highslide" class="highslide"><img width="170px" src="http://4to-prigotovit'.$arr_img[$imgNach].'" alt="'.$title.'" title="'.$title.'" style="float: left;" /></a></td>';
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

function translate_ch($text){

if (mb_check_encoding($text, 'UTF-8') && !mb_check_encoding($text, 'Windows-1251'))
        $text = mb_convert_encoding($text, 'Windows-1251', 'UTF-8');
		return $text;
}
/*
function translit($text){ //Транслитирация текста
// Делаем проверку на существование переменной
$st = $text;
$st2 = $st; // <----- делаем копию переменной до перевода
// Сначала заменяем "односимвольные" фонемы слова.
$st=strtr($st,"абвгдеёзийклмнопрстуфхъыэ_","abvgdeeziyklmnoprstufh'iei"); // <----- строчные
$st=strtr($st,"АБВГДЕЁЗИЙКЛМНОПРСТУФХЪЫЭ_","ABVGDEEZIYKLMNOPRSTUFH'IEI"); // <----- ПРОПИСНЫЕ
$st=strtr($st, array( "ж"=>"zh", "ц"=>"ts", "ч"=>"ch", "ш"=>"sh", "щ"=>"shch","ь"=>"", "ю"=>"yu", "я"=>"ya", "Ж"=>"ZH", "Ц"=>"TS", "Ч"=>"CH", "Ш"=>"SH", "Щ"=>"SHCH","Ь"=>"", "Ю"=>"YU", "Я"=>"YA", "ї"=>"i", "Ї"=>"Yi", "є"=>"ie", "Є"=>"Ye", '"'=>'', "'"=>'' ));
return $st;
}

function translit_title($title){ //Транслитирация заголовка
	$result = '';
	$words = explode(' ',strtolower($title));
	foreach ($words as $word){
		//$result.=translit($word).'|';
		$result_arr[] = translit($word);
	}
	$result = implode("-", $result_arr);
	$result = translate_ch($result);
	return $result;
}
*/
//insert2dbDLE($author,'images/1398672392-183600.jpg','Коричные булочки к завтраку ','Коричные булочки к завтраку <br><br>Ингредиенты: ',$category_robots);
?>