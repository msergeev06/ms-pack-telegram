<?php
/*
 * https://core.telegram.org/bots/api
 * http://freelancer.kiev.ua/blog/%D0%BA%D0%B0%D0%BA-%D1%81%D0%BE%D0%B7%D0%B4%D0%B0%D1%82%D1%8C-%D0%B1%D0%BE%D1%82%D0%B0-telegram-%D0%BD%D0%B0-php-%D1%87%D0%B0%D1%81%D1%82%D1%8C-2/
 *
*/
namespace MSergeev\Packages\Telegram\Lib;

use MSergeev\Core\Lib as CoreLib;
use MSergeev\Core\Entity\Query;
use MSergeev\Packages\Kuzmahome\Lib as KuzmaLib;
use MSergeev\Packages\Kuzmahome\Tables as KuzmaTables;
use MSergeev\Packages\Telegram\Entity;
use MSergeev\Packages\Telegram\Tables;

class Telegram
{
	const EVENTS_FOR_CALLBACK = 9;
	const EVENTS_FOR_LOCATION = 8;
	const EVENTS_FOR_TEXT_MESSAGE = 1;

	private static
		$isConfig = false,
		$token,         //Токен бота
		$cachedDir,     //Папка кеша
		$botName,       //Имя бота
		$isDebug,       //Режим отладки
		$isWebhook,     //Используем webhook
		$storageDir,    //Папка сохранения файлов
		$countRow,
		$playVoiceLvl;  //Уровень важности присланного голоса

	public static function processDaemon()
	{
		$objBot = static::init();
		//msDebug($objBot->getMe());
		if (static::$isWebhook)
			return;
		// Get all the new updates and set the new correct update_id
		$updates = $objBot->getUpdates(0,100,5);
		//msDebug($updates);
		$updateCount = $objBot->UpdateCount();
		//msDebug($updateCount);
		for($i = 0; $i < $updateCount; $i++) {
			// You NEED to call serveUpdate before accessing the values of message in Telegram Class
			$objBot->serveUpdate($i);
			static::processMessage($objBot);
		}
	}

	public static function sendContent ($content)
	{
		$objBot = static::init();
		static::debug($content);
		$res = $objBot->sendMessage($content);
		static::debug($res);
	}

	public static function onSayHandler ($arRec)
	{
		/*
		$arRec = array(
			'MESSAGE' => $strPhrase,
			'DATETIME' => date('d.m.Y H:i:s'),
			'ROOM_ID' => intval($iRoomID),
			'MEMBER_ID' => intval($iMemberID),
			'SOURCE' => $strSource,
			'LEVEL' => intval($iLevel)
		);
		 */
		$message = KuzmaLib\Say::clearMessage($arRec['MESSAGE']);

		$arRes = Tables\UsersTable::getList(
			array(
				'select' => array('ID','CHAT_ID','HISTORY_LEVEL','HISTORY_SOURCE'),
				'filter' => array(
					'HISTORY' => true
				)
			)
		);
		if ($arRes)
		{
			foreach ($arRes as $ar_res)
			{
				$content = null;
				if ($arRec['SOURCE']=='')
				{
					$content = array(
						'chat_id' => $ar_res['CHAT_ID'],
						'text' => $message
					);
				}
				elseif (preg_match('/telegram.*/',$arRec['SOURCE'],$matches))
				{
					$content = null;
				}
				elseif (!is_null($ar_res['HISTORY_SOURCE']))
				{
					$ar_res['HISTORY_SOURCE'] = unserialize($ar_res['HISTORY_SOURCE']);
					if (in_array(strval($arRec['SOURCE']),$ar_res['HISTORY_SOURCE']))
					{
						$content = array(
							'chat_id' => $ar_res['CHAT_ID'],
							'text' => $message
						);
					}
				}
				elseif ($arRec['LEVEL']>=$ar_res['HISTORY_LEVEL'])
				{
					$content = array(
						'chat_id' => $ar_res['CHAT_ID'],
						'text' => $message
					);
				}

				if (!is_null($content))
				{
					static::sendContent($content);
				}
			}
		}
	}

