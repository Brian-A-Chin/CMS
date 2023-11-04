<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

class Render extends FilesystemLoader {
    private FilesystemLoader $loader;
    private Environment $twig;

    public function __construct($path){
        $this->loader = new FilesystemLoader($path);
        $this->twig = new Environment($this->loader);
    }

    public static function getTemplateByName($name) : array{

        return match ($name) {
            'confirmNewLoginMethodViaCode' => [
                'path' => '/templates/email',
                'fileName' => 'confirmNewLoginMethodViaCode.twig'
            ],
            default => [
                'path' => '/templates/error',
                'fileName' => 'notFound.twig'
            ],
        };

    }

    public function getTemplate($type, $parameters = false ): string
    {
        $accountData = array();
        $accountId = SessionManager::getAccountID();
        if($accountId != -1){
            $account = new Account(SessionManager::getAccountID());
            $accountData = [
                'accountId' => $account->accountId
            ];
        }
        if(!$parameters){
            $parameters = [];
        }
        $parameters['accountData'] = $accountData;

        try {
            if(!$parameters){
                return $this->twig->render($type);
            }else{
                return $this->twig->render($type,$parameters);
            }
        } catch (LoaderError | SyntaxError | RuntimeError $e) {
            BaseClass::logError([
                'message' => 'Twig failed to render template',
                'exception' => $e
            ]);
            return false;
        }
    }

    public static function getWebTemplate($template, $parameters = false ): string
    {
        $loader = new FilesystemLoader(ABSPATH.'templates/web');
        $twig = new Environment($loader);
        try {
            if(!$parameters){
                return $twig->render($template);
            }else{
                return $twig->render($template,$parameters);
            }
        } catch (LoaderError | SyntaxError | RuntimeError $e) {
            BaseClass::logError([
                'message' => 'Twig failed to render web template',
                'exception' => $e
            ]);
            return false;
        }
    }



}