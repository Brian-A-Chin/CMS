<?php

    class Utilities {

        public static function getCurrentUrl() : array{
            $url = [];
            if(isset($_GET['url'])){
                $url = rtrim($_GET['url'], '/');
                $url = filter_var($url, FILTER_SANITIZE_URL);
                $url = explode('/', $url);
            }
            return $url;
        }

        public static function modifyCurrentUrl($index, $value) : void{
            $url = self::getCurrentUrl();
            if(Count($url) >= $index){
                $url[$index] = $value;
                $_GET['url'] = implode("/",$url);
            }
        }

        public static function modifyGET() : void{
            foreach($_GET as $key => $value){
                $new_val = rawurldecode($value);
                $_GET[$key] = preg_replace('/\s+/', '+', $new_val);
            }
        }

        public static function getCurrentController() : string{
            $url = self::getCurrentUrl();
            if(isset($_POST['controller']) && !empty($_POST['controller'])){
                return sprintf('%sController',$_POST['controller']);
            }else{
                if(Count($url) == 0)
                    return 'error';
                return sprintf('%sController',$url[0]);
            }
        }

        public static function getCurrentMethod() : string{
            $url = self::getCurrentUrl();
            if(Count($url) < 2)
                return 'index';
            return lcfirst(self::getCurrentUrl()[1]);
        }



    }