<?php
/* Общие команды для всех демонов */
include_once ("/var/www/kuzmahome/config.php");

use MSergeev\Core\Lib as CoreLib;
use MSergeev\Packages\Kuzmahome\Lib;
use MSergeev\Packages\Telegram\Lib as TlgLib;
CoreLib\Loader::IncludePackage('telegram');
set_time_limit(0);

$daemonName = 'telegram';  /**<-- Обязательно изменить на уникальное имя демона*/

$bStopped = false;
Lib\Daemons::log($daemonName,'Daemon started');
/* end Общие команды для всех демонов*/

while (1)
{
	TlgLib\Telegram::processDaemon();

	/* Общие команды для всех демонов */
	if (Lib\Daemons::needBreak($daemonName))
	{
		$bStopped = true;
		break;
	}
	sleep(1);
	/* end Общие команды для всех демонов*/
}

/* Общие команды для всех демонов */
if (!$bStopped)
{
	Lib\Daemons::stopped($daemonName);
	Lib\Daemons::log($daemonName,"Daemon unexpected exit");
}
/* end Общие команды для всех демонов*/
