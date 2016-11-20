<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once("autoload.php");
require_once("PHPExcel.php");

\CModule::IncludeModule("iblock");
\CModule::IncludeModule("catalog");

$dbRes = \CIBlockElement::GetList(
    ['ID' => 'ASC'],
    ['IBLOCK_ID' => 16],
    false,
    false,
    ['XML_ID', 'ID']
);

$count = 0;
while($arElement = $dbRes->GetNext()) {
    \CIBlockElement::SetPropertyValueCode($arElement['ID'], 'ARTICLE', $arElement['XML_ID']);
    $count++;
}

echo json_encode([
    'count' => $count
]);
exit;