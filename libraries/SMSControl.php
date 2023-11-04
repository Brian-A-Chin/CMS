<?php
    use Twilio\Rest\Client;
    class SMSControl Extends SMSConfiguration {

        private int $phoneNumber;
        private string $message;

        public function __construct($phoneNumber,$message){
            $this->phoneNumber = $phoneNumber;
            $this->message = $message;
        }


        public function sendSMS() : bool{
            try {
                $client = new Client(SMSConfiguration::$ACCOUNT_SID, SMSConfiguration::$AUTH_TOKEN);
                $client->messages->create(
                    $this->phoneNumber,
                    array(
                        'from' => SMSConfiguration::$SENDER_PHONE_NUMBER,
                        'body' => $this->message
                    )
                );
                return true;
            }catch (Twilio\Exceptions\TwilioException $e){
                $e->getmessage();
                BaseClass::logError([
                    'message' => 'Failed to send SMS via Twilio',
                    'exception' => $e->getmessage()
                ]);
            }
            return false;
        }


    }