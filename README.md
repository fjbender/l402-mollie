# L402 Protocol Server with Mollie

This is an implementation of the [L402 protocol](https://github.com/l402-protocol/l402) using PHP/Symfony with Mollie as the payment provider. The L402 protocol leverages HTTP's 402 Payment Required status code to enable machine-friendly payments on the internet.

## Features

- Complete L402 protocol implementation
- User management with API tokens
- Credit-based API access system
- Mollie payment integration
- Webhook handling for payment updates
- Simple API example with 402 payment flow

## Requirements

- PHP 8.1+
- Composer
- SQLite (or any other database supported by Doctrine)
- Mollie API key

## Installation

1. Clone the repository
2. Install dependencies:
    ```
    composer install
    ```
3. Configure your environment variables in `.env.local`:
    ```
    DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
    MOLLIE_API_KEY=your_mollie_api_key
    APP_SECRET=your_app_secret
    ```
4. Create the database and run migrations:
    ```
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    ```
5. Start the development server:
    ```
    symfony server:start
    ```

## How It Works

The L402 protocol flow is implemented as follows:

1. Client requests a protected resource
2. If the client has no credits, server responds with HTTP 402 and available offers
3. Client selects an offer and payment method
4. Client makes a payment request and receives payment details from Mollie
5. After payment, Mollie webhooks update the user's credit balance
6. Client can access the protected resource using their API token

## API Documentation

### Authentication

API requests require authentication using Bearer tokens:
```
Authorization: Bearer your_token
```

### Endpoints

- `GET /signup` - Create a new user account and get an authentication token
- `GET /info` - Get current user information including credit balance
- `GET /api/protected` - Protected resource requiring payment (L402)
- `POST /payment/request` - Process L402 payment requests

### L402 Flow Example

1. Create a user:
    ```
    curl https://your-server.com/signup
    ```

2. Try to access protected API:
    ```
    curl -H "Authorization: Bearer your_token" https://your-server.com/api/protected
    ```
    You'll get a 402 response with payment options.
   ```json
   {
     "version": "0.2.2",
     "payment_request_url": "https://your-server.com/payment/request",
     "payment_context_token": null,
     "offers": [
       {
         "id": "offer_1_credit",
         "title": "1 Credit Package",
         "description": "Purchase 1 credit for API access",
         "amount": 1,
         "currency": "EUR",
         "payment_methods": [
           "mollie"
         ],
         "credits": 1
       },
       {
         "id": "offer_5_credits",
         "title": "5 Credit Package",
         "description": "Purchase 5 credits for API access",
         "amount": 4.5,
         "currency": "EUR",
         "payment_methods": [
           "mollie"
         ],
         "credits": 5
       },
       {
         "id": "offer_10_credits",
         "title": "10 Credit Package",
         "description": "Purchase 10 credits for API access",
         "amount": 8,
         "currency": "EUR",
         "payment_methods": [
           "mollie"
         ],
         "credits": 10
       }
     ],
     "terms_url": "https://your-server.com/terms",
     "metadata": {
       "resource_id": "api_access",
       "client_note": "Payment required for API access"
     }
   }
   ```

4. Make a payment request:
    ```
    curl -X POST -H "Authorization: Bearer your_token" \
      -H "Content-Type: application/json" \
      -d '{"offer_id": "offer_1_credit", "payment_method": "mollie"}' \
      https://your-server.com/payment/request
    ```
   You'll see something like
   ```json
   {
     "version": "0.2.2",
     "payment_request": {
       "checkout_url": "https://www.mollie.com/checkout/select-method/5Pou5ogmjr?checkout_version=2.0"
     },
     "expires_at": "2025-05-07T10:13:56.373+00:00"
   }
   ```

5. Complete payment via the provided Mollie checkout URL

6. After payment, the API becomes accessible:
    ```
    curl -H "Authorization: Bearer your_token" https://your-server.com/api/protected
    ```

## Development

This project is designed as a demonstration of the L402 protocol. In a production environment, you would want to:

- Implement proper user authentication
- Add more payment methods
- Enhance security measures
- Add monitoring and logging
- Implement rate limiting

## License

This project is licensed under the MIT License.

## Acknowledgements

- [L402 Protocol](https://github.com/l402-protocol/l402)
- [Mollie API](https://docs.mollie.com/)
- [Symfony Framework](https://symfony.com/)
