<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once("autoload.php");
require_once("PHPExcel.php");

\CModule::IncludeModule("iblock");
\CModule::IncludeModule("catalog");

$arFields = [
    'IBLOCK_ID' => 16,
    'SMART_FILTER' => 'Y',
    "SECTION_PROPERTY" => "Y"
];

$dbRes = \CIBlockProperty::GetList(
    ['ID' => 'ASC'],
    ['IBLOCK_ID' => 16]
);

fputcsv($fp, ['ID', 'Название'], ';');
while($arProp = $dbRes->GetNext()) {
    $ibp = new \CIBlockProperty();
    $res = $ibp->Update($arProp['ID'], ['IBLOCK_ID' => 16, 'SMART_FILTER' => 'N']);
}

$fp = fopen('prop.csv', 'r');

while($str = fgetcsv($fp, null, ';')) {
    if(!is_numeric($str[0])) {
        continue;
    }

    $ibp = new \CIBlockProperty();
    $res = $ibp->Update($str[0], $arFields);
}

echo json_encode([
    'count' => $count
]);
exit;