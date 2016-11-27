<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once("autoload.php");

global $DB;

$requestParams = ['state', 'productPosition', 'assocPosition'];
$filename = 'files/accessories.xml';

$state = new \Bezb\BoshImport\ImportState();

// Формируем состояние импорта
foreach($requestParams as $field) {
    if(isset($_REQUEST[$field])) {
        $setter = 'set' . ucfirst($field);
        $state->$setter($_REQUEST[$field]);
    }
}

$importer = new \Bezb\BoshImport\AccessoriesImport(dirname(__FILE__) . '/' . $filename, $state, $DB);
$importer->parse();

echo json_encode([
    'state' => $state->getState(),
    'productPosition' => $state->getProductPosition(),
    'assocPosition' => $state->getAssocPosition(),
    'sectionCount' => $state->getSectionCount(),
    'messages' => $state->getMessages(),
]);
