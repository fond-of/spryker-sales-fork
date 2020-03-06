<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Sales\Persistence;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderItemFilterTransfer;
use Orm\Zed\Sales\Persistence\Map\SpySalesOrderItemTableMap;
use Orm\Zed\Sales\Persistence\Map\SpySalesOrderTableMap;
use Orm\Zed\Sales\Persistence\SpySalesOrderAddress;
use Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \Spryker\Zed\Sales\Persistence\SalesPersistenceFactory getFactory()
 */
class SalesRepository extends AbstractRepository implements SalesRepositoryInterface
{
    protected const ID_SALES_ORDER = 'id_sales_order';

    /**
     * @param string $customerReference
     * @param string $orderReference
     *
     * @return int|null
     */
    public function findCustomerOrderIdByOrderReference(string $customerReference, string $orderReference): ?int
    {
        $idSalesOrder = $this->getFactory()
            ->createSalesOrderQuery()
            ->filterByCustomerReference($customerReference)
            ->filterByOrderReference($orderReference)
            ->select([static::ID_SALES_ORDER])
            ->findOne();

        return $idSalesOrder;
    }

    /**
     * @param int $idOrderAddress
     *
     * @return \Generated\Shared\Transfer\AddressTransfer|null
     */
    public function findOrderAddressByIdOrderAddress(int $idOrderAddress): ?AddressTransfer
    {
        $addressEntity = $this->getFactory()
            ->createSalesOrderAddressQuery()
            ->leftJoinWithCountry()
            ->filterByIdSalesOrderAddress($idOrderAddress)
            ->findOne();

        if ($addressEntity === null) {
            return null;
        }

        return $this->hydrateAddressTransferFromEntity($this->createOrderAddressTransfer(), $addressEntity);
    }

    /**
     * @param \Generated\Shared\Transfer\OrderItemFilterTransfer $orderItemFilterTransfer
     *
     * @return \Generated\Shared\Transfer\ItemTransfer[]
     */
    public function getOrderItems(OrderItemFilterTransfer $orderItemFilterTransfer): array
    {
        $salesOrderItemQuery = $this->getFactory()
            ->createSalesOrderItemQuery()
            ->leftJoinWithOrder()
            ->leftJoinWithProcess()
            ->leftJoinWithState();

        $salesOrderItemQuery = $this->setOrderItemFilters($salesOrderItemQuery, $orderItemFilterTransfer);

        $salesOrderItemQuery = $this->buildQueryFromCriteria(
            $salesOrderItemQuery,
            $orderItemFilterTransfer->getFilter()
        );

        $salesOrderItemQuery->setFormatter(ModelCriteria::FORMAT_OBJECT);

        return $this->getFactory()
            ->createSalesOrderItemMapper()
            ->mapSalesOrderItemEntityCollectionToOrderItemTransfers($salesOrderItemQuery->find());
    }

    /**
     * @param int[] $salesOrderItemIds
     *
     * @return \Generated\Shared\Transfer\ItemStateTransfer[][]
     */
    public function getItemHistoryStatesByOrderItemIds(array $salesOrderItemIds): array
    {
        $omsOrderItemStateHistoryQuery = $this->getFactory()
            ->createOmsOrderItemStateHistoryQuery()
            ->filterByFkSalesOrderItem_In($salesOrderItemIds)
            ->leftJoinWithState()
            ->leftJoinOrderItem()
            ->groupByFkSalesOrderItem();

        return $this->getFactory()
            ->createSalesOrderItemMapper()
            ->mapOmsOrderItemStateHistoryEntityCollectionToItemStateHistoryTransfers($omsOrderItemStateHistoryQuery->find());
    }

    /**
     * @param int[] $salesOrderItemIds
     *
     * @return string[][]
     */
    public function getOrderReferencesByOrderItemIds(array $salesOrderItemIds): array
    {
        if (!$salesOrderItemIds) {
            return [];
        }

        return $this->getFactory()
            ->createSalesOrderItemQuery()
            ->filterByIdSalesOrderItem_In($salesOrderItemIds)
            ->leftJoinWithOrder()
            ->withColumn(SpySalesOrderItemTableMap::COL_ID_SALES_ORDER_ITEM, ItemTransfer::ID_SALES_ORDER_ITEM)
            ->withColumn(SpySalesOrderTableMap::COL_ORDER_REFERENCE, ItemTransfer::ORDER_REFERENCE)
            ->select([
                ItemTransfer::ID_SALES_ORDER_ITEM,
                ItemTransfer::ORDER_REFERENCE,
            ])
            ->find()
            ->toArray();
    }

    /**
     * @param \Generated\Shared\Transfer\AddressTransfer $addressTransfer
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderAddress $addressEntity
     *
     * @return \Generated\Shared\Transfer\AddressTransfer
     */
    protected function hydrateAddressTransferFromEntity(
        AddressTransfer $addressTransfer,
        SpySalesOrderAddress $addressEntity
    ): AddressTransfer {
        $addressTransfer->fromArray($addressEntity->toArray(), true);
        $addressTransfer->setIso2Code($addressEntity->getCountry()->getIso2Code());

        return $addressTransfer;
    }

    /**
     * @return \Generated\Shared\Transfer\AddressTransfer
     */
    protected function createOrderAddressTransfer(): AddressTransfer
    {
        return new AddressTransfer();
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery $salesOrderItemQuery
     * @param \Generated\Shared\Transfer\OrderItemFilterTransfer $orderItemFilterTransfer
     *
     * @return \Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery
     */
    protected function setOrderItemFilters(
        SpySalesOrderItemQuery $salesOrderItemQuery,
        OrderItemFilterTransfer $orderItemFilterTransfer
    ): SpySalesOrderItemQuery {
        if ($orderItemFilterTransfer->getSalesOrderItemIds()) {
            $salesOrderItemQuery->filterByIdSalesOrderItem_In(array_unique($orderItemFilterTransfer->getSalesOrderItemIds()));
        }

        if ($orderItemFilterTransfer->getSalesOrderItemUuids()) {
            $salesOrderItemQuery->filterByUuid_In(array_unique($orderItemFilterTransfer->getSalesOrderItemUuids()));
        }

        if ($orderItemFilterTransfer->getCustomerReference()) {
            $salesOrderItemQuery
                ->useOrderQuery()
                    ->filterByCustomerReference($orderItemFilterTransfer->getCustomerReference())
                ->endUse();
        }

        if ($orderItemFilterTransfer->getOrderReferences()) {
            $salesOrderItemQuery
                ->useOrderQuery()
                    ->filterByOrderReference_In(array_unique($orderItemFilterTransfer->getOrderReferences()))
                ->endUse();
        }

        return $salesOrderItemQuery;
    }
}
