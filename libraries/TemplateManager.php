<?php


    class templateManager {
        public string $source;
        public ?string $template;
        public bool $encrypted;
        private static array $allowedFileExtensions = ['twig','css'];

        #[Pure] public function __Construct($source,$template = null,bool $encrypted = true) {
           $this->encrypted = $encrypted;
           $this->source = $this->encrypted ? Cryptography::decrypt($source) : $source;
           $this->template = $template;

        }



        public static function fetchAll($paths = false):array{

            if(!$paths){
                $paths = [
                    'layouts/admin/',
                    'layouts/members/',
                    'templates/email/',
                    'templates/email/content/',
                    'templates/email/campaigns/',
                    'templates/error/',
                    'views/setup/',
                    'views/individual/',
                    'views/authentication/',
                    'views/manage/',
                    'models/',
                    'stylesheets/'
                ];
            }

            $templateList =  array();

            foreach($paths as $file_path){
                foreach(glob(sprintf("%s%s*.{%s}",ABSPATH,$file_path,implode(",",self::$allowedFileExtensions)),GLOB_BRACE) as $filename){
                    if(is_file($filename)){
                        $basename = basename($filename);
                        $pathName = substr($file_path,0,strlen($file_path) - 1);
                        //Adds spacing to file name based on capital letters
                        $formattedName = implode(' ',preg_split('/(?=[A-Z])/',pathinfo($basename, PATHINFO_FILENAME)));

                        $templateList[$pathName][] = [
                            'name' => $basename,
                            'excludeExtension' => substr($basename,0,strpos($basename,'.')),
                            'formattedName' => $formattedName,
                            'location' => Cryptography::encrypt(sprintf("%s%s%s",ABSPATH,$file_path,$basename)),
                            'Modified' => date('F d, Y \a\t g:i:s a', filemtime($filename))
                        ];
                    }
                }
            }

            return $templateList;


        }

        public function getRawHtmlTemplate(){
            if(in_array(pathinfo($this->source)['extension'],self::$allowedFileExtensions)){
                return file_get_contents($this->source);
            }else{
                return [
                    'result' => false,
                    'response' => "Invalid File type Please add ".pathinfo($this->source)['extension']." to your configuration."
                ];
            }

        }

        public function saveRawHtmlTemplate(){
            if(file_exists($this->source)){
                file_put_contents($this->source, $this->template);
                return [
                    'result' => true,
                    'response' => "File updated successfully"
                ];
            }else{
                return [
                    'result' => false,
                    'response' => "File does not exist"
                ];
            }
        }
    }