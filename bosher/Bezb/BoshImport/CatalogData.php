<?php
namespace Bezb\BoshImport;

class CatalogData {
    const IBLOCK_ID = 16;

    /**
     * @var array
     */
    static public $propertyMap = [];

    /**
     * @var array
     */
    static public $sectionMap = [];

    /**
     * @var array
     */
    static public $productMap = [];

    /**
     * @var array
     */
    static public $catalogMap = [];

    static public function buildPropertyMap() {
        $propertyMap = [];
        $dbProp = \CIBlockProperty::GetList(["ID" => "ASC"], ["ACTIVE"=>"Y", "IBLOCK_ID"=> self::IBLOCK_ID]);

        while($arProp = $dbProp->GetNext()) {
            $propertyMap[$arProp['CODE']] = $arProp['ID'];
        }

        self::$propertyMap = $propertyMap;
    }

    static public function buildSectionMap() {
        $sectionMap = [];
        $dbSection = \CIBlockSection::GetList(
            ["ID" => "ASC"],
            ["IBLOCK_ID"=> self::IBLOCK_ID],
            false,
            ['XML_ID', 'ID']
        );

        while($arSection = $dbSection->GetNext()) {
            $sectionMap[$arSection['XML_ID']] = $arSection['ID'];
        }

        self::$sectionMap = $sectionMap;
    }

    static public function buildProductMap() {
        $productMap = [];
        $dbElem = \CIBlockElement::GetList(
            ["ID" => "ASC"],
            ["IBLOCK_ID"=> self::IBLOCK_ID],
            false,
            false,
            ['XML_ID', 'ID']
        );

        while($arElement = $dbElem->GetNext()) {
            $productMap[$arElement['XML_ID']] = $arElement['ID'];
        }

        self::$productMap = $productMap;
    }

    static public function buildCatalogMap() {
        $catalogMap = [];
        $dbElem = \CCatalogProduct::GetList(
            ["ID" => "ASC"],
            ["ELEMENT_IBLOCK_ID"=> self::IBLOCK_ID],
            false,
            false,
            ['ID']
        );

        while($arElement = $dbElem->GetNext()) {
            $catalogMap[] = $arElement['ID'];
        }

        self::$catalogMap = $catalogMap;
    }
}