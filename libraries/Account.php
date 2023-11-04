<?php

class Account extends ContactRecords {

    public int $accountId;
    public string $email;
    public bool $complete;
    public int $status;
    public string $accountType;
    public int $permissionGroupId;
    public string $joinDate;
    public int $phone;
    //null for when creating an admin account
    public ?string $stripeCustomerId;
    public ?string $paypalEmail;

    public function __Construct($accountId){
        $this->accountId = $accountId;
        $this->set();
        parent::__construct($accountId);
    }

    private function set() : void {
        try {
            $query = 'SELECT * FROM accounts WHERE accountId=? LIMIT 1';
            $statement = SQLServices::makeCoreConnection()->prepare($query);
            $statement->execute([$this->accountId]);
            $data = $statement->fetch(PDO::FETCH_ASSOC);
            $this->email = Cryptography::decrypt($data['email']);
            $this->complete = $data['complete'];
            $this->status = $data['status'];
            $this->accountType = $data['accountType'];
            $this->permissionGroupId = $data['permissionGroupId'];
            $this->joinDate = $data['joinDate'];
            $this->phone = Cryptography::decrypt($data['phone']);
            $this->stripeCustomerId = $data['stripeCustomerId'];
            $this->paypalEmail = $data['paypalEmail'];
        } catch (Exception $e) {
            BaseClass::logError([
                'message' => 'Failed to get account data',
                'exception' => $e
            ]);
        }
    }

    private function emailAboutChange(array $data) : void{
        $email = $this->email;
        $bodyText = [];
        foreach ($data['changes'] as $change) {
            $bodyText[] = '<span style="margin-left: 25px;"> - '.$change.'</span>';
        }
        $bodyText[] = 'If this change was not requested by you, please contact us and let us know.';

        Mailer::sendSingleEmail(
            '/generic.twig',
            [
                //TEMPLATE DATA
                'firstName' => $this->firstName,
                'fullName' => $this->fullName,
                'headerLeft' => $data['headerLeft'],
                'headerRight' => $this->fullName.' | Account #'.$this->accountId,
                'jumboTitle' => 'Changes to your account',
                'intro' => 'Hey '.$this->firstName.', we\'ve noticed that you\'ve recently made the following changes to your account.',
                'body' => implode('<br><br>',$bodyText),
                'buttonLink' => '',
                'buttonText' => '',
                'signature' => 'Thanks!<br>BUSINESS_NAME_HIDDEN'
            ],
            $this->firstName.' '.$this->lastName,
            MailConfiguration::$MEMBERSHIP_UPDATE_SENDER_ADDRESS,
            'Changes to your account',
            $email
        );
    }

    public static function add(array $data): int{
        try {
            $conn = SQLServices::makeCoreConnection();
            $smt = $conn->prepare("INSERT INTO `accounts` (`email`,`phone`,`permissionGroupId`,`accountType`,`paypalEmail`,`stripeCustomerId`) VALUES (:email,:phone,:permissionGroupId,:accountType,:paypalEmail,:stripeCustomerId)");
            $smt->execute(array(
                "email" => Cryptography::encrypt($data['email']),
                "phone" => Cryptography::encrypt($data['phone']),
                "permissionGroupId" => $data['permissionGroupId'],
                "accountType" =>  $data['accountType'],
                "paypalEmail" => $data['paypalEmail'],
                "stripeCustomerId" => $data['stripeCustomerId'],
            ));
            return $conn->lastInsertId();
        } catch (Exception $e) {
            BaseClass::logError([
                'message' => 'Failed to register User',
                'exception' => $e
            ]);
            return -1;
        }
    }

    public static function addSettingsEntry(string $accountId): bool{
        try {
            $smt = SQLServices::makeCoreConnection()->prepare("INSERT INTO `accountSettings` (`accountId`) VALUES (:accountId)");
            $smt->execute(array(
                ":accountId" => $accountId,
            ));
            return true;
        } catch (Exception $e) {
            BaseClass::logError([
                'message' => 'Failed to add account setting',
                'exception' => $e
            ]);
            return false;
        }
    }

    public function updateLoginInfo() : bool{
        try {
            $smt = SQLServices::makeCoreConnection()->prepare("UPDATE `accounts` SET email=:email,phone=:phone WHERE accountId=:accountId");
            $smt->execute(array(
                ":email" => Cryptography::encrypt($this->email),
                ":phone" =>  Cryptography::encrypt($this->phone),
                ":accountId" =>  $this->accountId
            ));
            return true;
        } catch (Exception $e) {
            BaseClass::logError([
                'message' => 'Failed to update account login info',
                'exception' => $e
            ]);
            return false;
        }
    }

