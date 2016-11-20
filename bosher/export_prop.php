<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once("autoload.php");

\CModule::IncludeModule('iblock');
global $DB;

\Bezb\BoshImport\CatalogData::buildProductMap();

$xmlProperty = \Bezb\BoshImport\BoshImport::RELATED_XML;
$endProperty = 'ASSOCIATED';

$dbRes = \CIBlockProperty::GetList(
    ['ID' => 'ASC'],
    ['IBLOCK_ID' => 16]
);

$fp = fopen("prop.csv", "w+");
fputcsv($fp, ['ID', 'Название'], ';');
while($arProp = $dbRes->GetNext()) {
    fputcsv($fp, [$arProp['ID'], $arProp['NAME']], ';');
}
fclose($fp);