<?php

    class Authentication{


        private Render $twig;
        private ?int $phone;
        private ?string $accountType;
        private ?int $accountId;
        private ?string $ipV4Address;
        private string $fingerprint;
        private int $maxVerificationCodeAttempts = 3;
        private int $maxResendAttempts = 3;

        function __construct($phone,$accountType, $accountId = -1) {
            $this->twig = new Render(ABSPATH.'views/authentication');
            $this->phone = $phone;
            $this->accountType = $accountType;
            $this->accountId = $accountId;
        }

        //RELATED METHODS
        //---------------------------------------------------------------------------------------------------------
        public static function convertPostToControllerAction (): string {

            if(empty($_POST))
                return 'error';

            if(array_key_exists('twoFactorCode',$_POST)){
                return 'addNewLoginLocation';
            }elseif(array_key_exists('getTemplate',$_POST)){
                return 'getTemplate';
            }elseif(array_key_exists('resendCode',$_POST)){
                return 'resendCode';
            }

            return 'login';

        }

        private function setFingerprint(int $verificationCode) : void{
            $this->fingerprint = Cryptography::encrypt($verificationCode);
        }

        public function getFormattedWaitTime(string $wait): string{
            $currentTimeStamp = date('Y-m-d H:i:s');
            $waitData = BaseClass::getDateDifference($currentTimeStamp,$wait);
            return 'Please wait '.$waitData->i.' minute(s) '.$waitData->s.' seconds before retrying.';
        }

        //---------------------------------------------------------------------------------------------------------



        //SESSION MANAGEMENT
        //---------------------------------------------------------------------------------------------------------
        private function getNewSessionExpires(): string{
            return date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s'). ' + 1 days'));
        }

        public static function getSessionEntryData($useFingerprint = null, $accountID = null):array{
            $currentIp = BaseClass::getIpv4();
            try{
                if($useFingerprint != null){
                    $query = 'SELECT * FROM `sessions` WHERE fingerprint=?';
                    $statement = SQLServices::makeCoreConnection()->prepare($query);
                    $statement->execute([$useFingerprint]);
                }else{
                    $query = 'SELECT * FROM `sessions` WHERE ip=? and accountId=?';
                    $statement = SQLServices::makeCoreConnection()->prepare($query);
                    $statement->execute([$currentIp,$accountID]);
                }
                if($statement->rowCount() == 0)
                    return array();

                return $statement->fetch(PDO::FETCH_ASSOC);
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to get session Entry data',
                    'exception' => $e
                ]);
                return array();
            }
        }

        public static function removeSessionById($id):void{
            SQLServices::makeCoreConnection()->prepare("DELETE FROM `sessions` WHERE id=? LIMIT 1")->execute([$id]);
        }

        private function updateSession (): bool {
            $expires = $this->getNewSessionExpires();
            try{
                SQLServices::makeCoreConnection()->prepare("UPDATE `sessions` SET `expires`=?,`fingerprint`=? WHERE `ip`=?")->execute(
                    [
                        $expires,
                        $this->fingerprint,
                        BaseClass::getIpv4()
                    ]);
                return true;
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to update user session',
                    'exception' => $e
                ]);
                return false;
            }
        }

        private function createSession (): bool {
            $sessionData = $this->getSessionEntryData(null,$this->accountId);
            if(empty($sessionData)) {
                try{
                    $smt = SQLServices::makeCoreConnection()->prepare("INSERT INTO `sessions` (`accountId`,`ip`,`fingerprint`,`device`,`expires`)VALUES(:accountId,:ip,:fingerprint,:device,:expires)");
                    $smt->execute(array(
                        ':accountId' => $this->accountId,
                        ':ip' => BaseClass::getIpv4(),
                        ':fingerprint' => $this->fingerprint,
                        ':device' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "UNKNOWN",
                        ':expires' => self::getNewSessionExpires(),
                    ));
                    return true;
                }catch(Exception $e){
                    if ($e->errorInfo[1] != 1062) {
                        BaseClass::logError([
                            'message' => 'Failed to insert into user session',
                            'exception' => $e
                        ]);
                        return false;
                    }
                    return true;
                }
            }else{
                $this->updateSession();
                return false;
            }
        }
        //---------------------------------------------------------------------------------------------------------



        //VERIFICATION MANAGEMENT
        //---------------------------------------------------------------------------------------------------------
        private function getVerificationCodeData() : array{
            try{
                $query = 'SELECT attempts,code,expires,requested,ip FROM `verificationCodes` WHERE accountId=? AND ip=?';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute([$this->accountId,BaseClass::getIpv4()]);
                if($statement->rowCount() == 0)
                    return array();

                return $statement->fetch(PDO::FETCH_ASSOC);
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to get verification code data',
                    'exception' => $e
                ]);
                return array();
            }
        }

        private function removeVerificationCode():void{
            SQLServices::makeCoreConnection()->prepare("DELETE FROM `verificationCodes` WHERE accountId=?")->execute([$this->accountId]);
        }

        private function removeVerificationCodeByIp():void{
            SQLServices::makeCoreConnection()->prepare("DELETE FROM `verificationCodes` WHERE ip=?")->execute([$this->ipV4Address]);
        }

        private function incrementVerificationAttempts (): void {
            try{
                SQLServices::makeCoreConnection()->prepare("UPDATE `verificationCodes` SET `attempts`=attempts+1 WHERE `accountId`=? and `ip`=?")->execute([$this->accountId,BaseClass::getIpv4()]);
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to increment Verification Attempts',
                    'exception' => $e
                ]);
            }
        }

        public function UpdateVerificationCode ($code): void {
            try{
                SQLServices::makeCoreConnection()->prepare("UPDATE `verificationCodes` SET `code`=?, `requested`=NOW() WHERE `accountId`=? AND `ip`=?")->execute([
                    Cryptography::encrypt($code),
                    $this->accountId,
                    BaseClass::getIpv4()
                ]);
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to update Verification code data',
                    'exception' => $e
                ]);
            }
        }

        private function sendVerificationCode() : bool{
            $this->ipV4Address = BaseClass::getIpv4();
            self::removeVerificationCode();
            //self::removeVerificationCodeByIp();
            $code = BaseClass::generateIntCode(6);
            try{
                $smt = SQLServices::makeCoreConnection()->prepare("INSERT INTO `verificationCodes` (`accountId`,`code`,`expires`,`ip`)VALUES(:accountId,:code,DATE_FORMAT( DATE_ADD(NOW(),INTERVAL 2 MINUTE),'%Y-%m-%d %H:%i:%s'),:ip)");
                $smt->execute(array(
                    ':accountId' => $this->accountId,
                    ':code' => Cryptography::encrypt($code),
                    ':ip' => $this->ipV4Address
                ));
                $smsControl = new SMSControl($this->phone,sprintf("Your %s verification code is %u",BUSINESS_NAME,$code));
                return $smsControl->sendSMS();
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to insert into verificationCodes',
                    'exception' => $e
                ]);
                return false;
            }
        }
        //---------------------------------------------------------------------------------------------------------



        //WAIT LIST MANAGEMENT
        //---------------------------------------------------------------------------------------------------------
        private function iswaitlist() : bool | string{

            try{
                $query = 'SELECT `expires` FROM `waitList` WHERE ip=? and accountId=?';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute([BaseClass::getIpv4(),$this->accountId]);
                if($statement->rowCount() > 0){
                    return $statement->fetch(PDO::FETCH_ASSOC)['expires'];
                }

                return false;
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to check if waitlist',
                    'exception' => $e
                ]);
                return false;
            }

        }

        private function removeFromWaitlist() : void {
            SQLServices::makeCoreConnection()->prepare("DELETE FROM `waitList` WHERE ip=? AND accountId=?")->execute([BaseClass::getIpv4(),$this->accountId]);
        }

        private function updateWaitlist ($expires): void {

            try{
                SQLServices::makeCoreConnection()->prepare("UPDATE `waitList` SET `expires`=? WHERE `accountId`=? and `ip`=?")->execute([$expires,$this->accountId,BaseClass::getIpv4()]);
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to update waitlist',
                    'exception' => $e
                ]);
            }
        }

        private function addToWaitlist (string $overrideExpires = null): void {
            try{
                if($overrideExpires==null){
                    $smt = SQLServices::makeCoreConnection()->prepare("INSERT INTO `waitList` (`ip`,`accountId`,`expires`)VALUES(:ip,:accountId,DATE_FORMAT( DATE_ADD(NOW(),INTERVAL 10 MINUTE),'%Y-%m-%d %H:%i:%s'))");
                    $smt->execute(array(
                        ':ip' => BaseClass::getIpv4(),
                        ':accountId' => $this->accountId
                    ));
                }else{
                    $smt = SQLServices::makeCoreConnection()->prepare("INSERT INTO `waitList` (`ip`,`accountId`,`expires`)VALUES(:ip,:accountId,:expires)");
                    $smt->execute(array(
                        ':ip' => BaseClass::getIpv4(),
                        ':accountId' => $this->accountId,
                        ':expires' => $overrideExpires
                    ));
                }
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to add to waitlist',
                    'exception' => $e
                ]);
            }
        }
        //---------------------------------------------------------------------------------------------------------



        //WAIT LIST MULTIPLIER
        //---------------------------------------------------------------------------------------------------------
        private function getWaitMultiplierData() : array{
            try{
                $query = 'SELECT accountId,ip,multiplier FROM `exponentialWaitList` WHERE accountId=? AND ip=?';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute([$this->accountId,BaseClass::getIpv4()]);
                if($statement->rowCount() == 0)
                    return array();

                return $statement->fetch(PDO::FETCH_ASSOC);
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to get exponentialWaitList data',
                    'exception' => $e
                ]);
                return array();
            }
        }

        private function removeWaitMultiplier():void{
            SQLServices::makeCoreConnection()->prepare("DELETE FROM `exponentialWaitList` WHERE accountId=? AND ip=?")->execute([$this->accountId,BaseClass::getIpv4()]);
        }

        private function incrementWaitMultiplier (): void {
            try{
                SQLServices::makeCoreConnection()->prepare("UPDATE `exponentialWaitList` SET `multiplier`=multiplier+1, last_updated=now() WHERE `accountId`=? and `ip`=?")->execute([$this->accountId,BaseClass::getIpv4()]);
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to increment Wait Multiplier',
                    'exception' => $e
                ]);
            }
        }

        private function addWaitMultiplier (): void {
            try{
                $smt = SQLServices::makeCoreConnection()->prepare("INSERT INTO `exponentialWaitList` (`ip`,`accountId`)VALUES(:ip,:accountId)");
                $smt->execute(array(
                    ':ip' => BaseClass::getIpv4(),
                    ':accountId' => $this->accountId
                ));
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to add exponentialWaitList',
                    'exception' => $e
                ]);
            }
        }
        //---------------------------------------------------------------------------------------------------------


        //LOGIN
        //---------------------------------------------------------------------------------------------------------
        public function login():array{

            //Check if account exists under phone number
            //$accountData return empty array if no account exists
            //-------------------------------------------------------
            $accountData = Account::getAccountDataByPhoneAndAccType($this->phone,$this->accountType);
            if(empty($accountData)){
                return [
                    'result' => false,
                    'response' => 'No account found under this phone number.'
                ];
            }
            $this->accountId = $accountData['accountId'];


            //Check if account is in good standing
            //-------------------------------------------------------
            if($accountData['status'] != 1)
                return [
                    'result' => false,
                    'response' => 'You are no longer authorized to use this site.',

                ];

            //Check if wait listed due to bad verification code attempts
            //-------------------------------------------------------
            $waitlistExpiration = $this->iswaitlist();
            if(!empty($waitlistExpiration)) {
                if($waitlistExpiration < date('Y-m-d H:i:s')){
                    $this->removeFromWaitlist();
                }else{
                    return [
                        'result' => false,
                        'response' => $this->getFormattedWaitTime($waitlistExpiration)
                    ];
                }
            }

            //Check verification was previously sent
            //-------------------------------------------------------
            $verificationCodeData = $this->getVerificationCodeData();
            if(!empty($verificationCodeData)){
                $currentTimeStamp = date('Y-m-d H:i:s');
                $futureRequestTime = date('Y-m-d H:i:s', strtotime($verificationCodeData['requested']. ' + 3 minutes'));
                if($futureRequestTime >= $currentTimeStamp){
                    return [
                        'result' => false,
                        'response' => $this->getFormattedWaitTime($futureRequestTime)
                    ];
                }else{
                    $this->removeVerificationCode();
                    $verificationCodeData = array();
                }

            }

            //send verification code
            //-------------------------------------------------------
            if(empty($verificationCodeData)){
                //verification code was never sent before
                if($this->sendVerificationCode()){
                    return [
                        'result' => true,
                        'response' => 'Please enter the code sent to your phone.',
                        'data' => [
                            'phone' => $this->phone
                        ],
                    ];
                }else {
                    return [
                        'result' => false,
                        'response' => 'System error occurred. Please try again later.'
                    ];
                }
            }else{
                //verification was sent before the user refreshed/left the login page
                $code = BaseClass::generateIntCode(6);
                $this->UpdateVerificationCode($code);
                return [
                    'result' => true,
                    'response' => 'Please enter the code sent to your phone.',
                    'data' => [
                        'phone' => $this->phone
                    ],
                ];
            }

        }
        //---------------------------------------------------------------------------------------------------------


        //VERIFY CODE
        //---------------------------------------------------------------------------------------------------------
        public function verify(int $code):array{



            //Check if wait listed due to bad verification code attempts
            //-------------------------------------------------------
            $waitlistExpiration = $this->iswaitlist();
            if(!empty($waitlistExpiration)) {
                if($waitlistExpiration < date('Y-m-d H:i:s')){
                    $this->removeFromWaitlist();
                }else{
                    return [
                        'result' => false,
                        'response' => $this->getFormattedWaitTime($waitlistExpiration)
                    ];
                }
            }

            //check if has verification code pending
            //-------------------------------------------------------
            $verificationCodeData = $this->getVerificationCodeData();
            if(empty($verificationCodeData))
                return [
                    'result' => false,
                    'returnToLogin' => true,
                    'response' => 'Let\'s try that again.'
                ];

            //check if verification code has expired
            //-------------------------------------------------------
            $currentTimeStamp = date('Y-m-d H:i:s');
            if($currentTimeStamp > $verificationCodeData['expires']){
                $this->removeVerificationCode();
                return [
                    'result' => false,
                    'returnToLogin' => true,
                    'response' => 'This code has expired. Please try logging in again.'
                ];

            }

            //check if has verification code matches
            //-------------------------------------------------------
            $serverCode = Cryptography::decrypt($verificationCodeData['code']);
            if($serverCode == $code){
                $accountData = Account::getAccountDataByPhoneAndAccType($this->phone,$this->accountType);
                //Check if account is in good standing
                //-------------------------------------------------------
                if($accountData['status'] != 1){
                    return [
                        'result' => false,
                        'returnToLogin' => true,
                        'response' => 'You are no longer authorized to use this site.',

                    ];
                }else{
                    //SUCCESSFUL CODE MATCH
                    $accountData = Account::getAccountDataByPhoneAndAccType($this->phone,$this->accountType);
                    $this->accountId = $accountData['accountId'];
                    $this->removeVerificationCode();
                    $this->removeWaitMultiplier();
                    $this->removeFromWaitlist();
                    $this->setFingerprint($serverCode);
                    $this->createSession();
                    return [
                        'result' => true,
                        'response' => 'Welcome back!' ,
                        'sessionData' => [
                            'ACCOUNT_ID' => $this->accountId,
                            'FINGERPRINT' => $serverCode
                        ]
                    ];
                }

            }else{
                //CODE DOES NOT MATCH
                $this->incrementVerificationAttempts();
                $verificationCodeData = $this->getVerificationCodeData();
                $multiplierData = $this->getWaitMultiplierData();

                $multiplier = empty($multiplierData['multiplier']) ? 1 : (int)$multiplierData['multiplier'];
                $totalAttempts = $this->maxVerificationCodeAttempts-$multiplier;
                $totalAttempts = $totalAttempts <= 0 ? 1 : $totalAttempts;

                if($verificationCodeData['attempts'] >= $totalAttempts){
                    $this->removeVerificationCode();
                    if(!empty($multiplierData)){
                        $this->incrementWaitMultiplier();
                    }else{
                        $this->addWaitMultiplier();
                    }
                    $minutes = 10 * $multiplier;
                    $expires = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s'). ' + '.$minutes.' minutes'));
                    $this->addToWaitlist($expires);
                    return [
                        'result' => false,
                        'returnToLogin' => true,
                        'response' => $this->getFormattedWaitTime($expires)
                    ];

                }else{
                    return [
                        'result' => false,
                        'returnToLogin' => false,
                        'response' => 'The code you entered is invalid. Please double check and try again.'
                    ];
                }


            }

        }
        //---------------------------------------------------------------------------------------------------------



        //RESEND CODE
        //---------------------------------------------------------------------------------------------------------
        public function resendVerificationCode() : array{
            $verificationCodeData = $this->getVerificationCodeData();

            if(empty($verificationCodeData)){
                return [
                    'result' => false,
                    'returnToLogin' => true,
                    'response' => 'Let\'s try that again.'
                ];
            }

            //Check if wait listed due to bad verification code attempts
            //-------------------------------------------------------
            $waitlistExpiration = $this->iswaitlist();
            if(!empty($waitlistExpiration)) {
                if($waitlistExpiration < date('Y-m-d H:i:s')){
                    $this->removeFromWaitlist();
                }else{
                    return [
                        'result' => false,
                        'returnToLogin' => true,
                        'response' => $this->getFormattedWaitTime($waitlistExpiration)
                    ];
                }
            }

            //check if verification code has expired
            //-------------------------------------------------------
            $currentTimeStamp = date('Y-m-d H:i:s');
            if($currentTimeStamp > $verificationCodeData['expires']){
                $this->removeVerificationCode();
                return [
                    'result' => false,
                    'returnToLogin' => true,
                    'response' => 'This session has expired. Please login again to continue.'
                ];

            }

            //check if user exceeded resend attempts
            //-------------------------------------------------------
            if($verificationCodeData['attempts'] >= $this->maxResendAttempts){
                $this->addToWaitlist();
                $this->removeVerificationCode();
                $waitlistExpiration = $this->iswaitlist();
                return [
                    'result' => false,
                    'returnToLogin' => false,
                    'response' => $this->getFormattedWaitTime($waitlistExpiration)
                ];
            }


            //ADD A 3 MINUTE WAIT TIME IF THIRD RESEND
            //-------------------------------------------------------
            $code = BaseClass::generateIntCode(6);
            if($verificationCodeData['attempts'] > 2){
                $currentTimeStamp = date('Y-m-d H:i:s');
                $futureRequestTime = date('Y-m-d H:i:s', strtotime($verificationCodeData['requested']. ' + 3 minutes'));
                if($futureRequestTime >= $currentTimeStamp){
                    return [
                        'result' => false,
                        'returnToLogin' => false,
                        'response' => $this->getFormattedWaitTime($futureRequestTime)
                    ];
                }
            }else{
                $smsControl = new SMSControl($this->phone,sprintf("Your %s verification code is %u",BUSINESS_NAME,$code));
                if(!$smsControl->sendSMS()){
                    return [
                        'result' => false,
                        'returnToLogin' => false,
                        'response' => 'An unknown error occurred. Please try again.'
                    ];
                }
            }
            $this->incrementVerificationAttempts();
            $this->UpdateVerificationCode($code);
            return [
                'result' => true,
                'returnToLogin' => false,
                'response' => 'Code resent successfully. Please enter within 5 minutes.'
            ];
        }
        //---------------------------------------------------------------------------------------------------------

        
    }

  