    public function updateStatus(int $status) : int | bool{
        try {
            $smt = SQLServices::makeCoreConnection()->prepare("UPDATE `accounts` SET status=:status WHERE accountId=:accountId");
            $smt->execute(array(
                ":status" => $status,
                ":accountId" =>  $this->accountId
            ));
            return true;
        } catch (Exception $e) {
            BaseClass::logError([
                'message' => 'Failed to update account status',
                'exception' => $e
            ]);
            return false;
        }
    }

    public function updatePermissionGroup (int $permissionGroupId ): bool
    {
        try{
            $smt = SQLServices::makeCoreConnection()->prepare("UPDATE `accounts` SET `permissionGroupId`=:permissionGroupId WHERE `accountId`=:accountId LIMIT 1");
            $smt->execute([
                ':permissionGroupId' => $permissionGroupId,
                ':accountId' => $this->accountId,
            ]);
            return true;
        }catch(Exception $e){
            BaseClass::logError([
                'message' => 'Failed to update Account privileges Group',
                'exception' => $e
            ]);
            return false;
        }
    }

    public static function getAccountDataByPhoneAndAccType($phone,$type) : array{
        try {
            $phone = Cryptography::encrypt($phone);
            $query = 'SELECT accountId,status FROM accounts WHERE phone=? AND accountType=?';
            $statement = SQLServices::makeCoreConnection()->prepare($query);
            $statement->execute([$phone,$type]);
            if($statement->rowCount() > 0){
                return $statement->fetch(PDO::FETCH_ASSOC);
            }else{
                return array();
            }
        } catch (Exception $e) {
            BaseClass::logError([
                'message' => 'Failed to get AccountID',
                'exception' => $e
            ]);
            return array();
        }
    }

    public static function getAccountDataByEmailAndAccType($email,$type) : array{
        try {
            $email = Cryptography::encrypt($email);
            $query = 'SELECT accountId,status FROM accounts WHERE email=? AND accountType=?';
            $statement = SQLServices::makeCoreConnection()->prepare($query);
            $statement->execute([$email,$type]);
            if($statement->rowCount() > 0){
                return $statement->fetch(PDO::FETCH_ASSOC);
            }else{
                return array();
            }
        } catch (Exception $e) {
            BaseClass::logError([
                'message' => 'Failed to get AccountID',
                'exception' => $e
            ]);
            return array();
        }
    }

    public static function getDirectory($accountId): string {
        return Filters::alphaNumericFilter($accountId);
    }

    public function requireFullAccountSetup(): void{
        if(!$this->complete) {
            header("Location: /Account/Setup");
            exit;
        }
    }

    public function completeSetup(): bool {
        try{
            SQLServices::makeCoreConnection()->prepare("UPDATE `accounts` SET `complete`=1 WHERE `accountId`=?")->execute( [$this->accountId]);
            return true;
        }catch(Exception $e){
            BaseClass::logError([
                'message' => 'Failed to set account as complete',
                'exception' => $e
            ]);
            return false;
        }
    }

    public function updateEmail($email): bool {
        try {
            $smt = SQLServices::makeCoreConnection()->prepare("UPDATE `accounts` SET email=:email WHERE accountId=:accountId");
            $smt->execute(array(
                ":email" => Cryptography::encrypt($email),
                ":accountId" => $this->accountId
            ));
            //notify old email
            $this->emailAboutChange([
                'headerLeft' => 'Primary Email Updated Successfully',
                'changes' => [
                    'Updated primary email address to: '.$email
                ],
            ]);
            $this->email = $email;
            //email new account
            $this->emailAboutChange([
                'headerLeft' => 'Primary Email Updated Successfully',
                'changes' => [
                    'Updated primary email address'
                ],
            ]);

            return true;
        } catch (Exception $e) {
            BaseClass::logError([
                'message' => 'Failed to update account email',
                'exception' => $e
            ]);
            return false;
        }
    }

    public function updatePhone(int $phone): bool {
        try {
            $smt = SQLServices::makeCoreConnection()->prepare("UPDATE `accounts` SET phone=:phone WHERE accountId=:accountId");
            $smt->execute(array(
                ":phone" => Cryptography::encrypt($phone),
                ":accountId" => $this->accountId
            ));
            $this->emailAboutChange([
                'headerLeft' => 'Primary Phone Updated Successfully',
                'changes' => [
                    'Updated primary phone number to: '.$phone
                ],
            ]);
            return true;
        } catch (Exception $e) {
            BaseClass::logError([
                'message' => 'Failed to update account Phone',
                'exception' => $e
            ]);
            return false;
        }
    }

