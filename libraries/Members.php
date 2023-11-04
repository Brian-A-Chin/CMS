<?php


    class Members {

        public function getAllAccounts($results): array | bool {
            $data = array();
            if(!empty($results)){
                foreach ($results as $row) {
                    $data[] = [
                        'accountId' => Cryptography::encrypt($row["accountID"]),
                        'permissionGroup' => $row["permissionName"],
                        'identifier' => $row["identifier"],
                        'phone' => Cryptography::decrypt($row["phone"]),
                        'email' => Cryptography::decrypt($row["email"]),
                        'niceDate' => $row["niceDate"]
                    ];
                }
                return $data;
            }else{
                BaseClass::logError([
                    'message' => 'Failed to get all member accounts',
                    'exception' => 'array of rows is empty. Pagination requires rows inorder to get all accounts'
                ]);
                return false;
            }
        }

//        public static function removeMemberIntent(int $phone): void{
//            try {
//                SQLServices::makeCoreConnection()->prepare("DELETE FROM `memberIntent` WHERE phone=?")->execute([$phone]);
//            }catch(Exception $e){
//                BaseClass::logError([
//                    'message' => 'Failed to delete Member Intent',
//                    'exception' => $e
//                ]);
//            }
//        }

//        public static function addMemberIntent(int $phone): void{
//            try {
//                $smt = SQLServices::makeCoreConnection()->prepare("INSERT INTO `memberIntent` (`phone`) VALUES (:phone)");
//                $smt->execute(array(
//                    ":phone" => $phone
//                ));
//                //return true;
//            } catch (Exception $e) {
//                BaseClass::logError([
//                    'message' => 'Failed to add member intent already exist',
//                    'exception' => $e
//                ]);
//                //return false;
//            }
//        }

//        public static function isValidNewMember(int $phone): bool{
//            try{
//                $stmt = SQLServices::makeCoreConnection()->prepare("SELECT phone FROM memberIntent WHERE phone=?");
//                $stmt->execute([$phone]);
//                return $stmt->rowCount() != 0;
//            }catch(Exception $e){
//                BaseClass::logError([
//                    'message' => 'Failed to check if is valid new member',
//                    'exception' => $e
//                ]);
//                return false;
//            }
//        }



    }