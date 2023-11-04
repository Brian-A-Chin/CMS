<?php


    class Individual extends Account {

        public int $accountId;
        public function __Construct($accountId){
            $this->accountId = $accountId;
        }


        public function updateAccountPrivileges ($data ): bool
        {
            try{
                $smt = SQLServices::makeCoreConnection()->prepare("UPDATE `accounts` SET `permissionGroupId`=:permissionGroupId WHERE `accountId`=:accountId LIMIT 1");
                $smt->execute([
                    ':permissionGroupId' => $data['permissionGroupId'],
                    ':accountId' => $data['accountId'],
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

    }