<? 
/*__nks{ путь заточен под расположение файла в папке bitrix. Если файл в другой папке - меняем пути }*/
require(dirname(__FILE__)."/modules/main/include/prolog_before.php");
if (!CModule::IncludeModule("sale"))
{
	echo 'Не подключен модуль';
	return;
}
/*__nks{ тут по сути есть 2 варианта выборки, зависящие от количества покупателей в месяц. Можно выбрать все отложенные товары, получить по ним юзеров, по юзерам получить заказы и вычесть из отложенных товаров купленные. Или второй вариант, как здесь, просто получаем все корзины всех юзеров, группируем по ним же и получаем массив отложенных товаров без учета купленных. }*/
 $bdBaskets = CSaleBasket::GetList(
	array(
		'DELAY' => 'ASC',
		'FUSER_ID' => 'ASC'
		),
	array(
		'LID'=> SITE_ID,
		'>=DATE_INSERT' => \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime("-30 days"))
		)
	);

$usData = array();
$tmpSell = array();

while ($baskets = $bdBaskets->GetNext()) {
	if ($baskets['DELAY'] == 'N') {

		/*__nks{ получаем проданные товары }*/		  
		$tmpSell[$baskets['FUSER_ID']][$baskets['PRODUCT_ID']] = $baskets['PRODUCT_ID'];

	} else {
		/*__nks{ проверяем есть ли отложенный товар в проданных }*/		  
		if (!in_array($baskets['PRODUCT_ID'], $tmpSell[$baskets['FUSER_ID']])) {

			/*__nks{ проверяем есть ли юзер в системе }*/			  
			$arFilter=Array('ID' => $baskets['FUSER_ID'], '!USER_ID' => '');	

			if ($trueUser=CSaleUser::GetList($arFilter)) {

				$db_user = CUser::GetByID($trueUser['USER_ID']);
				if ($user = $db_user->Fetch()) {

					/*__nks{ составляем массив с данными пользователя и его отложенными товарами }*/
					$usData[$user['ID']]['USER_FIRST_NAME'] = $user['NAME'];
					$usData[$user['ID']]['USER_LAST_NAME'] = $user['LAST_NAME'];
					$usData[$user['ID']]['USER_EMAIL'] = $user['EMAIL'];
					$usData[$user['ID']]['PRODUCTS'][$baskets['PRODUCT_ID']]['PRODUCT_ID'] = $baskets['PRODUCT_ID'];
					$usData[$user['ID']]['PRODUCTS'][$baskets['PRODUCT_ID']]['NAME'] = $baskets['NAME'];
					$usData[$user['ID']]['PRODUCTS'][$baskets['PRODUCT_ID']]['DETAIL_PAGE_URL'] = $baskets['DETAIL_PAGE_URL'];
					  
				}
				
			}
		}
	}
}


$event_type = new CEventType();
$event_temp = new CEventMessage();
$event = new CEvent();
$EVENT_TYPE = '';
$EVENT_TEMPLATE = '';

/*__nks{ проверяем есть ли в системе почтовое событие. Если нет - создаем. }*/  
$db_event_type_check= $event_type->GetByID("MOUNTH_DELAY_DELIVERY",LANGUAGE_ID);
if($event_type_check = $db_event_type_check->Fetch()){
	$EVENT_TYPE = $event_type_check['ID'];
}else{
	$EVENT_TYPE = $event_type->Add(array(
		"LID"           => LANGUAGE_ID,
		"EVENT_NAME"    => "MOUNTH_DELAY_DELIVERY",
		"NAME"          => "Ежемесечная рассылка по отложенным товарам",
		"DESCRIPTION"   => "
		#EMAIL_TO#
		#USER_NAME#
		#USER_LASTNAME#
		#PRODUCT_LIST#
		"
	));
}

/*__nks{ проверяем есть ли в системе почтовый шаблон. Если нет - создаем. }*/  
$db_template = $event_temp->GetList($by="site_id", $order="desc", array("TYPE_ID" => "MOUNTH_DELAY_DELIVERY"));
if($template = $db_template->Fetch()){
	$EVENT_TEMPLATE = $template['ID'];
}else{
	$EVENT_TEMPLATE = $event_temp->Add(array(
		"ACTIVE" 		=> "Y",
		"EVENT_NAME"    => "MOUNTH_DELAY_DELIVERY",
		"LID"           => SITE_ID,
		"EMAIL_FROM"	=> "test@test.ru", //тут можно по-разному реализовать
		"EMAIL_TO"		=> "#EMAIL_TO#",
		"SUBJECT"		=> "Ваши товары ждут Вас!",
		"BODY_TYPE"		=> "html",
		"MESSAGE"		=> "Добрый день, #USER_NAME# #USER_LASTNAME#.<br>
		В вашем вишлисте хранятся товары<br>
		#PRODUCT_LIST#"

	));
}

/*__nks{ тут опять же можно идти 2 путями - или регистрировать почтовыое событие через CEvent::Send, и потом отправлять или просто отправлять без записи в базу, как сделано ниже }*/  
foreach ($usData as $data) {	

	/*__nks{ оформляем список товаров }*/	  
	$PRODUCT_LIST = '';
  	foreach ($data['PRODUCTS'] as $products) { 		  
  		$PRODUCT_LIST .= '<a href="http://'.SITE_SERVER_NAME.$products['DETAIL_PAGE_URL'].'">'.$products['NAME'].'</a><br>';
  	}
  	$event->SendImmediate(
  		$EVENT_TYPE,
  		SITE_ID,
  		array(
			'EMAIL_TO' => $data['USER_EMAIL'],
			'USER_NAME' => $data['USER_FIRST_NAME'],
			'USER_LASTNAME' => $data['USER_LAST_NAME'],
			'PRODUCT_LIST' => $PRODUCT_LIST
		),
		"N",
		$EVENT_TEMPLATE
  	);
  }  

require(dirname(__FILE__)."/modules/main/include/epilog_after.php");?>