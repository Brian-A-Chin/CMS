<?php


    class Security{

        public function __construct(){

        }

        public static function log($data): void{
            $smt = SQLServices::makeCoreConnection()->prepare("INSERT INTO `securityLogs` (`subject`,`message`,`accountId`,`ip`) VALUES (:subject,:message,:accountId,:ip)");
            $smt->execute(array(
                ":subject" =>  $data['subject'],
                ":message" =>  $data['message'],
                ":accountId" =>  array_key_exists('accountId',$data) ? $data['accountId'] : null,
                ":ip" =>  BaseClass::getIpv4()
            ));
        }

        public function addToBlackList($data): bool {
            $insertValues = array();
            $placeHolders = array();
            $accountId = SessionManager::getAccountID();
            foreach(explode(',',$data['Blacklist']) as $ip){
                if(strlen($ip) == 0)
                    continue;

                $insertValues[] = $ip;
                $insertValues[] = $data['reason'];
                $insertValues[] = $data['loginAccess'];
                $insertValues[] = $data['registrationAccess'];
                $insertValues[] = $data['paymentAbility'];
                $insertValues[] = $data['supportAbility'];
                $insertValues[] = $data['Expires'];
                $insertValues[] = $accountId;
                $placeHolders[] = "(?,?,?,?,?,?,?,?)";
            }
            $query = "INSERT INTO `Blacklist` (`ip`, `reason`,`login`,`Registration`,`Payment`,`Support`,`expires`,`accountId`) VALUES".implode(',',$placeHolders);
            try {
                $smt = SQLServices::makeCoreConnection()->prepare($query);
                $smt->execute($insertValues);
                return true;
            }catch (Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to add to BlackList',
                    'exception' => $e
                ]);
                return false;
            }

        }

        public function updateBlacklist ($data ): bool
        {
            try{
                $smt = SQLServices::makeCoreConnection()->prepare("UPDATE `Blacklist` SET `Registration`=:registration,`login`=:login,`Payment`=:payment,`Support`=:support,`ip`=:ip ,`reason`=:reason ,`expires`=:expires WHERE `ID`=:id LIMIT 1");
                $smt->execute([
                    ':id' => $data["id"],
                    ':registration' => $data["registrationAccess"],
                    ':login' => $data["loginAccess"],
                    ':payment' => $data["paymentAbility"],
                    ':support' => $data["supportAbility"],
                    ':ip' => $data["ip"],
                    ':reason' => $data["reason"],
                    ':expires' => $data["expires"],
                ]);
                return true;
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to update Blacklist Group',
                    'exception' => $e
                ]);
                return false;
            }
        }

        public function removeBlacklistRule($data ): bool {
            try {
                SQLServices::makeCoreConnection()->prepare("DELETE FROM `Blacklist` WHERE ID=?")->execute([$data['id']]);
                return true;
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to delete from Blacklist',
                    'exception' => $e
                ]);
                return false;
            }
            return false;
        }

        public static function getBlacklistData($ID ): array | bool{
            try {
                $smt = SQLServices::makeCoreConnection()->prepare("SELECT * FROM `Blacklist` WHERE ID=?");
                $smt->execute([$ID]);
                return $smt->fetch(PDO::FETCH_ASSOC);
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to fetch Blacklist Data',
                    'exception' => $e
                ]);
                return false;
            }
        }

        public static function getLatestFiveSessions(int $accountID): array {
            try {
                $query = 'SELECT `id`,`device`,`ip` FROM sessions WHERE accountId=? ORDER BY created DESC LIMIT 5';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute([$accountID]);
                return $statement->fetchAll();
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to get Account Status',
                    'exception' => $e
                ]);
                return array();
            }
        }



    }