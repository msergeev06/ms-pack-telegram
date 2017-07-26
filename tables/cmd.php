<?php

namespace MSergeev\Packages\Telegram\Tables;

use MSergeev\Core\Lib\DataManager;
use MSergeev\Core\Entity;
use MSergeev\Core\Lib\TableHelper;

class CmdTable extends DataManager
{
	public static function getTableName ()
	{
		return 'ms_telegram_cmd';
	}

	public static function getTableTitle ()
	{
		return 'Команды';
	}

	public static function getTableLinks ()
	{
		return array(
			'ID' => array(
				'ms_telegram_user_cmd' => 'CMD_ID'
			)
		);
	}

	public static function getMap ()
	{
		return array(
			TableHelper::primaryField(),
			new Entity\StringField('TITLE',array(
				'required' => true,
				'title' => 'Название'
			)),
			new Entity\TextField('DESCRIPTION',array(
				'title' => 'Описание'
			)),
			new Entity\TextField('CODE',array(
				'title' => 'Код'
			)),
			new Entity\IntegerField('ACCESS',array(
				'required' => true,
				'default_value' => 0,
				'title' => 'Доступ'
			)),
			new Entity\IntegerField('SHOW_MODE',array(
				'required' => true,
				'default_value' => 1,
				'title' => 'Show mode'
			)),
			new Entity\StringField('LINKED_OBJECT',array(
				'title' => 'Связанный объект'
			)),
			new Entity\StringField('LINKED_PROPERTY',array(
				'title' => 'Связанное свойство'
			)),
			new Entity\IntegerField('CONDITION',array(
				'required' => true,
				'default_value' => 1,
				'title' => 'Условие'
			)),
			new Entity\StringField('CONDITION_VALUE',array(
				'title' => 'Значение условия'
			)),
			new Entity\IntegerField('PRIORITY',array(
				'required' => true,
				'default_value' => 1,
				'title' => 'Приоритет'
			))
		);
	}
}