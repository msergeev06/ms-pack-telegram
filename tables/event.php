<?php

namespace MSergeev\Packages\Telegram\Tables;

use MSergeev\Core\Lib\DataManager;
use MSergeev\Core\Entity;
use MSergeev\Core\Lib\TableHelper;

class EventTable extends DataManager
{
	public static function getTableName ()
	{
		return 'ms_telegram_event';
	}

	public static function getTableTitle ()
	{
		return 'События';
	}

	public static function getMap ()
	{
		return array(
			TableHelper::primaryField(),
			new Entity\StringField('TITLE',array(
				'required' => true,
				'title' => 'Название события'
			)),
			new Entity\TextField('DESCRIPTION',array(
				'title' => 'Описание события'
			)),
			new Entity\IntegerField('TYPE_EVENT',array(
				'required' => true,
				'size' => 3,
				'default_value' => 1,
				'title' => 'Тип события'
			)),
			new Entity\BooleanField('ENABLE',array(
				'required' => true,
				'default_value' => false,
				'title' => 'Флаг включенного события'
			)),
			new Entity\TextField('CODE',array(
				'title' => 'Код события'
			))
		);
	}
}