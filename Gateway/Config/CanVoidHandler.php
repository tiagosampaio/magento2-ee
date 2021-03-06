<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Config;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection;

class CanVoidHandler implements ValueHandlerInterface
{
    private $filterBuilder;
    private $searchCriteriaBuilder;
    private $transactionRepository;

    public function __construct(
        ObjectManager $objectManager,
        Payment\Transaction\Repository $transactionRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder
    ) {
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Retrieve method configured value
     *
     * @param array $subject
     * @param int|null $storeId
     *
     * @return mixed
     */
    public function handle(array $subject, $storeId = null)
    {
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $subject['payment'];

        $order = $paymentDO->getOrder();

        $orderIdFilter = $this->filterBuilder->setField('order_id')
            ->setValue($order->getId())
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter($orderIdFilter)
            ->addSortOrder('order_id', 'ASC')
            ->create();

        /** @var Collection $transactionList */
        $transactionList = $this->transactionRepository->getList($searchCriteria);
        $transactions = $transactionList->getItems();
        foreach ($transactions as $item) {
            if ($item->getTxnType() == \Wirecard\PaymentSdk\Transaction\Transaction::TYPE_AUTHORIZATION) {
                return $paymentDO->getPayment()->getAmountPaid() !== $paymentDO->getPayment()->getAmountOrdered();
            }
        }

        return false;
    }
}