    public function updatePayPalEmail(string $paypalEmail): bool {
        try {
            $smt = SQLServices::makeCoreConnection()->prepare("UPDATE `accounts` SET paypalEmail=:paypalEmail WHERE accountId=:accountId");
            $smt->execute(array(
                ":paypalEmail" => $paypalEmail,
                ":accountId" => $this->accountId
            ));
            return true;
        } catch (Exception $e) {
            BaseClass::logError([
                'message' => 'Failed to update account Phone',
                'exception' => $e
            ]);
            return false;
        }
    }

    public function updateSetting($data ): bool {
        $setting = $data['setting'];
        if(in_array($setting,['2faMethod'])){
            try {
                $smt = SQLServices::makeCoreConnection()->prepare("UPDATE `accountSettings` SET ".$setting."=:value WHERE accountId=:accountId");
                $smt->execute(array(
                    ":value" => $data['value'],
                    ":accountId" => $this->accountId
                ));
                return true;
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to update account setting->'.$setting,
                    'exception' => $e
                ]);
                return false;
            }
        }

        return false;
    }


    public function updatePassword($password ): bool {
        return true;
    }



    public function sendClientEmailVerificationCode(string $email) : bool{

        $verificationCode = BaseClass::generateIntCode(6);
        $bodyText = [
            'Please use the following code to verify:<br><b>'.$verificationCode.'</b>',
            'If this change was not requested by you, please login to your membership portal and remove all unknown devices under the security tab.'
        ];

        if(Mailer::sendSingleEmail(
            '/generic.twig',
            [
                //TEMPLATE DATA
                'firstName' => $this->firstName,
                'fullName' => $this->fullName,
                'headerLeft' => 'Request to change your primary email',
                'headerRight' => $this->fullName.' | Account #'.$this->accountId,
                'jumboTitle' => 'Verification',
                'intro' => 'Hey '.$this->firstName.', we\'ve noticed that you\'ve recently requested to change your primary email address.',
                'body' => implode('<br><br>',$bodyText),
                'buttonLink' => '',
                'buttonText' => '',
                'signature' => 'Thanks!<br>BUSINESS_NAME_HIDDEN'
            ],
            $this->firstName.' '.$this->lastName,
            MailConfiguration::$MEMBERSHIP_UPDATE_SENDER_ADDRESS,
            'Changes to your account',
            $email
        )){
            $_SESSION['clientVerificationCode'] = $verificationCode;
            return true;
        }else{
            return false;
        }

    }

    public function sendClientSmsVerificationCode(string $phone) : bool{

        $verificationCode = BaseClass::generateIntCode(6);

        $smsControl = new SMSControl($phone,'Your '.BUSINESS_NAME.' verification code is '.$verificationCode);
        if(!$smsControl->sendSMS()){
            return false;
        }else{
            $_SESSION['clientVerificationCode'] = $verificationCode;
            return true;
        }

    }

    public function delete(): bool {
        try {
            SQLServices::makeCoreConnection()->prepare("DELETE FROM `accounts` WHERE accountId=? limit 1")->execute([$this->accountId]);
            return true;
        } catch (Exception $e) {
            BaseClass::logError([
                'message' => 'Failed to delete from accounts',
                'exception' => $e
            ]);
            return false;
        }
    }

    public function completeAccountSetup(){

        if($this->accountType != 'ADMIN'){
            $currentYear = date("Y");
            $portalUrl = SITE_URL.'account/login';
            $twig = new Render(ABSPATH.'templates/email/content');
            //Send welcome Email
            Mailer::sendSingleEmail(
                '/generic.twig',
                [
                    //TEMPLATE DATA
                    'firstName' => $this->firstName,
                    'fullName' => $this->fullName,
                    'headerLeft' => 'Welcome to the '.$currentYear.' BUSINESS_NAME_HIDDEN community, '.$this->fullName.'!',
                    'headerRight' => 'Account #'.$this->accountId,
                    'jumboTitle' => 'WELCOME',
                    'intro' => 'Dear '.$this->firstName.',',
                    'body' => $twig->getTemplate('welcomeBodyText.twig',[]),
                    'buttonLink' => $portalUrl,
                    'buttonText' => 'MEMBERSHIP PORTAL',
                    'signature' => 'Cheers,<br>BUSINESS_NAME_HIDDEN'
                ],
                $this->firstName.' '.$this->lastName,
                MailConfiguration::$RENEWAL_SENDER_ADDRESS,
                'Welcome to the '.$currentYear.' BUSINESS_NAME_HIDDEN community, '.$this->fullName,
                $this->email
            );

        }
    }

}