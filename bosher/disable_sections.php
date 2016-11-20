<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once("autoload.php");

\CModule::IncludeModule('iblock');
global $DB;

\Bezb\BoshImport\CatalogData::buildProductMap();

$xmlProperty = \Bezb\BoshImport\BoshImport::RELATED_XML;
$endProperty = 'ASSOCIATED';

$dbRes = \CIBlockSection::GetList(
    ['ID' => 'ASC'],
    ['IBLOCK_ID' => 16],
    true, ['ID', 'ELEMENT_CNT']
);

while($arSection = $dbRes->GetNext()) {
    if($arSection['ELEMENT_CNT'] == 0) {
        $bs = new \CIBlockSection;
        $bs->Update($arSection['ID'], ['ACTIVE' => 'N']);
    }
}