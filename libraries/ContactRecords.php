<?php


    class contactRecords extends AccountSettings {

        public int $accountId;
        public ?string $fullName;
        public array $nameParts;
        public ?string $firstName;
        public ?string $lastName;

        public function __Construct($accountId){
            $this->accountId = $accountId;
            $this->set();
            parent::__construct($accountId);
        }

        private function set(){
            try {
                $query = 'SELECT * FROM contactRecords WHERE accountId=? LIMIT 1';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute([$this->accountId]);
                $data = $statement->fetch(PDO::FETCH_ASSOC);
                $this->fullName = $data['identifier'];
                $nameParts = explode(' ', $data['identifier']);
                $this->nameParts = [
                    'firstName' => $nameParts[0],
                    'lastName' => $nameParts[count($nameParts)-1],
                ];
                $this->firstName = $data['firstName'];
                $this->lastName = $data['lastName'];
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to get contactRecords data',
                    'exception' => $e
                ]);
            }
        }

        public static function addContactRecord(array $data): bool{
            try {
                $smt = SQLServices::makeCoreConnection()->prepare("INSERT INTO `contactRecords` (`accountId`,`firstName`,`lastName`,`identifier`) VALUES (:accountId,:firstName,:lastName,:identifier)");
                $smt->execute(array(
                    "accountId" => $data['accountId'],
                    "firstName" => $data['firstName'],
                    "lastName" => $data['lastName'],
                    "identifier" => $data['identifier']
                ));
                return true;
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to add account contact record',
                    'exception' => $e
                ]);
                return false;
            }
        }

        public function updateContactInfo($data): bool {
            try {
                $smt = SQLServices::makeCoreConnection()->prepare("UPDATE `contactRecords` SET identifier=:identifier,firstName=:firstName,lastName=:lastName WHERE accountId=:accountId");
                $smt->execute(array(
                    ":identifier" =>  $data['identifier'],
                    ":firstName" =>  $data['firstName'],
                    ":lastName" => $data['lastName'],
                    ":accountId" =>  $this->accountId
                ));
                return true;
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to update contact information',
                    'exception' => $e
                ]);
                return false;
            }
        }

    }