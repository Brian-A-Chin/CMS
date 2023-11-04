<?php
    class SessionManager {

        public static function getAccountID() : int{
            if(isset($_SESSION['SESSION'])){
                return $_SESSION['SESSION']['ACCOUNT_ID'];
            }
            return -1;
        }

        public static function initializeSession(array $sessionData) : void{
            unset($_SESSION['SESSION']);
            $_SESSION['SESSION'] = $sessionData;
        }

        public static function requireSession($level = 'normal') : void{
            $accountId = self::getAccountID();
            if($accountId == -1)
                exit(header("location:".SITE_URL."account/login"));

            switch ($level){
                case 'normal':
                    $ip = BaseClass::getIpv4();
                    //$fingerprint = $_SESSION['SESSION']['FINGERPRINT'];
                    $sessionData = Authentication::getSessionEntryData(null,$accountId);
                    if(
                        empty($sessionData)||
                        $sessionData['expires'] < date('Y-m-d H:i:s')
                        //causes issues with private relay due to changing ips
                        //|| $sessionData['ip'] != $ip
                    ){
                        exit(header("location:".SITE_URL."account/login"));
                    }
                    break;
            }
        }

    }