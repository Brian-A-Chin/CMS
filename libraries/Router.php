<?php
    class Router {

        protected mixed $currentController;
        protected string $currentMethod;

        public function __construct($currentController,$currentMethod) {

            $this->currentController = $currentController;
            $this->currentMethod = $currentMethod;

        }

        public function route($overrides = false){

            if($overrides != false){
                if(array_key_exists('controller',$overrides)){
                    $controllerName = $overrides['controller'];
                    if (stripos($controllerName, 'controller') === FALSE) {
                        $this->currentController = sprintf('%sController',$controllerName);
                    }else {
                        $this->currentController = $controllerName;
                    }
                }
            }

            if(!file_exists(ABSPATH.'controllers/' . $this->currentController. '.php')){
                // If not exists, set as controller
                $twig = new Render(ABSPATH.'views/error');
                echo $twig->getTemplate('index.twig',['error'=>'Controller invalid: '.ABSPATH.'controllers/' . $this->currentController. '.php']);
                return;
            }

            // Require the controller
            require_once ABSPATH.'controllers/'. $this->currentController . '.php';

            // Instantiate controller class
            $this->currentController = new $this->currentController;

            // Check if method does not exist
            if($this->currentMethod == 'error'){
                Utilities::modifyCurrentUrl(1,'index');
                $this->currentMethod = 'index';
            }else if(!method_exists($this->currentController, $this->currentMethod)) {
                $twig = new Render(ABSPATH.'views/error');
                echo $twig->getTemplate('index.twig',['error'=>'Method invalid: '.$this->currentMethod]);
                return;
            }

            Utilities::modifyGET();

            call_user_func_array([$this->currentController, $this->currentMethod], []);

        }



    }