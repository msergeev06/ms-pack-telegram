<?php

// ---- SETUP ----
$packageName = "telegram";
// ---------------

use \MSergeev\Core\Lib\Config;
use \MSergeev\Core\Lib\Loader;

if (!Loader::IncludePackage('kuzmahome'))
{
	return;
}

$packageNameToUpper = strtoupper($packageName);
Config::addConfig($packageNameToUpper.'_ROOT',Config::getConfig('PACKAGES_ROOT').$packageName."/");
//Config::addConfig($packageNameToUpper.'_PUBLIC_ROOT',Config::getConfig('PUBLIC_ROOT').$packageName."/");
//Config::addConfig($packageNameToUpper.'_TOOLS_ROOT',str_replace(Config::getConfig("SITE_ROOT"),"",Config::getConfig('PACKAGES_ROOT').$packageName."/tools/"));

//***** Entity ********
Loader::includeFiles(Config::getConfig($packageNameToUpper.'_ROOT')."entity/");

//***** Tables ********
Loader::includeFiles(Config::getConfig($packageNameToUpper.'_ROOT')."tables/");

//***** Lib ********
Loader::includeFiles(Config::getConfig($packageNameToUpper.'_ROOT')."lib/");


global $USER;
if ($USER->getParam('KUZMA_USER_ID')!== false)
{
	$arRes = \MSergeev\Packages\Telegram\Tables\UsersTable::getList(
		array(
			'select' => array('ID'),
			'filter' => array('MEMBER_ID'=>$USER->getParam('KUZMA_USER_ID')),
			'limit' => 1
		)
	);
	if ($arRes && isset($arRes[0]))
	{
		$arRes = $arRes[0];
	}
	if ($arRes)
	{
		$USER->setParam('TELEGRAM_USER_ID',$arRes['ID']);
	}
}
