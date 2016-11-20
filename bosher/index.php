<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
error_reporting(E_ALL & ~E_NOTICE);
require_once("autoload.php");


$filename = 'Robert_Bosch_GmbH-BOEWMDB5-_1298_ru-RU_-BMEcat12r55-2016-01-20.xml';
$xmlReader = new \XMLReader();
$xmlReader->open($filename);

$xmlReader->next();

// null
$xmlReader->read();

// HEADER
$xmlReader->next();

// null
$xmlReader->next();

// T_NEW_CATALOG
$xmlReader->read();
// null
$xmlReader->read();

// CATALOG_GROUP_SYSTEM
$xmlReader->read();


for($i = 0; $i < 4251; $i++) {
    $xmlReader->next();
    $xmlReader->read();
}

$r = $xmlReader->readOuterXml();

$productXml = simplexml_load_string(trim($r));

//var_dump($productXml);
CModule::IncludeModule('iblock');
$dbProp = \CIBlockElement::GetProperty(16, 9882, [], ['CODE' => 'MORE_PHOTO']);
$images = [];
while($arProp = $dbProp->GetNext()) {
    $images[$arProp['PROPERTY_VALUE_ID']] = [
        'VALUE' => [
            'MODULE_ID' => 'iblock',
            'del' => 'Y'
        ]
    ];
}

\CIBlockElement::SetPropertyValueCode(9882, 'MORE_PHOTO', $images);
echo 'aaaaa';
exit;
/*foreach($productXml->ARTICLE_FEATURES as $articleFeatures) {
    foreach($articleFeatures->FEATURE as $feature) {
        foreach($feature->FVALUE as $value) {
            echo (string) $value;
            echo "<br>";
            echo"----";
            echo"<br>";
        }
       echo "<br>";
        echo"==================";
        echo"<br>";
    }
}*/
//var_dump($productXml);

$el = new CIBlockElement;

function getPropertyId($propertyName, $isMultiple) {
    $hash = 'p_' . substr(md5($propertyName), 0, 14);

    $arFields = [
        "IBLOCK_ID" => 16,
        "NAME" => $propertyName,
        "ACTIVE" => "Y",
        "SORT" => "100",
        "CODE" => $hash,
        "PROPERTY_TYPE" => "S",
        "MULTIPLE" => $isMultiple ? 'Y' : 'N'
    ];


    $ibp = new CIBlockProperty;
    $propertyId = $ibp->Add($arFields);

    return $propertyId;
}

//Свойства товара
$properties = [];
foreach($productXml->ARTICLE_FEATURES as $articleFeatures) {
    foreach($articleFeatures->FEATURE as $feature) {
        $valueCount = count($feature->FVALUE);
        $propertyId = getPropertyId((string) $feature->FNAME, $valueCount > 1);

        if($valueCount == 1) {
            $property = (string) $feature->FVALUE;

            if(isset($feature->FUNIT)) {
                $property .= ' ' . (string) $feature->FUNIT;
            }
        } else {
            $property = [];

            foreach($feature->FVALUE as $featureValue) {
                $property[] = (string) $featureValue;
            }
        }

        $properties[$propertyId] = $property;
    }
}

// Картинки товара
$images = [];
$mainImage = null;
if(isset($productXml->MIME_INFO)) {
    foreach($productXml->MIME_INFO->MIME as $mimeXml) {
        $images[] = [
            'VALUE' => CFile::MakeFileArray(dirname(__FILE__) . '/media/' . (string)$mimeXml->MIME_SOURCE),
            'DESCRIPTION' => (string) $mimeXml->MIME_DESCR,
        ];
    }

    if($images) {
        $firstImage = array_shift($images);
        $mainImage = $firstImage['VALUE'];
    }
}

if($images) {
    $properties['MORE_PHOTO'] = $images;
}


$arLoadProductArray = [
    "ACTIVE"         => "Y",
    "IBLOCK_ID"      => 16,
    "XML_ID" => (string) $productXml->SUPPLIER_AID,
    "IBLOCK_SECTION_ID" => false,
    "NAME"           => (string) $productXml->ARTICLE_DETAILS->DESCRIPTION_SHORT,
    "DETAIL_TEXT"    => (string) $productXml->ARTICLE_DETAILS->DESCRIPTION_LONG,
    "DETAIL_PICTURE" => $mainImage,
    "PREVIEW_PICTURE" => $mainImage,
    "PROPERTY_VALUES"=> $properties,
];
var_dump($arLoadProductArray);
if($PRODUCT_ID = $el->Add($arLoadProductArray))
    echo "New ID: ".$PRODUCT_ID;
else
    echo "Error: ".$el->LAST_ERROR;

exit;
//var_dump($sectionXml);

function convert($size)
{
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

echo convert(memory_get_usage(true)); // 123 kb

exit;
$addedSections = [];
foreach($sectionXml as $xmlNode) {
    if(isset($addedSections[(int) $xmlNode->PARENT_ID])) {
        $parentId = $addedSections[(int) $xmlNode->PARENT_ID];
    } else {
        $parentId = 0;
    }


    $bs = new CIBlockSection;
    $arFields = Array(
        "ACTIVE" => "Y",
        "XML_ID" => (int) $xmlNode->GROUP_ID,
        "CODE" => (int) $xmlNode->GROUP_ID,
        "IBLOCK_SECTION_ID" => $parentId,
        "IBLOCK_ID" => 16,
        "NAME" => (string) $xmlNode->GROUP_NAME,
        "SORT" => (int) $xmlNode->GROUP_ORDER
    );

    $sectionId = $bs->Add($arFields);
    if(!$sectionId > 0) {
        echo $bs->LAST_ERROR;
        exit;
    }

    $addedSections[(int) $xmlNode->GROUP_ID] = $sectionId;
}

unset($simpleXmlNode);


echo ' ';
echo convert(memory_get_usage(true)); // 123 kb

