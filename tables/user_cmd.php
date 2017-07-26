<?php

namespace MSergeev\Packages\Telegram\Tables;

use MSergeev\Core\Lib\DataManager;
use MSergeev\Core\Entity;
use MSergeev\Core\Lib\TableHelper;

class UserCmdTable extends DataManager
{
	public static function getTableName ()
	{
		return 'ms_telegram_user_cmd';
	}

	public static function getTableTitle ()
	{
		return 'Команды пользователей';
	}

	public static function getMap ()
	{
		return array(
			TableHelper::primaryField(),
			new Entity\IntegerField('USER_ID',array(
				'required' => true,
				'link' => 'ms_telegram_users.ID',
				'title' => 'ID пользователя'
			)),
			new Entity\IntegerField('CMD_ID',array(
				'required' => true,
				'link' => 'ms_telegram_cmd.ID',
				'title' => 'ID команды'
			))
		);
	}
}