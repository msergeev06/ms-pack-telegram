<?php

namespace MSergeev\Packages\Telegram\Lib;

use MSergeev\Packages\Kuzmahome\Lib\Users as KuzmaUsers;
use MSergeev\Packages\Telegram\Tables;

class Users extends KuzmaUsers
{
	public static function getAuthUserParams ($arParams = array())
	{
		global $USER;

		if ($USER->getParam('TELEGRAM_USER_ID') !== false)
		{
			return self::getUserParams($USER->getParam('TELEGRAM_USER_ID'),$arParams);
		}
		else
		{
			return array();
		}
	}

	public static function getUserParams($userID, $arParams = array())
	{
		$arReturn = array();
		$arSelect = array();
		$kuzmaUserID = null;
		if (!empty($arParams))
		{
			$arMapArray = Tables\UsersTable::getMapArray();
			foreach ($arParams as $parameter)
			{
				$parameter = strtoupper($parameter);
				if ($parameter == 'ID')
				{
					continue;
				}
				if (isset($arMapArray[$parameter]))
				{
					$arSelect[] = $parameter;
				}
			}
		}

		$arList = array(
			'filter' => array(
				'ID' => $userID
			),
			'limit' => 1
		);
		if (!empty($arSelect))
		{
			if (!in_array('MEMBER_ID',$arSelect))
			{
				$arSelect[] = 'MEMBER_ID';
			}
			$arList['select'] = $arSelect;
		}

		$arRes = Tables\UsersTable::getList($arList);
		if ($arRes && isset($arRes[0]))
		{
			$arRes = $arRes[0];
		}
		if ($arRes)
		{
			$kuzmaUserID = $arRes['MEMBER_ID'];

			foreach ($arRes as $key=>$value)
			{
				$arReturn[$key] = $value;
			}
		}

		if (!is_null($kuzmaUserID))
		{
			$arKuzma = parent::getUserParams($kuzmaUserID, $arParams);
			if (!empty($arKuzma))
			{
				$arReturn = array_merge($arReturn,$arKuzma);
			}
		}

		return $arReturn;
	}

	public static function setUserParams ($userID, array $arParams)
	{
		$userID = intval($userID);
		if (isset($arParams) && !empty($arParams) && $userID > 0)
		{
			$arMapArray = Tables\UsersTable::getMapArray();
			$arUpdate = array();
			foreach ($arParams as $key=>$value)
			{
				if ($key == 'ID')
				{
					continue;
				}
				if (isset($arMapArray[$key]))
				{
					$arUpdate[$key] = $value;
				}
			}

			if (!empty($arUpdate))
			{
				Tables\UsersTable::update($userID,array("VALUES"=>$arUpdate));
			}

			$arRes = Tables\UsersTable::getList(
				array(
					'select' => array('MEMBER_ID'),
					'filter' => array('ID'=>intval($userID)),
					'limit' => 1
				)
			);
			if ($arRes && isset($arRes[0]))
			{
				$arRes = $arRes[0];
			}
			if ($arRes)
			{
				parent::setUserParams($arRes['MEMBER_ID'],$arParams);
			}
		}
	}
}