	private static function init ()
	{
		if (!static::$isConfig)
		{
			static::$isConfig = true;
			static::$token = CoreLib\Options::getOptionStr('TELEGRAM_TOKEN');
			static::$cachedDir = CoreLib\Options::getOptionStr('TELEGRAM_CACHED_DIR');
			static::$botName = CoreLib\Options::getOptionStr('TELEGRAM_BOTNAME');
			static::$isDebug = CoreLib\Options::getOptionInt('TELEGRAM_DEBUG');
			static::$isWebhook = CoreLib\Options::getOptionInt('TELEGRAM_WEBHOOK');
			static::$playVoiceLvl = CoreLib\Options::getOptionInt('TELEGRAM_PLAY_VOICE_LEVEL');
			static::$storageDir = CoreLib\Options::getOptionStr('TELEGRAM_STORAGE');
			static::$countRow = CoreLib\Options::getOptionInt('TELEGRAM_COUNT_ROW');
		}
		
		return new Entity\TelegramBot(static::$token);
	}

	private static function processMessage (Entity\TelegramBot $objBot)
	{
		$skip = false;
		$bot_name = static::$botName;

		$data = $objBot->getData();
		//msDebug($data);
		static::debug($data);
		$callback = $objBot->Callback_Data();
		//msDebug($callback);
		if($callback) {
			$chat_id = $objBot->Callback_ChatID();
			$cbm = $objBot->Callback_Message();
			$message_id = $cbm["message_id"];
			// get events for callback
			//$events = SQLSelect("SELECT * FROM tlg_event WHERE TYPE_EVENT=9 and ENABLE=1;");
			$events = static::getEvents(self::EVENTS_FOR_CALLBACK);
			if (isset($events[0]))
			{
				foreach($events as $event) {
					if($event['CODE']) {
						static::log("Execute code event " . $event['TITLE']);
						try {
							eval($event['CODE']);
						}
						catch(\Exception $e) {
							static::registerError('telegram', sprintf('Exception in "%s" method ' . $e->getMessage(), $event['CORE']));
						}
					}
				}
			}
			return;
		}
		$text = $objBot->Text();
		$chat_id = $objBot->ChatID();
		$document = $objBot->Document();
		$audio = $objBot->Audio();
		$video = $objBot->Video();
		$voice = $objBot->Voice();
		$sticker = $objBot->Sticker();
		$photo_id = $objBot->PhotoIdBigSize();
		$username = $objBot->Username();
		$fullname = $objBot->FirstName() . ' ' . $objBot->LastName();
		$location = $objBot->Location();
		$arDebug = array(
			'text' => $text,
			'chat_id' => $chat_id,
			'document' => $document,
			'audio' => $audio,
			'video' => $video,
			'voice' => $voice,
			'sticker' => $sticker,
			'photo_id' => $photo_id,
			'username' => $username,
			'fullname' => $fullname,
			'location' => $location
		);
		//msDebug($arDebug);
		// найти в базе пользователя
		//$user = SQLSelectOne("SELECT * FROM tlg_user WHERE CHAT_ID LIKE '" . DBSafe($chat_id) . "';");
		$user = static::getOneUserByChatId($chat_id);
		//msDebug($user);
		if($chat_id < 0 && substr($text, 0, strlen('@' . $bot_name)) === '@' . $bot_name) {
			//DebMes("Direct message to bot: ".$bot_name. " ($text)");
			$text = str_replace('@' . $bot_name, '', $text);
			//$source_user = SQLSelectOne("SELECT * FROM tlg_user WHERE TRIM(NAME) LIKE '" . DBSafe(trim($username)) . "'");
			$query = new Query('select');
			$sql = "SELECT *\nFROM\n\t"
				.Tables\UsersTable::getTableName()."\nWHERE\n\tTRIM(USER_NAME) LIKE '" . trim($username) . "' LIMIT 1";
			$query->setQueryBuildParts($sql);
			$source_user = $query->exec()->fetch();
			if(isset($source_user['ID'])) {
				$user = $source_user;
				static::log("New user check: ".serialize($user));
			} else {
				static::log("Cannot find user: ".$username);
			}
		} else {
			static::log("Chatid: ".$chat_id."; Bot-name: ".$bot_name."; Message: ".$text);
		}
		if($location)
		{
			$latitude = $location["latitude"];
			$longitude = $location["longitude"];
			static::log("Get location from " . $chat_id . " - " . $latitude . "," . $longitude);
			if($user['MEMBER_ID']) {
				//$sqlQuery = "SELECT * FROM users WHERE ID = '" . $user['MEMBER_ID'] . "'";
				//$userObj = SQLSelectOne($sqlQuery);
				/*
				$arParams = array(
					'PROPERTY_COORDINATES' => $latitude . ',' . $longitude,
					'PROPERTY_COORDINATES_UPDATED' => date('H:i'),
					'PROPERTY_COORDINATES_UPDATED_TIMESTAMP' => time()
				);
				static::log("Update location to user ID='" . $user['ID']."'");
				Users::setUserParams($user['ID'],$arParams);
				*/
				$userObj = KuzmaTables\UsersTable::getList(
					array(
						'filter' => array('ID'=>intval($user['MEMBER_ID'])),
						'limit' => 1
					)
				);
				if ($userObj && isset($userObj[0]))
				{
					$userObj = $userObj[0];
				}
				if($userObj['LINKED_OBJECT'])
				{
					KuzmaLib\Objects::setGlobal($userObj['LINKED_OBJECT'] . '.Coordinates', $latitude . ',' . $longitude);
					KuzmaLib\Objects::setGlobal($userObj['LINKED_OBJECT'] . '.CoordinatesUpdated', date('H:i'));
					KuzmaLib\Objects::setGlobal($userObj['LINKED_OBJECT'] . '.CoordinatesUpdatedTimestamp', time());
				}
			}
			// get events for location
			//$events = SQLSelect("SELECT * FROM tlg_event WHERE TYPE_EVENT=8 and ENABLE=1;");
			$events = static::getEvents(self::EVENTS_FOR_LOCATION);
			if (isset($events[0]))
			{
				foreach($events as $event) {
					if($event['CODE']) {
						static::log("Execute code event " . $event['TITLE']);
						try {
							eval($event['CODE']);
						}
						catch(\Exception $e) {
							static::registerError('telegram', sprintf('Exception in "%s" method ' . $e->getMessage(), $text));
						}
					}
				}
			}
			return;
		}
		//permission download file
		if($user['DOWNLOAD'] == 1) {
			$type = 0;
			//папку с файлами в настройках
			$storage = static::$storageDir;
			if($photo_id) {
				$file = $objBot->getFile($photo_id);
				static::log("Get photo from " . $chat_id . " - " . $file["result"]["file_path"]);
				$file_path = $storage . $chat_id . '/' . $file["result"]["file_path"];
				$type = 2;
			}
			if($document) {
				$file = $objBot->getFile($document["file_id"]);
				static::log("Get document from " . $chat_id . " - " . $document["file_name"]);
				//print_r($file);
				if(!isset($file['error_code'])) {
					$file_path = $storage . $chat_id . '/' . "document" . '/' . $document["file_name"];
					if(file_exists($file_path))
						$file_path = $storage . $chat_id . '/' . "document" . '/' . $objBot->UpdateID() . "_" . $document["file_name"];
				} else {
					$file_path = "";
					static::log($file['description']);
				}
				$type = 6;
			}
			if($audio) {
				$file = $objBot->getFile($audio["file_id"]);
				//print_r($file);
				static::log("Get audio from " . $chat_id . " - " . $file["result"]["file_path"]);
				$path_parts = pathinfo($file["result"]["file_path"]);
				$filename = $path_parts["basename"];
				//use title and performer
				if(isset($audio['title']))
					$filename = $audio['title'] . "." . $path_parts['extension'];
				if(isset($audio['performer']))
					$filename = $audio['performer'] . "-" . $filename;
				$file_path = $storage . $chat_id . '/' . "audio" . '/' . $filename;
				$type = 4;
			}
			if($voice) {
				$file = $objBot->getFile($voice["file_id"]);
				//print_r($file);
				static::log("Get voice from " . $chat_id . " - " . $file["result"]["file_path"]);
				$file_path = $storage . $chat_id . '/' . $file["result"]["file_path"];
				$type = 3;
			}
			if($video) {
				$file = $objBot->getFile($video["file_id"]);
				//print_r($file);
				static::log("Get video from " . $chat_id . " - " . $file["result"]["file_path"]);
				$file_path = $storage . $chat_id . '/' . $file["result"]["file_path"];
				$type = 5;
			}
			if($sticker) {
				$file = $objBot->getFile($sticker["file_id"]);
				static::log("Get sticker from " . $chat_id . " - " . $sticker["file_id"]);
				//$file_path = $storage.$chat_id.'/'.$file["result"]["file_path"];
				$sticker_id = $sticker["file_id"];
				$type = 7;
			}
			if(isset($file_path) && isset($file))
			{
				// качаем файл
				//msDebug($file_path);
				//msDebug($file);
				$path_parts = pathinfo($file_path);
				if(!is_dir($path_parts['dirname']))
					mkdir($path_parts['dirname'], 0777, true);
				//TODO:Ошибка в методе
				/*
Warning: fopen(https://api.telegram.org/file/bot261618880:AAHnEtoY_6XS6GiHpXqVQBhbEYCXL-Y3zfU/): failed to open stream: HTTP request failed! HTTP/1.1 404 Not Found in /var/www/msergeev/packages/telegram/entity/telegram_bot.php on line 626

Warning: fread() expects parameter 1 to be resource, boolean given in /var/www/msergeev/packages/telegram/entity/telegram_bot.php on line 629

Warning: fclose() expects parameter 1 to be resource, boolean given in /var/www/msergeev/packages/telegram/entity/telegram_bot.php on line 632
				*/
				$objBot->downloadFile($file["result"]["file_path"], $file_path);
			}
			if($voice && $user['PLAY'] == 1 && isset($file_path)) {
				//проиграть голосовое сообщение
				static::log("Play voice from " . $chat_id . " - " . $file_path);
				@touch($file_path);
				KuzmaLib\Sound::playSound($file_path, 1, static::$playVoiceLvl);
			}
			if(isset($file_path) || isset($sticker_id)) {
				// get events
				//$events = SQLSelect("SELECT * FROM tlg_event WHERE TYPE_EVENT=" . $type . " and ENABLE=1;");
				$events = static::getEvents($type);
				if (isset($events[0]))
				{
					foreach($events as $event) {
						if($event['CODE']) {
							static::log("Execute code event " . $event['TITLE']);
							try {
								eval($event['CODE']);
							}
							catch(\Exception $e) {
								static::registerError('telegram', sprintf('Exception in "%s" method ' . $e->getMessage(), $text));
							}
						}
					}
				}
			}
			$file_path = "";
		}
		if($text == "") {
			return;
		}
		static::log($chat_id . " (" . $username . ", " . $fullname . ")=" . $text);
		// get events for text message
		//$events = SQLSelect("SELECT * FROM tlg_event WHERE TYPE_EVENT=1 and ENABLE=1;");
		$events = static::getEvents(self::EVENTS_FOR_TEXT_MESSAGE);
		if (isset($events[0]))
		{
			foreach($events as $event) {
				if($event['CODE']) {
					static::log("Execute code event " . $event['TITLE']);
					try {
						eval($event['CODE']);
					}
					catch(\Exception $e) {
						static::registerError('telegram', sprintf('Exception in "%s" method ' . $e->getMessage(), $text));
					}
				}
			}
		}
		// пропуск дальнейшей обработки если с обработчике событий установили $skip
		if($skip) {
			static::log("Skip next processing message");
			return;
		}
		if($text == "/start" || $text == "/start@" . $bot_name)
		{
			// найти в базе пользователя
			// если нет добавляем
			//$user = SQLSelectOne("SELECT * FROM tlg_user WHERE USER_ID LIKE '" . DBSafe($chat_id) . "';");
			$user = static::getOneUserByChatId($chat_id);
			if(!$user['ID'])
			{
				$user = array();
				$user['CHAT_ID'] = intval($chat_id);
				$user['CREATED'] = date('d.m.Y H:i:s');
				if ($username!='')
				{
					$user['USER_NAME'] = $username;
				}
				elseif ($fullname!='')
				{
					$user['USER_NAME'] = $fullname;
				}
				else
				{
					$user['USER_NAME'] = $chat_id;
				}
				//$user['ID'] = SQLInsert('tlg_user', $user);
				$user['ID'] = Tables\UsersTable::add(array("VALUES"=>$user))->getInsertId();
				static::log("User added - " . $chat_id);
			}
			$reply = "Вы зарегистрированы! Обратитесь к администратору для получения доступа к функциям. Используйте /help для получения помощи.";
			$content = array(
				'chat_id' => $chat_id,
				'text' => $reply
			);
			static::sendContent($content);
			static::updateInfo($objBot, $user);
			return;
		}
		if($user['ID'])
		{
			//смотрим разрешения на обработку команд
			if($user['ADMIN'] == 1 || $user['CMD'] == 1)
			{
				$keyb = static::getKeyb($user);
				//$cmd = SQLSelectOne("SELECT * FROM tlg_cmd INNER JOIN tlg_user_cmd on tlg_cmd.ID=tlg_user_cmd.CMD_ID where tlg_user_cmd.USER_ID=" . $user['ID'] . " and ACCESS>0 and '" . DBSafe($text) . "' LIKE CONCAT(TITLE,'%');");
				$query = new Query('select');
				$cmdTable = Tables\CmdTable::getTableName();
				$userCmdTable = Tables\UserCmdTable::getTableName();
				$sql = "SELECT *\nFROM\n\t".$cmdTable."\n"
					."INNER JOIN ".$userCmdTable." ON ".$cmdTable.".ID=".$userCmdTable.".CMD_ID\n"
					."WHERE\n\t".$userCmdTable.".USER_ID=" . $user['ID'] . " AND\n\tACCESS>0 AND\n\t'" . $text . "' LIKE CONCAT(TITLE,'%')\nLIMIT 1;";
				$query->setQueryBuildParts($sql);
				$cmd = $query->exec()->fetch();
				if($cmd['ID'])
				{
					static::log("Find command");
					//нашли команду
					if($cmd['CODE'])
					{
						static::log("Execute user`s code command");
						try
						{
							$success = eval($cmd['CODE']);
							static::log("Command:" . $text . " Result:" . $success);
							if($success == false)
							{
								//нет в выполняемом куске кода return
								//$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "Ошибка выполнения кода команды ".$text);
								//$telegramBot->sendMessage($content);
							}
							else
							{
								$content = array(
									'chat_id' => $chat_id,
									'reply_markup' => $keyb,
									'text' => $success,
									'parse_mode' => 'HTML'
								);
								static::sendContent($content);
								static::log("Send result to " . $chat_id . ". Command:" . $text . " Result:" . $success);
							}
						}
						catch(\Exception $e)
						{
							static::registerError('telegram', sprintf('Exception in "%s" method ' . $e->getMessage(), $text));
							$content = array(
								'chat_id' => $chat_id,
								'reply_markup' => $keyb,
								'text' => "Ошибка выполнения кода команды " . $text
							);
							static::sendContent($content);
						}
						return;
					}
					// если нет кода, который надо выполнить, то передаем дальше на обработку
				}
				else
				{
					static::log("Command not found");
				}
				if($text == "/test")
				{
					if($objBot->messageFromGroup())
					{
						$reply = "Chat Group";
					}
					else
					{
						$reply = "Private Chat";
					}
					$content = array(
						'chat_id' => $chat_id,
						'reply_markup' => $keyb,
						'text' => $reply
					);
					static::sendContent($content);
				}
				else
				{
					KuzmaLib\Say::say(htmlspecialchars($text), 0, 0, $user['MEMBER_ID'], 'telegram' . $user['ID']);
				}
			}
		}
	}

