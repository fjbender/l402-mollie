<?php

namespace App\Controller;

use App\Service\L402Service;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    public function __construct(
        private readonly L402Service $l402Service,
    ) {
    }
    
    #[Route('/signup', name: 'app_signup', methods: ['GET'])]
    public function signup(): JsonResponse
    {
        // Create a new anonymous user
        $user = $this->l402Service->createUser();
        
        // Return the user data including their token
        return $this->unescapedJson([
            'id' => $user->getToken(),
            'credits' => $user->getCredits(),
            'created_at' => $user->getCreatedAt()->format(\DateTimeInterface::RFC3339_EXTENDED),
            'last_credit_update_at' => $user->getLastCreditUpdateAt()->format(\DateTimeInterface::RFC3339_EXTENDED),
        ]);
    }
    
    #[Route('/info', name: 'app_info', methods: ['GET'])]
    public function info(Request $request): JsonResponse
    {
        $token = $this->extractToken($request);
        
        if (!$token) {
            return $this->unescapedJson(['error' => 'Authorization header missing'], Response::HTTP_UNAUTHORIZED);
        }
        
        $user = $this->l402Service->findUserByToken($token);
        
        if (!$user) {
            return $this->unescapedJson(['error' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
        }
        
        return $this->unescapedJson([
            'id' => $user->getToken(),
            'credits' => $user->getCredits(),
            'created_at' => $user->getCreatedAt()->format(\DateTimeInterface::RFC3339_EXTENDED),
            'last_credit_update_at' => $user->getLastCreditUpdateAt()->format(\DateTimeInterface::RFC3339_EXTENDED),
        ]);
    }
    
    #[Route('/api/protected', name: 'app_api_protected', methods: ['GET'])]
    public function protectedApi(Request $request): JsonResponse
    {
        $token = $this->extractToken($request);
        
        if (!$token) {
            return $this->unescapedJson(['error' => 'Authorization header missing'], Response::HTTP_UNAUTHORIZED);
        }
        
        $user = $this->l402Service->findUserByToken($token);
        
        if (!$user) {
            return $this->unescapedJson(['error' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
        }
        
        // Check if user has credits
        if ($user->getCredits() <= 0) {
            // Create payment offers
            $offers = [
                $this->l402Service->createOffer('offer_1_credit', '1 Credit Package', 'Purchase 1 credit for API access', 1.00, 'EUR', 1),
                $this->l402Service->createOffer('offer_5_credits', '5 Credit Package', 'Purchase 5 credits for API access', 4.50, 'EUR', 5),
                $this->l402Service->createOffer('offer_10_credits', '10 Credit Package', 'Purchase 10 credits for API access', 8.00, 'EUR', 10),
            ];
            
            // Generate L402 response with HTTP 402 Payment Required
            $l402Response = $this->l402Service->generateL402Response(
                'https://' . $request->getHttpHost() . '/payment/request',
                $offers
            );
            
            return $this->unescapedJson($l402Response, Response::HTTP_PAYMENT_REQUIRED);
        }
        
        // User has credits, decrement one credit and return the protected data
        $user->addCredits(-1); // Remove one credit
        $this->l402Service->saveUser($user); // Save the user with updated credits
        
        // Return sample data (in a real API, this would be actual data)
        return $this->unescapedJson([
            'message' => 'This is protected data accessed with L402 protocol',
            'remaining_credits' => $user->getCredits(),
            'data' => [
                'timestamp' => time(),
                'example_info' => 'Some valuable information'
            ]
        ]);
    }
    
    #[Route('/payment/request', name: 'app_payment_request', methods: ['POST'])]
    public function paymentRequest(Request $request): JsonResponse
    {
        $token = $this->extractToken($request);
        
        if (!$token) {
            return $this->unescapedJson(['error' => 'Authorization header missing'], Response::HTTP_UNAUTHORIZED);
        }
        
        $user = $this->l402Service->findUserByToken($token);
        
        if (!$user) {
            return $this->unescapedJson(['error' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
        }
        
        // Parse request body
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->unescapedJson(['error' => 'Invalid JSON request body'], Response::HTTP_BAD_REQUEST);
        }
        
        $offerId = $data['offer_id'] ?? null;
        $paymentMethod = $data['payment_method'] ?? null;
        
        if (!$offerId || !$paymentMethod) {
            return $this->unescapedJson(['error' => 'Missing offer_id or payment_method'], Response::HTTP_BAD_REQUEST);
        }
        
        // Currently we only support 'mollie' as payment method
        if ($paymentMethod !== 'mollie') {
            return $this->unescapedJson(['error' => 'Unsupported payment method'], Response::HTTP_BAD_REQUEST);
        }
        
        // Map offer_id to actual offer details
        $offerDetails = match($offerId) {
            'offer_1_credit' => ['credits' => 1, 'amount' => 1.00],
            'offer_5_credits' => ['credits' => 5, 'amount' => 4.50],
            'offer_10_credits' => ['credits' => 10, 'amount' => 8.00],
            default => null
        };
        
        if (!$offerDetails) {
            return $this->unescapedJson(['error' => 'Invalid offer ID'], Response::HTTP_BAD_REQUEST);
        }
        
        // Create payment
        $payment = $this->l402Service->createPayment(
            $user,
            $offerId,
            $offerDetails['credits'],
            $offerDetails['amount']
        );
        
        if (!$payment) {
            return $this->unescapedJson(['error' => 'Failed to create payment'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
        // Generate payment request response according to L402 protocol
        $paymentRequest = $this->l402Service->generatePaymentRequest(
            $payment->getPaymentContextToken(),
            $data
        );
        
        return $this->unescapedJson($paymentRequest);
    }
    
    private function extractToken(Request $request): ?string
    {
        $authHeader = $request->headers->get('Authorization');
        
        if (!$authHeader) {
            return null;
        }
        
        // Extract token from "Bearer <token>"
        if (strpos($authHeader, 'Bearer ') === 0) {
            return substr($authHeader, 7);
        }
        
        return null;
    }
    
    /**
     * Create a JsonResponse with unescaped slashes
     */
    protected function unescapedJson($data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES);
        return new JsonResponse($json, $status, $headers, true);
    }
}