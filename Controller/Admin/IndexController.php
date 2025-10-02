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

namespace BaksDev\Products\Supply\Controller\Admin;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Products\Supply\Repository\AllProductSupply\AllProductSupplyInterface;
use BaksDev\Products\Supply\Type\Status\ProductSupplyStatus;
use BaksDev\Products\Supply\Type\Status\ProductSupplyStatus\Collection\ProductSupplyStatusCompleted;
use BaksDev\Products\Supply\Type\Status\ProductSupplyStatus\ProductSupplyStatusCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_PRODUCT_SUPPLY_INDEX')]
final class IndexController extends AbstractController
{
    /**
     * Управление заказами (Канбан)
     */
    #[Route('/admin/products/supply', name: 'admin.index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ProductSupplyStatusCollection $collection,
        AllProductSupplyInterface $allProductSupplyRepository,
    ): Response
    {
        /* Поиск */
        $search = new SearchDTO();
        $searchForm = $this->createForm(SearchForm::class, $search);
        $searchForm->handleRequest($request);

        // 41f73113-b15d-7846-9eda-6e44000cbfca

        $query = null;

        /** @var ProductSupplyStatus $status */
        foreach(ProductSupplyStatus::cases() as $status)
        {
            if($status->equals(ProductSupplyStatusCompleted::class))
            {
                $allProductSupplyRepository->setLimit(10);
            }

            $productSupply = $allProductSupplyRepository
                ->search($search)
                ->status($status)
                ->findAll();


            // Получаем список
            $query[$status->getProductSupplyStatusValue()] =
                (false !== $productSupply) ? iterator_to_array($productSupply) : null;
        }

        dump($query);

        return $this->render(
            [
                'query' => $query,
                'search' => $searchForm->createView(),
                'status' => $collection->cases(),
            ]
        );
    }
}
