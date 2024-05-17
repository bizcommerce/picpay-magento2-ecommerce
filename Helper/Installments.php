<?php

/**
 *
 *
 *
 *
 *
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    PicPay
 * @package     PicPay_Checkout
 *
 *
 */

namespace PicPay\Checkout\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Installments data helper, prepared for PicPay Transparent
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Installments extends AbstractHelper
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    public function __construct(
        Context $context,
        PriceCurrencyInterface $priceCurrency,
        Data $helper
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function getAllInstallments(float $total = 0): array
    {
        $allInstallments = [];
        try {
            if ($total > 0) {
                $minimumInstallments = (int) $this->helper->getConfig('min_installments') ?: 1;
                $maxInstallments = (int) $this->helper->getConfig('max_installments') ?: 1;
                $minInstallmentAmount = (float) $this->helper->getConfig('minimum_installment_amount');
                $hasInterest = (bool) $this->helper->getConfig('has_interest');
                $installmentsWithoutInterest = $this->getInstallmentsWithoutInterest();
                $interestType = $this->helper->getConfig('interest_type');
                $defaultInterestRate = (float) $this->helper->getConfig('interest_rate');

                $allInstallments = $this->getInstallments(
                    $allInstallments,
                    $minInstallmentAmount,
                    $total,
                    $maxInstallments,
                    $installmentsWithoutInterest,
                    $minimumInstallments,
                    $hasInterest,
                    $defaultInterestRate,
                    $interestType
                );
            }
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
        }

        return $allInstallments;
    }

    public function getInstallmentItem(
        int $installments,
        float $interestRate,
        float $value,
        float $total
    ): array {
        return [
            'installments' => $installments,
            'interest_rate' => $interestRate,
            'installment_price' => $value,
            'total' => $total,
            'formatted_installments_price' => $this->priceCurrency->format($value, false),
            'formatted_total' => $this->priceCurrency->format($total, false),
            'text' => $this->getInterestText(
                $installments,
                $value,
                $interestRate,
                $total
            )
        ];
    }

    public function getInterestText(
        int $installments,
        float $value,
        float $interestRate,
        float $grandTotal
    ): string {
        $interestText = __('%1x of %2 (%3). Total: %4');

        $interestExtra = __('without interest');
        if ($interestRate > 0) {
            $interestExtra = __('with interest');
        } elseif ($interestRate < 0) {
            $interestExtra = __('with discount');
        }

        return __(
            $interestText,
            $installments,
            $this->priceCurrency->format($value, false),
            $interestExtra,
            $this->priceCurrency->format($grandTotal, false)
        );
    }

    public function getInstallmentPrice(
        float $total,
        int $installment,
        bool $hasInterest,
        float $interestRate,
        string $interestType
    ): float {
        $installmentAmount = $total / $installment;
        try {
            if ($hasInterest && $interestRate > 0) {
                switch ($interestType) {
                    case 'price':
                        //Amortization with price table
                        $part1 = $interestRate * pow((1 + $interestRate), $installment);
                        $part2 = pow((1 + $interestRate), $installment) - 1;
                        $installmentAmount = round($total * ($part1 / $part2), 2);
                        break;
                    case 'compound':
                        //M = C * (1 + i)^n
                        $installmentAmount = ($total * pow(1 + $interestRate, $installment)) / $installment;
                        break;
                    case 'simple':
                        //M = C * ( 1 + ( i * n ) )
                        $installmentAmount = ($total * (1 + ($installment * $interestRate))) / $installment;
                        break;
                    case 'per_installments':
                        $installmentAmount = ($total * (1 + $interestRate)) / $installment;
                        break;
                }
            }
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
        }

        return round($installmentAmount, 2);
    }

    public function getInterestRateByInstallment(
        int $installment,
        float $interestRate = 0,
        string $interestType = '',
        int $installmentsWithoutInterest = 0
    ): float {
        if ($installment > $installmentsWithoutInterest) {
            if ($interestType == 'per_installments') {
                $interestRate = (float)$this->helper->getConfig('interest_' . $installment . '_installments');
            }
            return $interestRate / 100;
        }

        return 0;
    }

    public function getInstallmentsWithoutInterest(): int
    {
        return (int) $this->helper->getConfig('max_installments_without_interest') ?: 1;
    }

    public function getInstallments(
        array $allInstallments,
        float $minInstallmentAmount,
        float $total,
        int $maxInstallments,
        int $installmentsWithoutInterest,
        int $minimumInstallments,
        bool $hasInterest,
        float $defaultInterestRate,
        string $interestType
    ): array {
        if ($minInstallmentAmount > 0) {
            while ($maxInstallments > ($total / $minInstallmentAmount)) {
                $maxInstallments--;
            }

            while ($installmentsWithoutInterest > ($total / $minInstallmentAmount)) {
                $installmentsWithoutInterest--;
            }
        }

        $maxInstallments = ($maxInstallments == 0) ? 1 : $maxInstallments;
        for ($i = $minimumInstallments; $i <= $maxInstallments; $i++) {
            $interestRate = $this->getInterestRateByInstallment(
                $i,
                $defaultInterestRate,
                $interestType,
                $installmentsWithoutInterest
            );
            $value = $this->getInstallmentPrice($total, $i, $hasInterest, $interestRate, $interestType);
            $grandTotal = $total;
            if (!$hasInterest) {
                $interestRate = 0;
            } elseif ($interestRate > 0) {
                $grandTotal = round($value * $i, 2);
            }
            $allInstallments[] = $this->getInstallmentItem($i, $interestRate, $value, $grandTotal);
        }
        return $allInstallments;
    }

    protected function logError(string $message): void
    {
        $this->_logger->error($message);
    }
}
