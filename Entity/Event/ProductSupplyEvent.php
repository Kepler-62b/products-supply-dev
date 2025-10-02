<?php

/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 *
 */

declare(strict_types=1);

namespace BaksDev\Products\Supply\Entity\Event;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Products\Supply\Entity\Event\Container\ProductSupplyContainer;
use BaksDev\Products\Supply\Entity\Event\Invariable\ProductSupplyInvariable;
use BaksDev\Products\Supply\Entity\Event\Modify\ProductSupplyModify;
use BaksDev\Products\Supply\Entity\ProductSupply;
use BaksDev\Products\Supply\Type\Event\ProductSupplyEventUid;
use BaksDev\Products\Supply\Type\ProductSupplyUid;
use BaksDev\Products\Supply\Type\Status\ProductSupplyStatus;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'product_supply_event')]
class ProductSupplyEvent extends EntityEvent
{
    /**
     * Идентификатор События
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: ProductSupplyEventUid::TYPE)]
    private ProductSupplyEventUid $id;

    /**
     * Идентификатор Service
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: ProductSupplyUid::TYPE, nullable: false)]
    private ProductSupplyUid $main;

    /** Статус заказа */
    #[Assert\NotBlank]
    #[ORM\Column(type: ProductSupplyStatus::TYPE)]
    private ProductSupplyStatus $status;

    /**
     * Продукт - Постоянная величина
     */
    #[ORM\OneToOne(targetEntity: ProductSupplyInvariable::class, mappedBy: 'event', cascade: ['all'], fetch: 'EAGER')]
    private ProductSupplyInvariable $invariable;

    /**
     * Постоянная величина
     */
    #[ORM\OneToOne(targetEntity: ProductSupplyContainer::class, mappedBy: 'event', cascade: ['all'], fetch: 'EAGER')]
    private ProductSupplyContainer $container;

    /**
     * Модификатор
     */
    #[ORM\OneToOne(targetEntity: ProductSupplyModify::class, mappedBy: 'event', cascade: ['all'], fetch: 'EAGER')]
    private ProductSupplyModify $modify;

    public function __construct()
    {
        $this->id = new ProductSupplyEventUid();
        $this->modify = new ProductSupplyModify($this);
    }

    public function __clone()
    {
        $this->id = clone new ProductSupplyEventUid();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    /**
     * Идентификатор события
     */
    public function getId(): ProductSupplyEventUid
    {
        return $this->id;
    }

    /**
     * Идентификатор корня
     */
    public function setMain(ProductSupplyUid|ProductSupply $main): void
    {
        $this->main = $main instanceof ProductSupply ? $main->getId() : $main;
    }

    public function getMain(): ?ProductSupplyUid
    {
        return $this->main;
    }

    public function getDto($dto): mixed
    {
        if($dto instanceof ProductSupplyEventInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof ProductSupplyEventInterface)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }
}