<?php
namespace Bezb\BoshImport;

class BoshImport {
    const PHOTO_PROPERTY = 'MORE_PHOTO';
    const ARTICLE_PROPERTY = 'ARTICLE';
    const RELATED_XML = 'ACCESSORIES_XML';
    const EAN_PROPERTY = 'EAN';
    const IMAGES_FOLDER = 'media';
    const PARTIAL_SIZE = 100;

    /**
     * @var string
     */
    protected $rootSectionName;

    /**
     * @var \XMLReader
     */
    protected $reader;

    /**
     * @var ImportState
     */
    protected $state;

    /**
     * @var CDatabase
     */
    protected $db;

    /**
     * @var int
     */
    protected $importCount;

    /**
     * @var bool
     */
    protected $isFullProductMap = false;

    /**
     * @var string
     */
    protected $imagePath;

    public function __construct($filename, ImportState $state, $db) {
        $this->db = $db;
        $this->state = $state;
        $this->reader = new \XMLReader();
        $this->reader->open($filename);
        $this->imagePath = dirname(__FILE__) . '/../../' . static::IMAGES_FOLDER;

        \CModule::IncludeModule('iblock');
        \CModule::IncludeModule('catalog');
    }

    public function parse() {
        $this->goToSection();

        if($this->state->getState() == ImportState::STATE_SECTION) {
            $this->db->Query('UPDATE b_iblock_section SET ACTIVE = "N" WHERE IBLOCK_ID = ' . CatalogData::IBLOCK_ID);
            $this->db->Query('UPDATE b_iblock_element SET ACTIVE = "N" WHERE IBLOCK_ID = ' . CatalogData::IBLOCK_ID);
            $this->parseSections();
            return;
        }

        if($this->state->getState() == ImportState::STATE_PRODUCT) {
            $this->parseProducts();
            return;
        }

        if($this->state->getState() == ImportState::STATE_ASSOC) {
            $this->parseProductsAssoc();

            return;
        }
    }

    public function parseSections() {
        CatalogData::buildSectionMap();

        $xml = $this->reader->readOuterXml();
        $sectionsXml = simplexml_load_string($xml);

        foreach($sectionsXml as $xmlNode) {
            $xmlId = (int) $xmlNode->GROUP_ID;

            $sectionId = null;
            if(isset(CatalogData::$sectionMap[$xmlId])) {
                $sectionId = CatalogData::$sectionMap[$xmlId];
            }

            if((int) $xmlNode->PARENT_ID == 0) {
                continue;
            }

            $sectionName = (string) $xmlNode->GROUP_NAME;

            if((int) $xmlNode->PARENT_ID == 1) {
                $parentId = 0;
                if($this->rootSectionName) {
                    $sectionName = $this->rootSectionName;
                }

            } elseif(isset(CatalogData::$sectionMap[(int) $xmlNode->PARENT_ID])) {
                $parentId = CatalogData::$sectionMap[(int) $xmlNode->PARENT_ID];
            }

            // Картинки товара
            $mainImage = null;
            if(isset($xmlNode->MIME_INFO)) {
                $images = [];

                foreach($xmlNode->MIME_INFO->MIME as $mimeXml) {
                    $images[] = \CFile::MakeFileArray($this->imagePath . '/' . (string)$mimeXml->MIME_SOURCE);
                }

                if($images) {
                    $mainImage = array_shift($images);
                }
            }

            $bs = new \CIBlockSection;
            $arFields = Array(
                "ACTIVE" => "Y",
                "XML_ID" => (int) $xmlNode->GROUP_ID,
                "CODE" => (int) $xmlNode->GROUP_ID,
                "IBLOCK_SECTION_ID" => $parentId,
                "IBLOCK_ID" => CatalogData::IBLOCK_ID,
                "NAME" => $sectionName,
                "SORT" => (int) $xmlNode->GROUP_ORDER
            );

            if($mainImage) {
                $arFields['PICTURE'] = $mainImage;
            }

            if($sectionId === null) {
                $sectionId = $bs->Add($arFields);

                if($sectionId) {
                    CatalogData::$sectionMap[$xmlId] = $sectionId;
                } else {
                    $this->state->addMessage("Ошибка при добавлении категории $xmlId: $bs->LAST_ERROR");
                }

            } else {
                $result = $bs->Update($sectionId, $arFields);

                if($result === false) {
                    $this->state->addMessage("Ошибка при обновлении категории #$sectionId: $bs->LAST_ERROR");
                }
            }

            $this->state->incrementSection();
        }

        $this->state->setState(ImportState::STATE_PRODUCT);
    }

