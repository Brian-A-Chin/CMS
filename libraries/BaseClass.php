<?php

    use JetBrains\PhpStorm\Pure;

    class BaseClass{

    public function createToken(): string {
        
        $date = new DateTime();
        $unix_time_stamp = $date->getTimestamp();
        return Cryptography::encrypt(ceil(($unix_time_stamp * 1996) / 24));

    }
    
    public function validateToken($token): bool {
        //BASED ON A 7 SEC TTL 
        $date = new DateTime();
        $current_time = $date->getTimestamp();
        $tokenTime = Cryptography::decrypt($token);
        return (ceil(($current_time * 1996) / 24) - 4 <= $tokenTime) && ($tokenTime <= (ceil(($current_time * 1996) / 24)) + 4);
    }

    
    #[Pure] public static function generateIntCode($length) :int{
        if($length == 1){
            return rand(0,9);
        }else{
          $int = '';
            for($i = 0;$i < $length; $i++){
                $int .= 9;
            } 
            return rand($int,$int.'9');
        }
    }

    public static function getDateDifference($startDate,$endDate):DateInterval{
        $d1 = new DateTime($startDate);
        $d2 = new DateTime($endDate);
        return $d1->diff($d2);
    }

    public static function getIpv4():string{
        if(!empty($_SERVER['HTTP_CLIENT_IP'])){
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else{
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    #[Pure] public static function generateAlphaNumericCode($length): string{
	   $str = "";
	   $characters = array_merge(range('A','Z'), range('a','z'), range('0','9'));
	   $max = count($characters) - 1;
	   for ($i = 0; $i < $length; $i++) {
		  $rand = mt_rand(0, $max);
		  $str .= $characters[$rand];
	   }
	   return $str;
        
    }

    public static function logError($data) : void{
        $smt = SQLServices::makeCoreConnection()->prepare("INSERT INTO `errorLogs` (`message`,`exception`,`ip`) VALUES (:message,:exception,:ip)");
        $smt->execute(array(
            ":ip" =>  BaseClass::getIpv4(),
            ":message" =>  $data['message'],
            ":exception" =>  $data['exception']
        ));
    }



    public static function getError($type): array {

        $types = [
            "critical" =>  [
                "result" => false,
                "response" => "An unknown error occurred. Please check back again later."
            ]
        ];

        return $types[$type];
    }

    
    
}