	private static function updateInfo (Entity\TelegramBot $objBot, array $user)
	{
		CoreLib\Loader::IncludePackage('kuzmahome');
		$chat = $objBot->getChat($user['CHAT_ID']);
		static::debug($chat);
		if($user['USER_NAME'] == "") {
			// set name
			if($chat["result"]["type"] == "private")
				$user["USER_NAME"] = $chat["result"]["first_name"] . " " . $chat["result"]["last_name"];
			else
				$user["USER_NAME"] = $chat["result"]["title"];
			//SQLUpdate("tlg_user", $user);
			Tables\UsersTable::update($user['ID'],array("VALUES"=>array('USER_NAME'=>$user['USER_NAME'])));
		}
		if($chat["result"]["type"] == "private") {
			$content = array(
				'user_id' => $user['CHAT_ID']
			);
			$image = $objBot->getUserProfilePhotos($content);
			//$this->debug($image);
			$file = $objBot->getFile($image["result"]["photos"][0][0]["file_id"]);
			static::debug($file);
			$file_path = static::$cachedDir . $user['CHAT_ID'] . ".jpg";
			// качаем файл
			$path_parts = pathinfo($file_path);
			KuzmaLib\Files::createDir($path_parts['dirname']);
			$objBot->downloadFile($file["result"]["file_path"], $file_path);
		}
	}