    public function parseProducts() {
        $this->state->setState(ImportState::STATE_PRODUCT);

        for($i = 0; $i < $this->state->getProductPosition(); $i++) {
            $this->readNextNode();
        }

        CatalogData::buildPropertyMap();
        CatalogData::buildProductMap();
        CatalogData::buildCatalogMap();

        while($this->importCount < static::PARTIAL_SIZE) {
            $this->readNextNode();

            $xml = $this->reader->readOuterXml();
            $productXml = simplexml_load_string($xml);

            // Начался раздел привязок продуктов к категориям, выходим
            if(isset($productXml->ART_ID)) {
                $this->state->setState(ImportState::STATE_ASSOC);
                break;
            }

            $this->parseProduct($productXml);
        }
    }

    protected function readNextNode() {
        $this->reader->next();
        $this->reader->read();
    }

    protected function parseProduct($productXml) {
        $el = new \CIBlockElement;

        $xmlId = (string) $productXml->SUPPLIER_AID;

        $elementId = null;
        if(isset(CatalogData::$productMap[$xmlId])) {
            $elementId = CatalogData::$productMap[$xmlId];
        }

        //Свойства товара
        $properties = [];
        foreach($productXml->ARTICLE_FEATURES as $articleFeatures) {
            foreach($articleFeatures->FEATURE as $feature) {
                $valueCount = count($feature->FVALUE);
                $propertyId = $this->getPropertyId((string) $feature->FNAME, $valueCount > 1);

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
                    'VALUE' => \CFile::MakeFileArray($this->imagePath . '/' . (string)$mimeXml->MIME_SOURCE),
                    'DESCRIPTION' => (string) $mimeXml->MIME_DESCR,
                ];
            }

            if($images) {
                $firstImage = array_shift($images);
                $mainImage = $firstImage['VALUE'];
            }
        }

        if($images) {
            if($elementId === null) {
                $properties[static::PHOTO_PROPERTY] = $images;
            } else {
                $dbProp = \CIBlockElement::GetProperty(CatalogData::IBLOCK_ID, $elementId, [], ['CODE' => static::PHOTO_PROPERTY]);

                while($arProp = $dbProp->GetNext()) {
                    $images[$arProp['PROPERTY_VALUE_ID']] = [
                        'VALUE' => [
                            'MODULE_ID' => 'iblock',
                            'del' => 'Y'
                        ]
                    ];
                }

                \CIBlockElement::SetPropertyValueCode($elementId, static::PHOTO_PROPERTY, $images);
            }
        }

        $accessories = [];
        if(isset($productXml->ARTICLE_REFERENCE)) {
            foreach($productXml->ARTICLE_REFERENCE as $accessory) {
                $accessories[] = (string) $accessory->ART_ID_TO;
            }
        }

        if($accessories) {
            $properties[static::RELATED_XML] = $accessories;
        }

        $properties[self::EAN_PROPERTY] = (string) $productXml->ARTICLE_DETAILS->EAN;
        $properties[self::ARTICLE_PROPERTY] = (string) $productXml->SUPPLIER_AID;

        $arFields = [
            "ACTIVE"         => "Y",
            "IBLOCK_ID"      => CatalogData::IBLOCK_ID,
            "XML_ID" => (string) $productXml->SUPPLIER_AID,
            "IBLOCK_SECTION_ID" => false,
            "NAME"           => (string) $productXml->ARTICLE_DETAILS->DESCRIPTION_SHORT,
            "DETAIL_TEXT"    => (string) $productXml->ARTICLE_DETAILS->DESCRIPTION_LONG,
            "DETAIL_PICTURE" => $mainImage,
            "PREVIEW_PICTURE" => $mainImage,
            "PROPERTY_VALUES"=> $properties,
        ];

