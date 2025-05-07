<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\User;
use App\Repository\PaymentRepository;
use Mollie\Api\MollieApiClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MolliePaymentService
{
    private MollieApiClient $mollie;
    
    public function __construct(
        #[Autowire('%env(MOLLIE_API_KEY)%')] string $mollieApiKey,
        private readonly PaymentRepository $paymentRepository,
    ) {
        $this->mollie = new MollieApiClient();
        $this->mollie->setApiKey($mollieApiKey);
    }
    
    public function createPayment(User $user, float $amount, int $credits, string $description): Payment
    {
        $payment = new Payment();
        $payment->setUser($user);
        $payment->setAmount($amount);
        $payment->setCredits($credits);
        
        $molliePayment = $this->mollie->payments->create([
            'amount' => [
                'currency' => 'EUR',
                'value' => number_format($amount, 2, '.', ''),
            ],
            'description' => $description,
            'redirectUrl' => 'https://' . $_SERVER['HTTP_HOST'] . '/payment/return/' . $payment->getPaymentContextToken(),
            'webhookUrl' => 'https://' . $_SERVER['HTTP_HOST'] . '/webhook/mollie',
            'metadata' => [
                'payment_context_token' => $payment->getPaymentContextToken(),
                'user_id' => $user->getId(),
            ],
        ]);
        
        $payment->setMolliePaymentId($molliePayment->id);
        $payment->setCheckoutUrl($molliePayment->getCheckoutUrl());
        
        $this->paymentRepository->save($payment);
        
        return $payment;
    }
    
    public function processWebhook(string $molliePaymentId): void
    {
        $molliePayment = $this->mollie->payments->get($molliePaymentId);
        $payment = $this->paymentRepository->findByMollieId($molliePaymentId);
        
        if (!$payment) {
            return;
        }
        
        $newStatus = match ($molliePayment->status) {
            'paid' => Payment::STATUS_PAID,
            'failed' => Payment::STATUS_FAILED,
            'canceled' => Payment::STATUS_CANCELED,
            'expired' => Payment::STATUS_EXPIRED,
            default => $payment->getStatus(),
        };
        
        if ($newStatus !== $payment->getStatus()) {
            $payment->setStatus($newStatus);
            
            if ($newStatus === Payment::STATUS_PAID) {
                $user = $payment->getUser();
                $user->addCredits($payment->getCredits());
                $this->paymentRepository->save($payment);
            } else {
                $this->paymentRepository->save($payment);
            }
        }
    }
    
    public function getPaymentByContextToken(string $token): ?Payment
    {
        return $this->paymentRepository->findByPaymentContextToken($token);
    }
}