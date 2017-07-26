<?php

namespace MSergeev\Packages\Telegram\Tables;

use MSergeev\Core\Lib\DataManager;
use MSergeev\Core\Entity;
use MSergeev\Core\Lib\TableHelper;

class UsersTable extends DataManager
{
	public static function getTableName ()
	{
		return 'ms_telegram_users';
	}

	public static function getTableTitle ()
	{
		return 'Пользователи';
	}

	public static function getTableLinks ()
	{
		return array(
			'ID' => array(
				'ms_telegram_user_cmd' => 'USER_ID'
			)
		);
	}

	public static function getMap ()
	{
		return array(
			TableHelper::primaryField(),
			new Entity\StringField('USER_NAME',array(
				'required' => true,
				'title' => 'Имя пользователя'
			)),
			new Entity\IntegerField('CHAT_ID',array(
				'required' => true,
				'title' => 'Уникальное ID пользователя'
			)),
			new Entity\IntegerField('MEMBER_ID',array(
				'title' => 'ID пользователя умного дома'
			)),
			new Entity\DatetimeField('CREATED',array(
				'title' => 'Дата создания пользователя'
			)),
			new Entity\BooleanField('ADMIN',array(
				'required' => true,
				'default_value' => false,
				'title' => 'Флаг, является ли пользователь админом'
			)),
			new Entity\BooleanField('HISTORY',array(
				'required' => true,
				'default_value' => false,
				'title' => 'Флаг, имеет ли доступ к истории'
			)),
			new Entity\IntegerField('HISTORY_LEVEL',array(
				'required' => true,
				'default_value' => 0,
				'title' => 'Уровень получаемых сообщений'
			)),
			new Entity\TextField('HISTORY_SOURCE',array(
				'serialised' => true,
				'title' => 'Типы источников сообщений, на которые подписан пользователь'
			)),
			new Entity\BooleanField('CMD',array(
				'required' => true,
				'default_value' => false,
				'title' => 'Флаг, может ли пользователь запускать команды'
			)),
			new Entity\BooleanField('DOWNLOAD',array(
				'required' => true,
				'default_value' => false,
				'title' => 'Флаг, сохранять изображения присланные пользователем'
			)),
			new Entity\BooleanField('PLAY',array(
				'required' => true,
				'default_value' => false,
				'title' => 'Флаг, воспроизводить файлы, присланные пользователем'
			))
		);
	}
}