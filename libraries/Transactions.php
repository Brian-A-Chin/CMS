<?php


    class Transactions extends Plans {

        public string $status;
        public int $accountId;
        public int $id;
        //paypal or stripe
        public string $processor;
        //provided by 3rd party(stripe/paypal)
        public string $transactionId;

        public function __Construct($accountId,$processor,$transactionId,$planId,$status){
            $this->accountId = $accountId;
            $this->processor = $processor;
            $this->transactionId = $transactionId;
            $this->id = $planId;
            $this->status = $status;
            parent::__construct($planId);
        }

        public static function getPaymentInfo(int $accountId,string $processor) : array{
            $account = new Account($accountId);
            $paymentInfo = 'PayPal ('.$account->paypalEmail.')';
            $brand = '';
            $last4 = '';
            if($processor == 'STRIPE'){
                $paymentInfo = "";
                $customerId = $account->stripeCustomerId;
                $stripeHandler = new StripeHandler($customerId);
                $customer = $stripeHandler->getCustomerData();
                $defaultPm = $customer->invoice_settings->default_payment_method;
                if($defaultPm != null){
                    $stripeHandler->paymentMethod = $defaultPm;
                    $card = $stripeHandler->getPmData()->card;
                    $last4 = $card->last4;
                    $brand = $card->brand;
                    $paymentInfo = $card->brand.' card ***'.$card->last4;
                }else{
                    $paymentMethods = $stripeHandler->getAllCards();
                    if($paymentMethods->first() != null){
                        $card = $paymentMethods->first()->card;
                        $last4 = $card->last4;
                        $brand = $card->brand;
                        $paymentInfo = $card->brand.' card ***'.$card->last4;
                    }
                }
            }
            return [
                'brand' =>$brand,
                'last4' =>$last4,
                'display' => $paymentInfo

            ];
        }

        private function emailAboutTransaction(array $data = array()) : void{
            $currentDate = date("F j, Y");
            $account = new Account($this->accountId);
            $email = $account->email;
            $plan = new Plans($this->id);
            $planData = $plan->getPlanInfo();
            $portalUrl = SITE_URL.'account/login';
            $transactionalData = [];
            $headerLeft = '';
            $headerRight = '';
            $subject = '';
            $jumboText = '';
            $reason= '';
            $subscriptionEndDateData = Subscriptions::calculateEndDate($planData['duration']);

            switch ($this->status){
                case 'SUCCESS':
                    $paymentInfo = $this::getPaymentInfo($this->accountId, $this->processor);
                    $subject = 'Thank you for your payment '.$account->fullName;
                    $jumboText = 'THANK YOU';
                    $headerLeft = 'Thank you for your payment '.$account->fullName;
                    $headerRight = 'View payment details below';
                    $txtUnderReceipt = 'Payment received: '.$currentDate;
                    $transactionalData = [
                        [$planData['name'].'<br>Membership active until: '.$subscriptionEndDateData['date'],'$'.$this->price],
                        ['Total charged to '.$paymentInfo['display'], '$'.$this->price]
                    ];
                    break;
                case 'REFUND':
                    $subject = 'Hi '.$account->firstName.', we\'ve issued you a refund';
                    $jumboText = 'REFUND ISSUED';
                    $headerLeft = 'Your refund confirmation';
                    $headerRight = 'Account #'.$this->accountId;
                    $txtUnderReceipt = 'Refund Issued: '.$currentDate;
                    $paymentInfo = self::get($this->transactionId);
                    $transactionalData = [
                        [$planData['name'],'$'.$this->price],
                        ['Amount refunded to '.$paymentInfo['brand'].' ***'.$paymentInfo['last4'], '$'.$this->price],
                    ];
                    $reason = 'Please allow up to 7 business days for this transaction to show as reversed in your account.<br>Reason:<br>'.$data['notes'];
                    break;

            }

            Mailer::sendSingleEmail(
                '/transactional.twig',
                [
                    //TEMPLATE DATA
                    'firstName' => $account->firstName,
                    'fullName' => $account->fullName,
                    'jumboText' => $jumboText,
                    'headerLeft' => $headerLeft,
                    'headerRight' => $headerRight,
                    'email' => $email,
                    'phone' => $account->phone,
                    'transactionId' => $this->transactionId,
                    'txtUnderReceipt' => $txtUnderReceipt,
                    'currentDate' => $currentDate,
                    'transactionalData' => $transactionalData,
                    'reason' => $reason,
                    'helpText' => 'You can also view a history of your receipts by <a href="'.$portalUrl.'">logging into your members portal</a>.'
                ],
                $account->firstName.' '.$account->lastName,
                MailConfiguration::$RENEWAL_SENDER_ADDRESS,
                $subject,
                $email
            );
        }

        public function insert():int{
            try {
                $paymentInfo = $this::getPaymentInfo($this->accountId, $this->processor);
                $conn = SQLServices::makeCoreConnection();
                $smt = $conn->prepare("INSERT INTO `transactions` (`accountId`,`processor`,`transactionId`,`price`,`status`,`brand`,`last4`) VALUES (:accountId,:processor,:transactionId,:price,:status,:brand,:last4)");
                $smt->execute(array(
                    ":accountId" => $this->accountId,
                    ":processor" => $this->processor,
                    ":transactionId" => $this->transactionId,
                    ":price" => $this->price,
                    ":status" => $this->status,
                    ":brand" => $paymentInfo['brand'],
                    ":last4" => $paymentInfo['last4'],
                ));
                $this->transactionId = $conn->lastInsertId();
                $this->emailAboutTransaction();
                return $this->transactionId;
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to add transaction',
                    'exception' => $e
                ]);
                return -1;
            }
        }

        public static function get(int $id):array | bool{
            try {
                $query = 'SELECT *,DATE_FORMAT(updated, "%m/%d/%Y %I:%i %p") AS updatedNiceDate FROM transactions WHERE id=? LIMIT 1';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute([$id]);
                return $statement->rowCount() > 0 ? $statement->fetch(PDO::FETCH_ASSOC) : false;
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to get Account Email',
                    'exception' => $e
                ]);
                return false;
            }
        }

        public static function getCount($accountId):int{
            try {
                $query = 'SELECT COUNT(id) as transactionsCount FROM transactions WHERE accountId=?';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute([$accountId]);
                return $statement->fetch(PDO::FETCH_ASSOC)['transactionsCount'];
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to get Account Email',
                    'exception' => $e
                ]);
                return -1;
            }
        }

        public  function refund(string $notes):bool{
            $signedInAccountId = SessionManager::getAccountID();
            try{
                SQLServices::makeCoreConnection()->prepare("UPDATE `transactions` SET `refunded`=price,`status`='REFUNDED',`notes`=?, `updated`=NOW(), `updatedBy`=? WHERE `id`=?")->execute( [$notes,$signedInAccountId,$this->transactionId]);
                $this->emailAboutTransaction([
                    'notes'=>$notes
                ]);
                return true;
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to set account as complete',
                    'exception' => $e
                ]);
                return false;
            }
        }

    }