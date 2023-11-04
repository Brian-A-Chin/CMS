<?php
    //Load the model and the view
    class Controller {

        //Load the view (checks for the file)
        public function view($templateData = [], $overrideTemplate = false) {
            $template = !$overrideTemplate ? Utilities::getCurrentMethod() : $overrideTemplate;
            $template = lcfirst($template);
            $path = sprintf(ABSPATH ."views/%s",str_replace('Controller','',Utilities::getCurrentController()));
            $templateName = sprintf("%s.twig",$template);
            if(file_exists(sprintf('%s/%s',$path,$templateName))){
                $twig = new Render($path);
                $account = new Account( SessionManager::getAccountID() );
                $templateData['currentUserPermissions'] = Permissions::getPermissions($account->accountId);

                //Adds paging partial to template
                if(array_key_exists('paging',$templateData)){
                    $pagingTwig = new Render(ABSPATH ."views/shared");
                    $templateData['pagingPartial'] = $pagingTwig->getTemplate('pagingPartial.twig',$templateData['paging']);
                }

                echo $twig->getTemplate($templateName,$templateData);
            }else{
                $twig = new Render(ABSPATH.'views/error');
                echo $twig->getTemplate('index.twig',['error'=>'template does not exist:'.sprintf('%s/%s',$path,$templateName)]);
            }


        }

        public function jSON($jsonData = []){
            echo json_encode($jsonData);
        }

    }