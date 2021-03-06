<?php declare(strict_types=1);


namespace Tests\Unit\app\Modules\Equipment\Domain;

use App\Modules\Character\Domain\CharacterId;
use App\Modules\Equipment\Domain\Inventory;
use App\Modules\Equipment\Domain\InventoryId;
use App\Modules\Equipment\Domain\InventoryItem;
use App\Modules\Equipment\Domain\Item;
use App\Modules\Equipment\Domain\ItemStatus;
use App\Modules\Equipment\Domain\Money;
use App\Modules\Generic\Domain\Container\NotEnoughSpaceInContainerException;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    /** @var InventoryId */
    private $id;

    /** @var CharacterId */
    private $characterId;

    /** @var Money */
    private $money;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = Mockery::mock(InventoryId::class);
        $this->characterId = Mockery::mock(CharacterId::class);
        $this->money = Mockery::mock(Money::class);
    }

    public function testWillThrowExceptionOnTryingToCreateInventoryWithNonInventoryItems(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Inventory(
            $this->id,
            $this->characterId,
            Collection::make([
                Mockery::mock(Item::class),
            ]),
            $this->money
        );
    }

    public function testWillThrowExceptionOnTryingToCreateInventoryWithTooManyItems(): void
    {
        $items = $this->generateItems(Inventory::NUMBER_OF_SLOTS + 1);

        $this->expectException(NotEnoughSpaceInContainerException::class);

        new Inventory(
            $this->id,
            $this->characterId,
            $items,
            $this->money
        );
    }

    public function testCreatingNewInventoryWithMaximumNumberOfItemsWorks(): void
    {
        $numberOfSlots = Inventory::NUMBER_OF_SLOTS;

        $items = $this->generateItems($numberOfSlots);

        $sut = new Inventory(
            $this->id,
            $this->characterId,
            $items,
            $this->money
        );

        $this->assertSame($numberOfSlots, $sut->getItems()->count());
    }

    public function testCreatingNewInventoryWithNoItemsWorks(): void
    {
        $sut = new Inventory(
            $this->id,
            $this->characterId,
            new Collection(),
            $this->money
        );

        $this->assertSame(0, $sut->getItems()->count());
    }

    public function testRightAmountOfMoneyAfterPuttingMoneyIn(): void
    {
        $initialMoney = new Money(5);

        $sut = new Inventory(
            $this->id,
            $this->characterId,
            $this->generateItems(5),
            $initialMoney
        );

        $this->assertSame(
            $initialMoney->getValue(),
            $sut->getMoney()->getValue()
        );
    }

    public function testRightAmountOfMoneyAfterPuttingMoreMoneyIn(): void
    {
        $initialMoney = new Money(5);
        $additionalMoney = new Money(4);

        $sut = new Inventory(
            $this->id,
            $this->characterId,
            $this->generateItems(5),
            $initialMoney
        );

        $sut->putMoneyIn($additionalMoney);

        $this->assertSame(
            $initialMoney->getValue() + $additionalMoney->getValue(),
            $sut->getMoney()->getValue()
        );
    }

    public function testRightAmountOfMoneyAfterTakingMoneyOut(): void
    {
        $initialMoney = new Money(5);
        $moneyToTakeOut = new Money(4);

        $sut = new Inventory(
            $this->id,
            $this->characterId,
            $this->generateItems(5),
            $initialMoney
        );

        $sut->takeMoneyOut($moneyToTakeOut);

        $this->assertSame(
            $initialMoney->getValue() - $moneyToTakeOut->getValue(),
            $sut->getMoney()->getValue()
        );
    }

    public function testAddingItemSequentially(): void
    {
        $initialNumberOfItems = $additionalItemOffset = 5;
        $additionalItem = Mockery::mock(Item::class);

        $sut = new Inventory(
            $this->id,
            $this->characterId,
            $this->generateItems($initialNumberOfItems),
            $this->money
        );

        $additionalItem->shouldReceive('getId')->atLeast()->once();
        $additionalItem->shouldReceive('getName')->atLeast()->once();
        $additionalItem->shouldReceive('getDescription')->atLeast()->once();
        $additionalItem->shouldReceive('getImageFilePath')->atLeast()->once();
        $additionalItem->shouldReceive('getType')->atLeast()->once();
        $additionalItem->shouldReceive('getEffects')->atLeast()->once();
        $additionalItem->shouldReceive('getPrice')->atLeast()->once();
        $additionalItem->shouldReceive('getPrototypeId')->atLeast()->once();
        $additionalItem->shouldReceive('getCreatorCharacterId')->atLeast()->once();

        $sut->add($additionalItem);

        $this->assertSame($initialNumberOfItems + 1, $sut->getItems()->count());
        $this->assertEquals(
            new InventoryItem($additionalItem, ItemStatus::inBackpack()),
            $sut->getItems()->offsetGet($additionalItemOffset)
        );
    }

    private function generateItems(int $numberOfItems): Collection
    {
        return Collection::make(array_map(static function () {

            return Mockery::mock(InventoryItem::class);

        }, range(0, $numberOfItems - 1)));
    }
}
