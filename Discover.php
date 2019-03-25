<?php

// require __DIR__ . '/config.php';
class Discover{

    private $token;
    function __construct(){
        global $config;
        $this->setToken($config);
    }

    private function setToken($config){
        
        $username = $config["discover"]["username"];
        $password = $config["discover"]["password"];
        $partnerKey = $config["discover"]["partnerKey"];
        $ch =  curl_init($config['discover']['baseEndpoint'].'login');
       // $data_string="{\"username\": \"".$username."\",""\"password\": \"".$password."\",""\"partnerKey\":\"".$partnerKey."\"}";
        $data_string="{ \"username\": \"".$username."\", \"password\": \"".$password."\", \"partnerKey\" : \"".$partnerKey."\"}";
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch,  CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $response = curl_exec($ch);
         
        preg_match_all('/^X-AUTH-TOKEN:\s*([^;]*)/mi', $response, $matches); 
        $this->token =  substr($matches[0][0],0,strpos($matches[0][0],"Strict-Transport-Security:")) ; 
        $this->token =  substr($this->token,13) ;    
        $this->token ;
    }

    public function getToken(){

        return $this->token;
    }

    public function searchPersonByEmail($emails){
        global $config;
        $ch =  curl_init($config['discover']['baseEndpoint'].'v1/search/persons');
        $data_string='{"personCriteria":{"emails":'.$this->arrayToString($emails).'}}';
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch,  CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('accept: application/json','Content-Type: application/json','X-AUTH-TOKEN:'.trim($this->getToken()),'X-PARTNER-KEY: '. $config["discover"]["partnerKey"]));
     
        $response = json_decode(curl_exec($ch),false);
        curl_close($ch);
        return $response->content;
    }
    public function searchCompanyByDomain($emails){
        global $config;
        
        $ch =  curl_init($config['discover']['baseEndpoint'].'v1/search/companies');
        $data_string='{"companyCriteria":{"emailDomains":'.$this->arrayToString($emails).'}}';
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch,  CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('accept: application/json','Content-Type: application/json','X-AUTH-TOKEN:'.trim($this->getToken()),'X-PARTNER-KEY: '. $config["discover"]["partnerKey"]));
        $response = json_decode(curl_exec($ch),false);
        curl_close($ch);
        return $response->content;
    }

    public function arrayToString($array){
        
       return $result = '["' . implode ( '","', $array ) . '"]';

    }
}

// $instance = new Discover();
// $instance->searchCompanyByDomain(array('canpango.com'))->content;