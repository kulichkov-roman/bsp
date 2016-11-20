<?php
namespace Bezb\BoshImport;

class AccessoriesImport extends BoshImport{
    const IMAGES_FOLDER = 'accessories';
    const PARTIAL_SIZE = 100;

    public function parse() {
        $this->rootSectionName = 'Принадлежности';
        $this->goToSection();

        if($this->state->getState() == ImportState::STATE_SECTION) {
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

    protected function parseProduct($productXml) {
        $el = new \CIBlockElement;

        $xmlId = (string) $productXml->SUPPLIER_AID;

        if(!isset(CatalogData::$productMap[$xmlId])) {
            return;
        }

        $elementId = CatalogData::$productMap[$xmlId];

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
            $dbProp = \CIBlockElement::GetProperty(CatalogData::IBLOCK_ID, $elementId, [], ['CODE' => self::PHOTO_PROPERTY]);

            while($arProp = $dbProp->GetNext()) {
                $images[$arProp['PROPERTY_VALUE_ID']] = [
                    'VALUE' => [
                        'MODULE_ID' => 'iblock',
                        'del' => 'Y'
                    ]
                ];
            }

            \CIBlockElement::SetPropertyValueCode($elementId, self::PHOTO_PROPERTY, $images);
        }

        $arFields = [
            "ACTIVE"         => "Y",
            "DETAIL_PICTURE" => $mainImage,
            "PREVIEW_PICTURE" => $mainImage,
        ];

        $res = $el->Update($elementId, $arFields);

        if(in_array($elementId, CatalogData::$catalogMap) === false) {
            $this->addCatalogProduct($elementId);
        }

        if($res === false) {
            $this->state->addMessage("Ошибка при обновлении товара #$elementId: $el->LAST_ERROR");
        }

        $this->importCount++;
        $this->state->incrementProduct();
    }
}