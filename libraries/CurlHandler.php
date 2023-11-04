<?php


    class CurlHandler {

        public string $url;
        public array $headers;
        public array $options;

        public function __Construct($url,$headers,$options){
            $this->url = $url;
            $this->headers = $headers;
            $this->options = $options;
        }

        private function getDefaultOptions(){
            return array(
                CURLOPT_URL => $this->url,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => $this->headers,
                CURLOPT_RETURNTRANSFER => true,
            );
        }

        public function makeRequest():string{
            $curl = curl_init();
            $options = $this->options+$this->getDefaultOptions();
            curl_setopt_array($curl, $options);
            $result = curl_exec($curl);
            curl_close($curl);
            return $result;
        }

    }