<?php

    /* ALL RETURN TYPES MUST BE AN ARRAY*/

    use JetBrains\PhpStorm\ArrayShape;
    use JetBrains\PhpStorm\Pure;

    class Explorer extends Account {

        private ?array $GET = null;
        #[Pure] public function __Construct($accountId, $GET) {
            $this->GET = $GET;
            parent::__construct($accountId);
        }

        public function getAllAccounts($results): array | bool {
            $data = array();
            if(!empty($results)){
                $accountStates = AccountDeclarations::getAccountState(false);
                foreach ($results as $row) {
                    $data[] = [
                        'accountId' => urlencode(Cryptography::encrypt($row["accountID"])),
                        'permissionGroup' => $row["permissionName"],
                        'identifier' => $row["identifier"],
                        'phone' => $row["phone"],
                        'email' => Cryptography::decrypt($row["email"]),
                        'status' => array_search($row["status"],$accountStates),
                        'niceDate' => $row["niceDate"],
                    ];
                }
                return $data;
            }else{
                BaseClass::logError([
                    'message' => 'Failed to get all accounts',
                    'exception' => 'array of rows is empty. Pagination requires rows inorder to get all accounts'
                ]);
                return false;
            }
        }


    }