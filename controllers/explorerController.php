<?php

    class explorerController extends Controller {

        public function __construct() {

        }

        public function index() {
            $pagingData = [
                'request' => $_GET,
                //used for table sorting. if a column is not listed below it will not honor the sort by that column
                'columns' => ['accounts.accountId','identifier','email','status','permissionName','joinDate'],
                'query' => 'SELECT count(accounts.accountId) OVER() AS totalRows,accounts.accountId AS accountId,contactRecords.identifier,accounts.permissionGroupId,name AS permissionName, phone,email,status,DATE_FORMAT(joinDate, "%m/%d/%Y") AS niceDate FROM contactRecords INNER Join accounts ON contactRecords.accountId=accounts.accountId INNER JOIN permissionGroups pg on accounts.permissionGroupId = pg.id AND accounts.accountType="ADMIN" AND accounts.status != 0'
            ];
            foreach($_GET as $key => $value){
                if(in_array($key, $pagingData['columns'])){
                    $pagingData['fixedWhereValues'][$key] = $value;
                }
            }
            $pagination = new Pagination($pagingData);
            $fetchRows = $pagination->getRows();
            if($fetchRows != false) {
                $accountStates = AccountDeclarations::getAccountState(false);
                foreach ($fetchRows[0] as $key => $row) {
                    $fetchRows[0][$key]['encryptedAccountId'] = urlencode(Cryptography::encrypt($row["accountId"]));
                    $fetchRows[0][$key]['permissionGroupId'] = urlencode(Cryptography::encrypt($row["permissionGroupId"]));
                    $fetchRows[0][$key]['email'] = Cryptography::decrypt($row["email"]);
                    $fetchRows[0][$key]['status'] = array_search($row["status"],$accountStates);
                }
                $this->view([
                    'rows' => $fetchRows[0],
                    'myAccEID' => urlencode(Cryptography::encrypt(SessionManager::getAccountID())),
                    'paging' => $fetchRows[1]]
                );
            }


        }
    }