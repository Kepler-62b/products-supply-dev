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

namespace BaksDev\Products\Supply\Repository\AllProductSupply;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Products\Supply\Entity\Event\Container\ProductSupplyContainer;
use BaksDev\Products\Supply\Entity\Event\Invariable\ProductSupplyInvariable;
use BaksDev\Products\Supply\Entity\Event\ProductSupplyEvent;
use BaksDev\Products\Supply\Entity\ProductSupply;
use BaksDev\Products\Supply\Type\Status\ProductSupplyStatus;
use BaksDev\Products\Supply\Type\Status\ProductSupplyStatus\ProductSupplyStatusInterface;
use Generator;

class AllProductSupplyRepository implements AllProductSupplyInterface
{
    //    private UserProfileUid|false $profile = false;

    private SearchDTO|false $search = false;

    private ?ProductSupplyStatus $status = null;

    private ?int $limit = null;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        //        private readonly UserProfileTokenStorageInterface $UserProfileTokenStorage,
    ) {}

    public function search(SearchDTO $search): self
    {
        $this->search = $search;
        return $this;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }


    public function status(ProductSupplyStatus|ProductSupplyStatusInterface|string $status): self
    {
        $this->status = new ProductSupplyStatus($status);
        return $this;
    }

    //    public function filter(OrderFilterInterface $filter): self
    //    {
    //        $this->filter = $filter;
    //        return $this;
    //    }

    /** Фильтр по профилю */
    //    public function byProfile(UserProfileUid $profile): self
    //    {
    //        $this->profile = $profile;
    //        return $this;
    //    }

    public function findAll(): Generator|false
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->addSelect('product_supply.id')
            ->addSelect('product_supply.event')
            ->from(ProductSupply::class, 'product_supply');

        $status = $this->status instanceof ProductSupplyStatus;

        $dbal
            ->join(
                'product_supply',
                ProductSupplyEvent::class,
                'product_supply_event',
                'product_supply_event.id = product_supply.event'.
                ($status ? ' AND product_supply_event.status = :status' : '')
            );

        if($status)
        {
            $dbal->setParameter(
                key: 'status',
                value: $this->status,
                type: ProductSupplyStatus::TYPE,
            );
        }

        $dbal
            ->addSelect('product_supply_invariable.barcode AS supply_barcode')
            ->join(
                'product_supply_event',
                ProductSupplyInvariable::class,
                'product_supply_invariable',
                'product_supply_invariable.event = product_supply_event.id'
            );

        $dbal
            ->addSelect('product_supply_container.value AS supply_container')
            ->join(
                'product_supply_event',
                ProductSupplyContainer::class,
                'product_supply_container',
                'product_supply_container.event = product_supply_event.id'
            );

        if($this->search && $this->search->getQuery())
        {
            $dbal
                ->createSearchQueryBuilder($this->search)
                ->addSearchLike('product_supply_invariable.barcode')
                ->addSearchLike('product_supply_container.value');
        }

        if(null !== $this->limit)
        {
            $dbal->setMaxResults($this->limit);
        }

        $result = $dbal->fetchAllHydrate(AllProductSupplyResult::class);

        return true === $result->valid() ? $result : false;
    }
}