        if($elementId === null) {
            $elementId = $el->Add($arFields);

            if($elementId) {
                CatalogData::$productMap[$xmlId] = $elementId;
                $this->addCatalogProduct($elementId);
            } else {
                $this->state->addMessage("Ошибка при добавлении товара $xmlId: $el->LAST_ERROR");
            }

        } else {
            $res = $el->Update($elementId, $arFields);

            if(in_array($elementId, CatalogData::$catalogMap) === false) {
                $this->addCatalogProduct($elementId);
            }

            if($res === false) {
                $this->state->addMessage("Ошибка при обновлении товара #$elementId: $el->LAST_ERROR");
            }
        }

        $this->importCount++;
        $this->state->incrementProduct();
    }

    /**
     * @param $productId
     */
    protected function addCatalogProduct($productId) {
        $arFields = [
            'ID' => $productId,
            'QUANTITY' => 1,
        ];

        \CCatalogProduct::Add($arFields);
    }

    /**
     * @param $propertyName
     * @param $isMultiple
     * @return mixed
     */
    protected function getPropertyId($propertyName, $isMultiple) {
        $hash = 'p_' . substr(md5($propertyName), 0, 14);
        if(isset(CatalogData::$propertyMap[$hash])) {
            return CatalogData::$propertyMap[$hash];
        }

        $arFields = [
            "IBLOCK_ID" => CatalogData::IBLOCK_ID,
            "NAME" => $propertyName,
            "ACTIVE" => "Y",
            "SORT" => "100",
            "CODE" => $hash,
            "PROPERTY_TYPE" => "S",
            "MULTIPLE" => $isMultiple ? 'Y' : 'N'
        ];

        $ibp = new \CIBlockProperty;
        $propertyId = $ibp->Add($arFields);

        if($propertyId) {
            CatalogData::$propertyMap[$hash] = $propertyId;
        } else {
            $this->state->addMessage("Ошибка при добавлении свойства $hash: $ibp->LAST_ERROR");
        }

        return $propertyId;
    }

    public function parseProductsAssoc() {
        if(!count(CatalogData::$sectionMap)) {
            CatalogData::buildSectionMap();
        }

        CatalogData::buildProductMap();

        $skippedCount = $this->state->getAssocPosition() + $this->state->getProductPosition();
        for($i = 0; $i < $skippedCount; $i++) {
            $this->readNextNode();
        }

        while($this->importCount < static::PARTIAL_SIZE) {
            $this->readNextNode();
            $xml = $this->reader->readOuterXml();
            $assocXml = simplexml_load_string(trim($xml));

            if(!$assocXml) {
                $this->state->setState(ImportState::STATE_FINAL);
                break;
            }

            $this->parseProductAssoc($assocXml);
        }
    }

    public function parseProductAssoc($assocXml) {
        if(!isset(CatalogData::$sectionMap[(int) $assocXml->CATALOG_GROUP_ID])) {
            return;
        }

        if(!isset(CatalogData::$productMap[(string) $assocXml->ART_ID])) {
            return;
        }

        $sectionId = CatalogData::$sectionMap[(int) $assocXml->CATALOG_GROUP_ID];

        \CIBlockElement::SetElementSection(CatalogData::$productMap[(string) $assocXml->ART_ID], [$sectionId]);

        $this->importCount++;
        $this->state->incrementAssoc();
    }

    protected function goToSection() {
        $this->reader->next();

        //null
        $this->reader->read();
        //HEADER
        $this->reader->next();

        //null
        $this->reader->next();
        //T_NEW_CATALOG
        $this->reader->read();
        //null
        $this->reader->read();

        //CATALOG_GROUP_SYSTEM
        $this->reader->read();
    }
}