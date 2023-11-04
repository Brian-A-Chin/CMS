<?php


    class Permissions {
        public static function getPermissionGroups(): array | bool{
            try{
                $query = 'SELECT id,name FROM permissionGroups';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute();
                return $statement->fetchAll(PDO::FETCH_ASSOC);
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to get permission group ids',
                    'exception' => $e
                ]);
                return false;
            }
        }


        public static function getPermissionGroupByID($ID): array | bool{
            try{
                $query = 'SELECT * FROM permissionGroups WHERE ID=? LIMIT 1';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute([$ID]);
                return $statement->fetch(PDO::FETCH_ASSOC);
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to get permission group BY id',
                    'exception' => $e
                ]);
                return false;
            }
        }


        public static function getPermissionEnrollmentCount($ID ): int | bool{
            try{
                $query = 'SELECT COUNT(accountId) AS `Total` FROM accounts WHERE permissionGroupId=?';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute([$ID]);
                return (int)$statement->fetch(PDO::FETCH_ASSOC)['Total'];
            }catch(Exception $e){
                BaseClass::logError([
                    'message' => 'Failed to get permission enrollment Count',
                    'exception' => $e
                ]);
                return false;
            }
        }

        public static function getPermissionGroupLayout($accountId): string | bool {
            $groupId = Permissions::getAccountPermissionGroupId($accountId);
            try {
                $query = 'SELECT layout FROM permissionGroups WHERE id=? LIMIT 1';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute([$groupId]);
                return $statement->rowCount() > 0 ? $statement->fetch(PDO::FETCH_ASSOC)['layout'] : false;
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to get Permission Group Layout',
                    'exception' => $e
                ]);
                return false;
            }

        }

        public static function getAccountPermissionGroupId($accountId = false) : int | bool{
            try {
                $query = 'SELECT permissionGroupId FROM accounts WHERE accountId=? LIMIT 1';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute([$accountId]);
                return (int)$statement->fetch(PDO::FETCH_ASSOC)['permissionGroupId'];
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to get Account permissionGroupId',
                    'exception' => $e
                ]);
                return false;
            }
        }

        public static function getPermissions($accountId): array | bool {
            $groupId = Permissions::getAccountPermissionGroupId($accountId);
            try {
                $query = 'SELECT permissions FROM permissionGroups WHERE id=? LIMIT 1';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute([$groupId]);
                $results = $statement->fetch(PDO::FETCH_ASSOC);
                $permissionsArray =  strlen($results['permissions']) > 0 ? explode(',',$results['permissions']) : array();
                return Filters::cleanArray( $permissionsArray );
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to get Account Status',
                    'exception' => $e
                ]);
                return false;
            }

        }

        public static function isPermitted($accountId,$permission): bool {
            $groupId = Permissions::getAccountPermissionGroupId($accountId);
            try {
                $query = 'SELECT FIND_IN_SET(?,permissions) as strpos FROM `permissionGroups` WHERE id=? LIMIT 1';
                $statement = SQLServices::makeCoreConnection()->prepare($query);
                $statement->execute([$permission,$groupId]);
                if($statement->rowCount() == 0)
                    return false;

                return $statement->fetch(PDO::FETCH_ASSOC)['strpos'] != 0;
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to get Account Status',
                    'exception' => $e
                ]);
                return false;
            }
        }

    }