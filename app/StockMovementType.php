<?php

namespace App;

enum StockMovementType: string
{
    case RESERVED = 'RESERVED';   // order received — stock committed
    case REVERTED = 'REVERTED';   // order cancelled/pending — revert reservation
    case DEDUCTED = 'DEDUCTED';   // order delivered — reduce committed
    case RETURNED = 'RETURNED';   // item returned — add back to available
    case DEFECTED = 'DEFECTED';   // returned defective — add to defected stock
}
