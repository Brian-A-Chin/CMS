<?php

class Extras extends Account {

    public int $accountId;

    public function __Construct($accountId){
        $this->accountId = $accountId;
        parent::__construct($accountId);
    }

    public function sendFacebookJoinRequestEmail(){
        $email = $this->email;
        $twig = new Render(ABSPATH.'templates/email/content');
        //Send Facebook group join Email
        Mailer::sendSingleEmail(
            '/generic.twig',
            [
                //TEMPLATE DATA
                'firstName' => $this->firstName,
                'fullName' => $this->fullName,
                'headerLeft' => 'Join Our Facebook Group '.$this->fullName,
                'headerRight' => 'View details about our Facebook group',
                'jumboTitle' => 'LET\'S CONNECT',
                'intro' => '',
                'body' => $twig->getTemplate('facebookBodyText.twig',[]),
                'buttonLink' => ClientConfiguration::$FACEBOOK_GROUP,
                'buttonText' => 'JOIN OUR FACEBOOK GROUP',
                'signature' => 'Cheers,<br>BUSINESS_NAME_HIDDEN'
            ],
            $this->firstName.' '.$this->lastName,
            MailConfiguration::$RENEWAL_SENDER_ADDRESS,
            'Connect with us on Facebook '.$this->fullName,
            $email
        );

    }

    public function updateLegal(): bool{
        try {
            $smt = SQLServices::makeCoreConnection()->prepare("UPDATE `extras` SET ethics='on',liability='on' WHERE accountId=:accountId");
            $smt->execute(array(
                ":accountId" =>  $this->accountId
            ));
            return true;
        } catch (Exception $e) {
            BaseClass::logError([
                'message' => 'failed to updated legal',
                'exception' => $e
            ]);
            return false;
        }
    }

    public function addSignupData($data): bool{
        try {
            $conn = SQLServices::makeCoreConnection();
            $smt = $conn->prepare("INSERT INTO `extras` (`accountId`,`facebook`,`diveExperience`,`divingCertification`,`shirtSize`,`ethics`,`liability`) VALUES (:accountId,:facebook,:diveExperience,:divingCertification,:shirtSize,:ethics,:liability)");
            $smt->execute(array(
                "accountId" => $this->accountId,
                "facebook" => $data['facebook'],
                "diveExperience" => $data['diveExperience'],
                "divingCertification" => $data['divingCertification'],
                "shirtSize" => $data['shirtSize'],
                "ethics" => $data['ethics'],
                "liability" => $data['liability'],
            ));
            return true;
        } catch (Exception $e) {
            BaseClass::logError([
                'message' => 'Failed to add extras',
                'exception' => $e
            ]);
            return false;
        }
    }

    public function getLegal() : array{
        try {
            $query = 'SELECT ethics,liability FROM extras WHERE accountId=? LIMIT 1';
            $statement = SQLServices::makeCoreConnection()->prepare($query);
            $statement->execute([$this->accountId]);
            return $statement->rowCount() > 0 ? $statement->fetch(PDO::FETCH_ASSOC) : array();
        } catch (Exception $e) {
            BaseClass::logError([
                'message' => 'Failed to get extras',
                'exception' => $e
            ]);
            return array();
        }
    }

    public function get() : array{
        try {
            $query = 'SELECT * FROM extras WHERE accountId=? LIMIT 1';
            $statement = SQLServices::makeCoreConnection()->prepare($query);
            $statement->execute([$this->accountId]);
            return $statement->rowCount() > 0 ? $statement->fetch(PDO::FETCH_ASSOC) : array();
        } catch (Exception $e) {
            BaseClass::logError([
                'message' => 'Failed to get extras',
                'exception' => $e
            ]);
            return array();
        }
    }

    public function updateInfo(array $data): bool
    {
        try{
            $smt = SQLServices::makeCoreConnection()->prepare("UPDATE `extras` SET `facebook`=:facebook,`diveExperience`=:diveExperience,`divingCertification`=:divingCertification,`shirtSize`=:shirtSize WHERE `accountId`=:accountId LIMIT 1");
            $smt->execute([
                ':facebook' => $data['facebook'],
                ':diveExperience' => $data['diveExperience'],
                ':divingCertification' => $data['divingCertification'],
                ':shirtSize' => $data['shirtSize'],
                ':accountId' => $this->accountId,
            ]);
            return true;
        }catch(Exception $e){
            BaseClass::logError([
                'message' => 'Failed to update Extras',
                'exception' => $e
            ]);
            return false;
        }
    }



}