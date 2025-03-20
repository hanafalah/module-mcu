<?php

namespace Hanafalah\ModuleMcu\Enums\McuPackage;

enum Flag: string
{
    case MAIN_PACKAGE       = 'MAIN_PACKAGE';
    case CATEGORY_PACKAGE   = 'CATEGORY';
    case SPESICIAL_PACKAGE  = 'SPECIAL_PACKAGE';
    case ADDITIONAL_PACKAGE = 'ADDITIONAL';
    case ITEM_PACKAGE       = 'ITEM';
    case POLI_PACKAGE       = 'POLI';
    case AGENT              = 'AGENT';
    case COMPANY            = 'COMPANY';
    case PAYER              = 'PAYER';
    case BOAT               = 'BOAT';
}
