<?php
    class authenticationController extends Controller {
        public function __construct() {

        }

        public function index() {
            $this->view();
        }

        public function logout() {
            session_destroy();
        }

        //LOGIN
        //-------------------------------------------------------
        public function login() {
            $twig = new Render(ABSPATH .'views/authentication');
            $intent = 'MEMBER';
            $urlParts = explode('/',$_SERVER["HTTP_REFERER"]);
            if(count($urlParts) == 7 && $urlParts[6] == 'admin'){
                $intent = 'ADMIN';
            }
            $phone = $_POST['phone'];
            $auth = new Authentication($phone,$intent);
            $data = $auth->login();
            if($data['result']){
                //code was allowed to send successfully
                $this->jSON([
                    'result'=>$data['result'],
                    'view' => $twig->getTemplate('confirmationCode.twig',[
                        'phone' => Cryptography::encrypt($phone)
                    ]),
                    'response' => $data['response'],
                ]);
            }else{
                $this->jSON([
                    'result'=>$data['result'],
                    'response'=>$data['response']
                ]);
            }
        }

        //CODE VERIFICATION
        //-------------------------------------------------------
        public function addNewLoginLocation() {
            $twig = new Render(ABSPATH .'views/authentication');
            $intent = 'MEMBER';
            $urlParts = explode('/',$_SERVER["HTTP_REFERER"]);
            if(count($urlParts) == 7 && $urlParts[6] == 'admin'){
                $intent = 'ADMIN';
            }
            $encryptedPhone = $_POST['phone'];
            $phone = Cryptography::decrypt($encryptedPhone);
            $accountData = Account::getAccountDataByPhoneAndAccType($phone,$intent);
            $auth = new Authentication($phone,$intent,$accountData['accountId']);
            $data = $auth->verify($_POST['twoFactorCode']);
            if($data['result']){
                //code was confirmed successfully
                SessionManager::initializeSession($data['sessionData']);
                $this->jSON([
                    'result'=>$data['result'],
                    'timeout' => 250,
                    'redirect' => sprintf("%s/account",SITE_URL),
                    'response' => $data['response'],
                ]);
            }else if($data['returnToLogin']){
                //security measures taken. Need to take back to login.
                $this->jSON([
                    'result'=>$data['result'],
                    'view' => $twig->getTemplate('login.twig'),
                    'response' => $data['response']
                ]);
            }else{
                //code did not match
                $this->jSON([
                    'result'=>$data['result'],
                    'response'=>$data['response']
                ]);
            }

        }

        //RESEND CODE
        //-------------------------------------------------------
        public function resendCode() {
            $twig = new Render(ABSPATH .'views/authentication');
            $intent = 'MEMBER';
            $urlParts = explode('/',$_SERVER["HTTP_REFERER"]);
            if(count($urlParts) == 7 && $urlParts[6] == 'admin'){
                $intent = 'ADMIN';
            }
            $encryptedPhone = $_POST['phone'];
            $phone = Cryptography::decrypt($encryptedPhone);
            $accountData = Account::getAccountDataByPhoneAndAccType($phone,$intent);
            $auth = new Authentication($phone,$intent,$accountData['accountId']);
            $data = $auth->resendVerificationCode();
            if($data['returnToLogin']){
                //security measures taken. Need to take back to login.
                $this->jSON([
                    'result'=>$data['result'],
                    'view' => $twig->getTemplate('login.twig'),
                    'response' => $data['response']
                ]);
            }else{
                $this->jSON([
                    'result'=>$data['result'],
                    'response'=>$data['response']
                ]);
            }
        }
    }


