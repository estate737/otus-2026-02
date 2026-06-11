<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
    "NAME" => Loc::getMessage("GETCOMPANYBYINN_DESCR_NAME"),
    "DESCRIPTION" => Loc::getMessage("GETCOMPANYBYINN_DESCR_DESCR"),
    "TYPE" => "activity",
    "CLASS" => "GetCompanyByInnActivity",
    "JSCLASS" => "BizProcActivity",
    "CATEGORY" => [
        "ID" => "other",
        "OWN_ID" => "otusedu",
        "OWN_NAME" => Loc::getMessage("GETCOMPANYBYINN_DESCR_CATEGORY"),
    ],
    "RETURN" => [
        "CompanyId" => [
            "NAME" => Loc::getMessage("GETCOMPANYBYINN_DESCR_FIELD_COMPANY_ID"),
            "TYPE" => "int",
        ],
        "CompanyName" => [
            "NAME" => Loc::getMessage("GETCOMPANYBYINN_DESCR_FIELD_COMPANY_NAME"),
            "TYPE" => "string",
        ],
    ],
];
