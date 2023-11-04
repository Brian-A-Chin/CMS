<?php


    class AccountSettings {

        public int $accountId;
        public ?string $twoFaMethod;

        public function __Construct($accountId){
            $this->accountId = $accountId;
            $this->set();
        }

        private function set(){
            try {
                $query = 'SELECT * FROM accountSettings WHERE accountId=? LIMIT 1';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute([$this->accountId]);
                $data = $statement->fetch(PDO::FETCH_ASSOC);
                if($statement->rowCount() == 0){
                    $this->twoFaMethod = $data['2faMethod'];
                }
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to get contactRecords data',
                    'exception' => $e
                ]);
            }
        }

    }