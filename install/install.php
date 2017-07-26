<?php

use MSergeev\Core\Lib as CoreLib;

$packageName = 'telegram';
CoreLib\Loader::IncludePackage($packageName);

use MSergeev\Packages\Kuzmahome\Lib as KuzmahomeLib;
use MSergeev\Packages\Telegram\Lib as TlgLib;

//Подписываемся на события
CoreLib\Events::registerPackageDependences('kuzmahome','OnSay',$packageName,'MSergeev\Packages\Telegram\Lib\Telegram','onSayHandler');

//Создаем таблицы в DB
//CoreLib\Installer::createPackageTables($packageName);

//Создаем символические ссылки на демонов
$daemonsLinkPath = KuzmahomeLib\Daemons::getDaemonsPath();
$daemonsPath = CoreLib\Config::getConfig(strtoupper($packageName).'_ROOT').'daemons/';
if (is_dir($daemonsPath))
{
	if ($dh = opendir($daemonsPath))
	{
		while (($file = @readdir($dh)) !== false)
		{
			if ($file != "." && $file != ".." && $file != ".daemon_blank.php")
			{
				if (!file_exists($daemonsLinkPath.$file))
				{
					symlink($daemonsPath.$file,$daemonsLinkPath.$file);
				}
			}
		}
		@closedir($dh);
	}
}
//Добавляем и запускаем демонов
KuzmahomeLib\Daemons::addNewDaemon(
	array(
		'NAME' => 'telegram',
		'DESCRIPTION' => 'Отвечает за получение ботом и обработку сообщений от пользователей Телеграм',
		'RUN' => true,
		'RUN_STARTUP' => true
	)
);

return true;