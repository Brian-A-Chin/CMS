<?php


    class StripeHandler extends \Stripe\StripeClient{

        public string $customerId;
        public ?string $paymentMethod;
        public function __Construct(string $customerId, ?string $paymentMethod = null){
            $this->customerId = $customerId;
            $this->paymentMethod = $paymentMethod;
            parent::__construct(StripeConfiguration::$SECRET_API_KEY);
        }

        public static function createCustomer(array $data) : string {
            \Stripe\Stripe::setApiKey(StripeConfiguration::$SECRET_API_KEY);
            $customer = \Stripe\Customer::create([
                'phone' => $data['phone'],
                'name'=>$data['name'],
                'email'=>$data['email'],
            ]);
            return $customer->id;
        }

        public static function createPaymentIntent(array $data, bool $isRenewing){
            \Stripe\Stripe::setApiKey(StripeConfiguration::$SECRET_API_KEY);

            if(!$isRenewing){
                // Alternatively, set up a webhook to listen for the payment_intent.succeeded event
                // and attach the PaymentMethod to a new Customer
                $customer = \Stripe\Customer::create([
                    'phone' => $data['phone'],
                    'name'=>$data['name'],
                    'email'=>$data['email'],
                ]);
                $customerId = $customer->id;
            }else{
                $accountId = SessionManager::getAccountID();
                $account = new Account($accountId);
                $customerId = $account->stripeCustomerId;
            }

            // Create a PaymentIntent with amount and currency
            $paymentIntent = \Stripe\PaymentIntent::create([
                'customer' => $customerId,
                'setup_future_usage' => 'off_session',
                'amount' =>$data['price']*100,
                'currency' => 'usd',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            $successQueryParams = [
                'type'=>'STRIPE',
                'key'=>urlencode(Cryptography::encrypt($data['key'])),
                'plan'=>urlencode(Cryptography::encrypt($data['planId'])),
                'phone'=>urlencode($data['phone']),
                'name'=>urlencode($data['name']),
                'email'=>urlencode($data['email']),
                'cusId'=>urlencode($customerId),
                'renew' =>urlencode($data['isRenewing']),
            ];

            if(!$data['isRenewing']){
                //extras
                $successQueryParams['facebook'] = urlencode($data['facebook']);
                $successQueryParams['diveExperience'] = urlencode($data['diveExperience']);
                $successQueryParams['divingCertification'] = urlencode($data['divingCertification']);
                $successQueryParams['shirtSize'] = urlencode($data['shirtSize']);
                $successQueryParams['ethics'] = urlencode($data['ethics']);
                $successQueryParams['liability'] = urlencode($data['liability']);
            }


            return [
                'clientSecret' => $paymentIntent->client_secret,
                'redirect'=>SITE_URL.'signup/success?'.http_build_query($successQueryParams)
            ];
        }

        public function getAllCards() : Stripe\Collection{
            return $this->paymentMethods->all(
                [
                    'customer' => $this->customerId,
                    'type' => 'card'
                ]);
        }

        public function getPmData() : \Stripe\PaymentMethod {
            return $this->paymentMethods->retrieve(
                $this->paymentMethod
            );
        }


        public function getTransactions(int $limit): Stripe\Collection {

            return $this->charges->all([
                'customer' => $this->customerId,
                'limit' => $limit
            ]);

        }

        public function getCustomerData() : Stripe\Customer{
            return $this->customers->retrieve($this->customerId);
        }

        public function deletePaymentMethod() : bool{

            $request = $this->paymentMethods->detach(
                $this->paymentMethod,
                []
            );

            return true;
        }

        public function updateDefaultPaymentMethod() : bool{

            $request = $this->customers->update(
                $this->customerId,
                ['invoice_settings'=>['default_payment_method' => $this->paymentMethod]]
            );

            return true;
        }

        public function refundTransaction() : bool{

            try {
                $this->refunds->create([
                    'payment_intent' => $this->paymentMethod,
                ]);
                return true;
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $err = $e;
                return false;
            }

        }


        //AUTOMATIC CHARGES
        //-----------------------------------------------------------------------------
        public static function capturePaymentIntent($customerId,$paymentMethod) : bool | \Stripe\PaymentIntent {
            \Stripe\Stripe::setApiKey(StripeConfiguration::$SECRET_API_KEY);

            try {
                return \Stripe\PaymentIntent::create([
                    'amount' => 1001,
                    'currency' => 'usd',
                    'customer' => $customerId,
                    'payment_method' => $paymentMethod,
                    'off_session' => true,
                    'confirm' => true,
                ]);

            } catch (Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to automatically charge card',
                    'exception' => 'Could not charge:'.$customerId
                ]);
                return false;
            }
        }


    }