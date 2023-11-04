<?php

    class Autoloader {

        private string $path;
        private array $prerequisites;
        private string $abs;
        public function __construct($abs,$path) {
            $this->abs = $abs;

            $this->prerequisites = [
                'Render'=>[
                    'libraries/3rdParty/twig/vendor/autoload.php',
                ]
            ];

            $this->path = $this->abs.$path;
            spl_autoload_register( array($this, 'load') );
        }

        function load( $file ) {

            if (is_file($this->path . '/' . $file . '.php')) {

                if(array_key_exists($file,$this->prerequisites)){
                    foreach($this->prerequisites[$file] as $pre_file ){
                        require_once( $this->abs . $pre_file );
                    }
                }

                require_once( $this->path . '/' . $file . '.php' );
            }
        }

    }