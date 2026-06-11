<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
    "NAME" => Loc::getMessage("CREATEORDERDEAL_DESCR_NAME"),
    "DESCRIPTION" => Loc::getMessage("CREATEORDERDEAL_DESCR_DESCR"),
    "TYPE" => "activity",
    "CLASS" => "CreateOrderDealActivity",
    "JSCLASS" => "BizProcActivity",
    "CATEGORY" => [
        "ID" => "other",
        "OWN_ID" => "otusedu",
        "OWN_NAME" => Loc::getMessage("CREATEORDERDEAL_DESCR_CATEGORY"),
    ],
    "RETURN" => [
        "DealId" => [
            "NAME" => Loc::getMessage("CREATEORDERDEAL_DESCR_FIELD_DEAL_ID"),
            "TYPE" => "int",
        ],
    ],
];