	private static function getKeyb (array $user)
	{
		$visible = true;
		// Create option for the custom keyboard. Array of array string
		if($user['ADMIN'] == 0 && $user['CMD'] == 0) {
			$option = array();
			$visible = false;
		} else {
			//$option = array( array("A", "B"), array("C", "D") );
			$option = array();
			//$rec = SQLSelect("SELECT *,(select VALUE from pvalues where Property_name=`LINKED_OBJECT`+'.'+`LINKED_PROPERTY` ORDER BY updated DESC limit 1) as pvalue" . " FROM tlg_cmd INNER JOIN tlg_user_cmd on tlg_cmd.ID=tlg_user_cmd.CMD_ID where tlg_user_cmd.USER_ID=" . $user['ID'] . " and ACCESS>0 order by tlg_cmd.PRIORITY desc, tlg_cmd.TITLE;");
			$rec = false;
			//$total = count($rec);
			$total = 0;
			if($total) {
				for($i = 0; $i < $total; $i++) {
					$view = false;
					if($rec[$i]["SHOW_MODE"] == 1)
						$view = true;
					elseif($rec[$i]["SHOW_MODE"] == 3) {
						if($rec[$i]["CONDITION"] == 1 && $rec[$i]["pvalue"] == $rec[$i]["CONDITION_VALUE"])
							$view = true;
						if($rec[$i]["CONDITION"] == 2 && $rec[$i]["pvalue"] > $rec[$i]["CONDITION_VALUE"])
							$view = true;
						if($rec[$i]["CONDITION"] == 3 && $rec[$i]["pvalue"] < $rec[$i]["CONDITION_VALUE"])
							$view = true;
						if($rec[$i]["CONDITION"] == 4 && $rec[$i]["pvalue"] <> $rec[$i]["CONDITION_VALUE"])
							$view = true;
					}
					if($view)
						$option[] = $rec[$i]["TITLE"];
				}
				$count_row = static::$countRow;
				if(!$count_row)
					$count_row = 3;
				$option = array_chunk($option, $count_row);
			}
		}
		// Get the keyboard
		$objBot = static::init();
		$keyb = $objBot->buildKeyBoard($option, $resize = true, $selective = $visible);
		//print_r($keyb);
		return $keyb;
	}

