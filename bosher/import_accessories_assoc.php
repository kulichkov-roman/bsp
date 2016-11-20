<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once("autoload.php");

\CModule::IncludeModule('iblock');
global $DB;

\Bezb\BoshImport\CatalogData::buildProductMap();

$xmlProperty = \Bezb\BoshImport\BoshImport::RELATED_XML;
$endProperty = 'ASSOCIATED';

$dbRes = \CIBlockElement::GetList(
    ['ID' => 'ASC'],
    ['IBLOCK_ID' => 16, 'SECTION_ID' => 2408, 'INCLUDE_SUBSECTIONS' => 'Y'],
    false, false, ['ID']
);

$resCount = 0;
while($arItem = $dbRes->GetNext()) {
    $propDb = \CIBlockElement::GetProperty(16, $arItem['ID'], array("sort" => "asc"), Array("CODE"=>$xmlProperty));

    $accessoriesIds = [];
    while($arProp = $propDb->Fetch()) {
        if(isset(\Bezb\BoshImport\CatalogData::$productMap[$arProp['VALUE']])) {
            $accessoriesIds[] = \Bezb\BoshImport\CatalogData::$productMap[$arProp['VALUE']];
        }
    }

    if($accessoriesIds) {
        \CIBlockElement::SetPropertyValues($arItem['ID'], 16, $accessoriesIds, $endProperty);
        $resCount++;
    }
}

echo json_encode([
   'count' => $resCount
]);
