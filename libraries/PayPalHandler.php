<?php


    class PayPalHandler {

        public static function generateAccessToken() : string{
            $url = PaypalConfiguration::$API_URL."/v1/oauth2/token";
            $data = 'grant_type=client_credentials';
            $headers = [
                'Content-Type: application/json',
                'Accept-Language: en_US'
            ];
            $options = array(
                CURLOPT_USERPWD => PaypalConfiguration::$CLIENT_ID.':'.PaypalConfiguration::$SECRET,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $data,
            );

            $curl = new CurlHandler($url,$headers,$options);
            $result = $curl->makeRequest();
            $json = json_decode($result);
            return $json->access_token;

        }

        public static function createOrder(array $data):string{
            $accessToken = self::generateAccessToken();
            $url = PaypalConfiguration::$API_URL."/v2/checkout/orders";
            $body = '{
              "intent": "CAPTURE",
              "purchase_units": [
                {
                  "amount": {
                    "currency_code": "USD",
                    "value": "'.$data['price'].'"
                  }
                }
              ],
              "payment_source": {
                "paypal": {
                  "experience_context": {
                    "payment_method_preference": "IMMEDIATE_PAYMENT_REQUIRED",
                    "payment_method_selected": "PAYPAL",
                    "brand_name":"'.FULLY_QUALIFIED_BUSINESS_NAME.'",
                    "locale": "en-US",
                    "landing_page": "LOGIN",
                    "shipping_preference": "NO_SHIPPING",
                    "user_action": "PAY_NOW",
                    "return_url": "https://example.com/returnUrl",
                    "cancel_url": "https://example.com/cancelUrl"
                  }
                }
              }
            }';
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer '.$accessToken,
                'PayPal-Request-Id:'.$data['requestId']
            ];
            $options = array(
                CURLOPT_USERPWD => PaypalConfiguration::$CLIENT_ID.':'.PaypalConfiguration::$SECRET,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
            );

            $curl = new CurlHandler($url,$headers,$options);
            return $curl->makeRequest();
        }

        public static function captureOrder(array $data){

            $accessToken = self::generateAccessToken();
            $url = PaypalConfiguration::$API_URL."/v2/checkout/orders/".$data['orderId']."/capture";
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer '.$accessToken,
                'PayPal-Request-Id:'.$data['requestId']
            ];
            $options = array(
                CURLOPT_USERPWD => PaypalConfiguration::$CLIENT_ID.':'.PaypalConfiguration::$SECRET,
                CURLOPT_POST => true,

            );

            $curl = new CurlHandler($url,$headers,$options);
            $result = $curl->makeRequest();
            $json = json_decode($result,true);

            $successQueryParams = [
                'type'=>'PAYPAL',
                'key' => urlencode($data['key']),
                'plan'=>urlencode(Cryptography::encrypt($data['planId'])),
                'phone'=>urlencode($data['phone']),
                'name'=>urlencode($data['name']),
                'email'=>urlencode($data['email']),
                'renew' => urlencode($data['isRenewing']),
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

            array_push($json, $json['internal'] = [
                'redirect'=>SITE_URL.'signup/success?'.http_build_query($successQueryParams)
            ]);

            return $json;

        }

        public static function refund(string $captureId,string $amount):stdClass{
            $accessToken = self::generateAccessToken();
            $url = PaypalConfiguration::$API_URL."/v2/payments/captures/".$captureId."/refund";
            $body = '{
              "amount": {
                "value": "'.$amount.'",
                "currency_code": "USD"
              }
            }';
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer '.$accessToken,
                'PayPal-Request-Id:'.BaseClass::generateAlphaNumericCode(7)
            ];
            $options = array(
                CURLOPT_USERPWD => PaypalConfiguration::$CLIENT_ID.':'.PaypalConfiguration::$SECRET,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
            );

            $curl = new CurlHandler($url,$headers,$options);
            return json_decode($curl->makeRequest());
        }

        //AUTOMATIC CHARGES
        //-----------------------------------------------------------------------------

        public static function createOrderIntent(array $data):string{
            $accessToken = self::generateAccessToken();
            $url = PaypalConfiguration::$API_URL."/v2/checkout/orders";
            $body = '{
              "intent": "AUTHORIZE",
              "purchase_units": [
                {
                  "amount": {
                    "currency_code": "USD",
                    "value": "'.$data['price'].'"
                  }
                }
              ],
              "payment_source": {
                "paypal": {
                  "experience_context": {
                    "payment_method_preference": "IMMEDIATE_PAYMENT_REQUIRED",
                    "payment_method_selected": "PAYPAL",
                    "brand_name":"'.FULLY_QUALIFIED_BUSINESS_NAME.'",
                    "locale": "en-US",
                    "landing_page": "LOGIN",
                    "shipping_preference": "SET_PROVIDED_ADDRESS",
                    "user_action": "PAY_NOW",
                    "return_url": "https://example.com/returnUrl",
                    "cancel_url": "https://example.com/cancelUrl"
                  }
                }
              }
            }';
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer '.$accessToken,
                'PayPal-Request-Id:'.$data['requestId']
            ];
            $options = array(
                CURLOPT_USERPWD => PaypalConfiguration::$CLIENT_ID.':'.PaypalConfiguration::$SECRET,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
            );

            $curl = new CurlHandler($url,$headers,$options);
            return $curl->makeRequest();
        }

        public static function approveTransaction(string $orderId):string{
            $accessToken = self::generateAccessToken();
            $url = PaypalConfiguration::$API_URL."/v2/checkout/orders/".$orderId;
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer '.$accessToken
            ];
            $options = array(
                CURLOPT_USERPWD => PaypalConfiguration::$CLIENT_ID.':'.PaypalConfiguration::$SECRET,
                CURLOPT_POST => false,
                CURLOPT_HTTPGET => true
            );

            $curl = new CurlHandler($url,$headers,$options);
            return $curl->makeRequest();
        }

        public static function authorizeTransaction(string $approvedId):string{
            $accessToken = self::generateAccessToken();
            $url = PaypalConfiguration::$API_URL."/v2/checkout/orders/".$approvedId.'/authorize';
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer '.$accessToken
            ];
            $options = array(
                CURLOPT_USERPWD => PaypalConfiguration::$CLIENT_ID.':'.PaypalConfiguration::$SECRET,
                CURLOPT_POST => true,
            );

            $curl = new CurlHandler($url,$headers,$options);
            return $curl->makeRequest();
        }

        public static function reAuthorizedTransactionById(string $id):void{
            $accessToken = self::generateAccessToken();
            $url = PaypalConfiguration::$API_URL."/v2/payments/authorizations/".$id."/reauthorize";
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer '.$accessToken,
                'PayPal-Request-Id:'.BaseClass::generateAlphaNumericCode(9)
            ];
            $options = array(
                CURLOPT_USERPWD => PaypalConfiguration::$CLIENT_ID.':'.PaypalConfiguration::$SECRET,
                CURLOPT_POST => true,
            );

            $curl = new CurlHandler($url,$headers,$options);
            $curl->makeRequest();
        }

        public static function captureAuthorizedTransactionById(int $accountId, string $id):bool | stdClass{
            $accessToken = self::generateAccessToken();
            $url = PaypalConfiguration::$API_URL."/v2/payments/authorizations/".$id."/capture";
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer '.$accessToken,
                'PayPal-Request-Id:'.BaseClass::generateAlphaNumericCode(9)
            ];
            $options = array(
                CURLOPT_USERPWD => PaypalConfiguration::$CLIENT_ID.':'.PaypalConfiguration::$SECRET,
                CURLOPT_POST => true,
            );

            $curl = new CurlHandler($url,$headers,$options);
            $response = json_decode($curl->makeRequest());
            try{
                if($response->status == 'COMPLETED' || (isset($response->name) && $response->name != 'RESOURCE_NOT_FOUND'))
                    return $response;

            } catch (Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to automatically charge paypal account:'.$accountId,
                    'exception' => $e
                ]);
            }

            BaseClass::logError([
                'message' => 'Failed to automatically charge paypal account',
                'exception' => 'account:'.$accountId
            ]);
            return false;

        }



    }