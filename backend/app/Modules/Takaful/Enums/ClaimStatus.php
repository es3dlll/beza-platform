<?php

declare(strict_types=1);

namespace Modules\Takaful\Enums;

enum ClaimStatus: string
{
    case Filed = 'filed';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Paid = 'paid';
}
