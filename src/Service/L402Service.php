<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\User;
use App\Repository\UserRepository;

class L402Service
{
    private const L402_VERSION = '0.2.2';
    private const JSON_OPTIONS = JSON_UNESCAPED_SLASHES;
    
    public function __construct(
        private readonly MolliePaymentService $molliePaymentService,
        private readonly UserRepository $userRepository
    ) {
    }
    
    public function generateL402Response(string $paymentRequestUrl, array $offers = [], ?string $paymentContextToken = null): array
    {
        return [
            'version' => self::L402_VERSION,
            'payment_request_url' => $paymentRequestUrl,
            'payment_context_token' => $paymentContextToken,
            'offers' => $offers,
            'terms_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/terms',
            'metadata' => [
                'resource_id' => 'api_access',
                'client_note' => 'Payment required for API access'
            ]
        ];
    }
    
    public function createOffer(string $id, string $title, string $description, float $amount, string $currency = 'EUR', int $credits = 1): array
    {
        return [
            'id' => $id,
            'title' => $title,
            'description' => $description,
            'amount' => $amount,
            'currency' => $currency,
            'payment_methods' => ['mollie'],
            'credits' => $credits
        ];
    }
    
    public function generatePaymentRequest(string $paymentContextToken, array $paymentData): ?array 
    {
        $offerId = $paymentData['offer_id'] ?? null;
        $paymentMethod = $paymentData['payment_method'] ?? null;
        
        // Currently we only support 'mollie' as payment method
        if (!$paymentMethod || $paymentMethod !== 'mollie') {
            return null;
        }
        
        $payment = $this->molliePaymentService->getPaymentByContextToken($paymentContextToken);
        
        if (!$payment) {
            return null;
        }
        
        return [
            'version' => self::L402_VERSION,
            'payment_request' => [
                'checkout_url' => $payment->getCheckoutUrl()
            ],
            'expires_at' => (new \DateTimeImmutable())->modify('+30 minutes')->format(\DateTimeInterface::RFC3339_EXTENDED)
        ];
    }
    
    public function createPayment(User $user, string $offerId, int $credits, float $amount): ?Payment
    {
        $description = "Purchase of $credits credits for API access";
        return $this->molliePaymentService->createPayment($user, $amount, $credits, $description);
    }
    
    public function findUserByToken(string $token): ?User
    {
        return $this->userRepository->findByToken($token);
    }

    public function saveUser(User $user): void
    {
        $this->userRepository->save($user);
    }
    
    public function createUser(?string $email = null, ?string $password = null): User
    {
        $user = new User();
        
        if ($email !== null) {
            $user->setEmail($email);
        } else {
            $user->setEmail('anonymous_' . uniqid() . '@example.com');
        }
        
        if ($password !== null) {
            // In a real application, you would hash the password
            $user->setPassword($password);
        } else {
            // Generate a random password for anonymous users
            $user->setPassword(bin2hex(random_bytes(16)));
        }
        
        // Start with 0 credits
        $user->setCredits(0);
        
        $this->userRepository->save($user);
        
        return $user;
    }

    /**
     * Encodes data to JSON with unescaped slashes
     */
    public function jsonEncode(array $data): string
    {
        return json_encode($data, self::JSON_OPTIONS);
    }
}