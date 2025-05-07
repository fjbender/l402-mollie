<?php

namespace App\Controller;

use App\Service\MolliePaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaymentController extends AbstractController
{
    public function __construct(
        private readonly MolliePaymentService $molliePaymentService,
    ) {
    }
    
    #[Route('/webhook/mollie', name: 'app_webhook_mollie', methods: ['POST'])]
    public function mollieWebhook(Request $request): Response
    {
        $molliePaymentId = $request->request->get('id');
        
        if (!$molliePaymentId) {
            return new Response('No payment ID received', Response::HTTP_BAD_REQUEST);
        }
        
        // Process the webhook notification
        $this->molliePaymentService->processWebhook($molliePaymentId);
        
        // Always respond with a 200 OK to Mollie
        return new Response('Webhook processed', Response::HTTP_OK);
    }
    
    #[Route('/payment/return/{token}', name: 'app_payment_return', methods: ['GET'])]
    public function paymentReturn(string $token): Response
    {
        $payment = $this->molliePaymentService->getPaymentByContextToken($token);
        
        if (!$payment) {
            return $this->render('payment/error.html.twig', [
                'error' => 'Payment not found'
            ]);
        }
        
        return $this->render('payment/return.html.twig', [
            'payment' => $payment,
            'status' => $payment->getStatus(),
        ]);
    }
}