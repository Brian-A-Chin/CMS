<?php


class AccountDeclarations {

    public static function getAccountState($type = false ): string | array{

        $accountStates = [
            'Deleted' => 0,
            'Active' => 1,
            'Disabled'=> 2,
        ];


        if($type != false) {
            return array_key_exists($type, $accountStates) ? $accountStates[$type] : $accountStates['disabled'];
        }else{
            return $accountStates;
        }

    }


}