	private static function getEvents ($iTypeEvent, $bIsEnable=true)
	{
		return Tables\EventTable::getList(
			array(
				'filter' => array(
					'TYPE_EVENT' => $iTypeEvent,
					'ENABLE' => $bIsEnable
				)
			)
		);
	}

	private static function getOneUserByChatId ($chat_id)
	{
		$user = Tables\UsersTable::getList(
			array(
				'filter' => array(
					'CHAT_ID' => intval($chat_id)
				),
				'limit' => 1
			)
		);
		if ($user && isset($user[0]))
		{
			$user = $user[0];
		}

		return $user;
	}

	private static function debug ($content)
	{
		if(static::$isDebug)
			static::log(print_r($content,true));
	}

	private static function log ($strMessage)
	{
		$logsDir = KuzmaLib\Logs::getLogsDir();
		$today_file = $logsDir . 'log-telegram_' . date('Y-m-d') . '.txt';
		$f1 = fopen ($today_file, 'a');
		$tmp=explode(' ', microtime());
		fwrite($f1, date("H:i:s ").$tmp[0].' '.$strMessage."\n------------------\n");
		fclose ($f1);
		@chmod($today_file, KuzmaLib\Files::getFileChmod());
	}

	private static function registerError ($title, $mess)
	{
		static::log('Error '.$title.': '.$mess);
	}

	private static function _curl($url, array $arPost = array()) {

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		//curl_setopt($ch, CURLOPT_VERBOSE, true);
		if (!empty($arPost)) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $arPost);
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		//curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
	}
}