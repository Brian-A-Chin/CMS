<?php


    use JetBrains\PhpStorm\Pure;

    class Files extends FilesConfiguration {

        public array $approvedFiles;
        public array $workingFiles;
        public array $folders;
        public array $errorList;
        public array $noticeList;
        public string $accountId;
        public string $accountDirectory;
        public ?string $overrideFileName;
        public string $workingDirectory;
        private string $AccountDirectory;

        public function __construct($approvedFiles, $workingFiles, $folders, $accountId, $overrideFileName = null){
            $this->approvedFiles = explode(",",$approvedFiles);
            $this->workingFiles = $workingFiles;
            $this->folders = $folders;
            $this->accountId = $accountId;
            $this->AccountDirectory = Account::getDirectory( Cryptography::encrypt($this->accountId) );
            $this->overrideFileName = $overrideFileName;
            $this->errorList = [];
            $this->noticeList = [];
        }

        public function verifyUserDirectory() : void{

            $path = sprintf("%s%s/%s",ABSPATH,FilesConfiguration::$USER_DIRECTORY,$this->AccountDirectory);
            if (!file_exists($path)) {
                mkdir($path, FilesConfiguration::$BASE_PERMISSIONS);
            }
            $this->workingDirectory = $path;
        }

        public function fileExist($fileName) : bool{
            return file_exists(sprintf("%s/%s",$this->workingDirectory,$fileName));
        }

        public function verifyRequestedDirectory() : void{
            for($i = 0; $i < Count($this->folders);$i++){
                $path = sprintf("%s/%s",$this->workingDirectory,$this->folders[$i]);
                if (!file_exists($path)) {
                    mkdir($path, FilesConfiguration::$BASE_PERMISSIONS);
                }
                $this->workingDirectory = sprintf("%s/%s",$this->workingDirectory,$this->folders[$i]);
            }
        }

        public function upload( ): array {
            if($this->workingFiles['size'][0] == 0){
                return [
                    'result' => false,
                    'errorList' => [],
                    'noticeList' => ['No files to upload']
                ];
            }
            $this->verifyUserDirectory();
            $this->verifyRequestedDirectory();
            foreach($this->workingFiles['name'] as $key =>  $filename){
                $fileSize = $this->workingFiles['size'][$key];
                $fileName = $this->overrideFileName != null ? $this->overrideFileName : $filename;
                $tmpName = $this->workingFiles['tmp_name'][$key];
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                //Logic
                $approvedExt = in_array($extension,FilesConfiguration::getSafeExtensions());
                $approvedFile = in_array($fileName,$this->approvedFiles);
                if($approvedFile && $approvedExt){
                    $oldFilename = $fileName;
                    if($this->fileExist($fileName)){
                        $fileName = strtolower(pathinfo($fileName,PATHINFO_FILENAME));
                        $fileName = sprintf("%s_%s.%s",$fileName,BaseClass::generateIntCode(8),$extension);
                        $this->noticeList[] = sprintf('"%s" was uploaded as: "%s".',$oldFilename,$fileName);
                    }
                    move_uploaded_file($tmpName, sprintf("%s/%s",$this->workingDirectory,$fileName));
                }else if(!$approvedExt && $approvedFile){
                    $this->errorList[] = sprintf('"%s" is not an approved file format.',$fileName);
                }
            }

            return [
                'result' => Count($this->errorList) != Count($this->workingFiles['name']),
                'errorList' => $this->errorList,
                'noticeList' => $this->noticeList
            ];
        }

        public static function getFolders($path): array {

            $folderList = [];
            foreach(glob(sprintf("%s%s/*",ABSPATH,$path)) as $folderName){
                if(is_dir($folderName)){
                    $folderList[] = basename($folderName);
                }
            }
            return $folderList;
        }

        public static function getFiles($path): array {

            $filesList = [];
            foreach(glob(sprintf("%s%s/*.*",ABSPATH,$path)) as $filename){
                if(is_file($filename)){
                    $basename = basename($filename);
                    $filesList[] = [
                        'name' => $basename,
                        'location' => sprintf("/%s/%s",$path,$basename),
                        'modified' => date('F d, Y \a\t g:i:s a', filemtime($filename)),
                        'modifiedDay' => date('n/j/y ', filemtime($filename))
                    ];
                }
            }
            return $filesList;
        }



    }