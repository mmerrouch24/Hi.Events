<?php

namespace HiEvents\DomainObjects\Enums;

enum PaymentProviders: string
{
    use BaseEnum;

    case STRIPE = 'STRIPE';
    case CMI = 'CMI';
    case OFFLINE = 'OFFLINE';
}
