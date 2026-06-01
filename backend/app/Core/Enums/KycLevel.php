<?php

namespace App\Core\Enums;

enum KycLevel: int
{
    case None = 0;
    case Basic = 1;
    case Full = 2;
}
