<?php declare(strict_types=1);


namespace App\Modules\Generic\Domain\Container;

use InvalidArgumentException;

class NotEnoughMoneyToRemove extends InvalidArgumentException
{
}