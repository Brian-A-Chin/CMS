<?php
//

class Admin{

    public int $accountId;
    public function __Construct($accountId){
        $this->accountId = $accountId;
    }


    public function createPermissionGroup ($data ): bool
    {
        try{
            $smt = SQLServices::makeCoreConnection()->prepare("INSERT INTO `permissionGroups` (`name`,`permissions`,`layout`)VALUES(:name,:permissions,:layout)");
            $smt->execute(array(
                ':name' => $data['name'],
                ':permissions' => implode(',',$data['permissions']),
                ':layout' => $data['layout']
            ));
            return true;
        }catch(Exception $e){
            BaseClass::logError([
                'message' => 'Failed to insert into permissionGroups',
                'exception' => $e
            ]);
            return false;
        }
    }

    public function updatePermissionGroup ($data ): bool
    {
        try{
            $smt = SQLServices::makeCoreConnection()->prepare("UPDATE `permissionGroups` SET `name`=:name,`permissions`=:permissions,`layout`=:layout WHERE `ID`=:id LIMIT 1");
            $smt->execute([
                ':name' => $data['name'],
                ':permissions' => implode(',',$data['permissions']),
                ':layout' => $data['layout'],
                ':id' => $data['id']
            ]);
            return true;
        }catch(Exception $e){
            BaseClass::logError([
                'message' => 'Failed to update Permission Group',
                'exception' => $e
            ]);
            return false;
        }
    }

    public function removePermissionGroup($data): bool {
        if (Permissions::getPermissionEnrollmentCount($data['id']) === 0) {
            try {
                SQLServices::makeCoreConnection()->prepare("DELETE FROM `permissionGroups` WHERE ID=?")->execute([$data['id']]);
                return true;
            } catch (Exception $e) {
                BaseClass::logError([
                    'message' => 'Failed to delete permissionGroup',
                    'exception' => $e
                ]);
                return false;
            }
        }
    }

    public function updateAccountPrivileges ($data ): bool
    {
        try{
            $smt = SQLServices::makeCoreConnection()->prepare("UPDATE `Accounts` SET `permissionGroupId`=:permissionGroupId WHERE `accountId`=:accountId LIMIT 1");
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