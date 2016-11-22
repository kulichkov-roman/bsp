<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once("autoload.php");
require_once("PHPExcel.php");

\CModule::IncludeModule("iblock");
\CModule::IncludeModule("catalog");

function buildPriceArray($productId, $price) {
    return [
        "PRODUCT_ID" => $productId,
        "CATALOG_GROUP_ID" => 1,
        "PRICE" => $price,
        "CURRENCY" => "RUB",
    ];
}

$productMap = [];
$dbElem = \CIBlockElement::GetList(
    ["ID" => "ASC"],
    ["IBLOCK_ID"=> 16],
    false,
    false,
    ['XML_ID', 'ID']
);

while($arElement = $dbElem->Fetch()) {
    $productMap[$arElement['XML_ID']] = $arElement['ID'];
}

$filename = 'Pricelist_Blue.xlsx';
$excel = PHPExcel_IOFactory::load(dirname(__FILE__) . '/files/' . $filename);
$excel->setActiveSheetIndex(0);
$sheet = $excel->getActiveSheet();

$lastRow = $sheet->getHighestRow();
$count = 0;
for($i = 1; $i <= $lastRow; $i++) {
    $xmlId = $sheet->getCell("D$i")->getValue();

    if(!$xmlId) {
        continue;
    }

    if(!isset($productMap[$xmlId])) {
        continue;
    }

    $productId = $productMap[$xmlId];
    $price = str_replace(',', '.', $sheet->getCell("G$i")->getValue());
    $clearPrice = str_replace(',', '.', $sheet->getCell("H$i")->getValue());

    $arFields = [
        "PURCHASING_PRICE" => $clearPrice,
        "PURCHASING_CURRENCY" => "RUB"
    ];

    \CCatalogProduct::Update($productId, $arFields);

    $updated = false;
    $dbPrice = \CPrice::GetList([], ["PRODUCT_ID" => $productId]);
    while($arPrice = $dbPrice->Fetch()) {
        if($arPrice['CATALOG_GROUP_ID'] == 1) {
            $priceTypeId = $arPrice['CATALOG_GROUP_ID'];

            $arFields = buildPriceArray($productId, $price);
            \CPrice::Update($arPrice['ID'], $arFields);
            $updated = true;
        }
    }

    if($updated === false) {
        $arFields = buildPriceArray($productId, $price);
        $priceId = \CPrice::Add($arFields);
    }

    $count++;
}

//$count = 100;

echo json_encode([
   'count' => $count
]);
exit;
