<?php
/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace SprykerFeature\Zed\Sales\Business;

use Generated\Shared\Transfer\CommentTransfer;
use Generated\Shared\Transfer\OrderItemsTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use SprykerEngine\Zed\Kernel\Business\AbstractFacade;
use SprykerEngine\Zed\Kernel\Locator;
use SprykerFeature\Shared\ZedRequest\Client\RequestInterface;
use SprykerEngine\Zed\Kernel\Business\ModelResult;
use SprykerFeature\Zed\Sales\Business\Model\OrderDetailsManager;
use SprykerFeature\Zed\Sales\SalesDependencyProvider;

/**
 * @method SalesDependencyContainer getDependencyContainer()
 */
class SalesFacade extends AbstractFacade
{

    /**
     * @deprecated
     * @param CommentTransfer $commentTransfer
     *
     * @return CommentTransfer
     */
    public function saveComment(CommentTransfer $commentTransfer)
    {
        $commentsManager = $this->getDependencyContainer()->createCommentsManager();
        $commentsManager->saveComment($commentTransfer);

        return $commentsManager->convertToTransfer();
    }

    /**
     * @param int $idOrder
     *
     * @return array
     */
    public function getArrayWithManualEvents($idOrder)
    {
        $orderManager = $this->getDependencyContainer()->createOrderDetailsManager();

        return $orderManager->getArrayWithManualEvents($idOrder);
    }

    /**
     * @deprecated
     * @param int $orderItemId
     *
     * @return array
     */
    public function getOrderItemManualEvents($orderItemId)
    {
        return $this->getDependencyContainer()
            ->getProvidedDependency(SalesDependencyProvider::FACADE_OMS)
            ->getManualEvents($orderItemId)
        ;
    }

    /**
     * @param int $orderItemId
     * @deprecated
     * @return OrderItemsTransfer
     */
    public function getOrderItemById($orderItemId)
    {
        return $this->getDependencyContainer()
            ->getProvidedDependency(SalesDependencyProvider::FACADE_OMS)
            ->getOrderItemById($orderItemId)
        ;
    }
    /**
     * @param OrderTransfer $transferOrder
     * @param RequestInterface $request
     * @deprecated
     * @return ModelResult
     */
    public function saveOrder(OrderTransfer $transferOrder, RequestInterface $request)
    {
        return $this->factory
            ->createModelOrderManager(Locator::getInstance(), $this->factory)
            ->saveOrder($transferOrder, $request);
    }
}
