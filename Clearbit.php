<?php


class Clearbit{
    private $token;

    function __construct(){
        global $config;
        
    }

    public function authorize(){
        global $config;
        $ch =  curl_init($config['clearbit']['baseEndpoint'].'oauth/authorize?client_id='.$config['clearbit']['client_id'].'&redirect_uri='.$config['clearbit']['redirect_uri']);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                     
        curl_setopt($ch,  CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('accept: application/json','Content-Type: application/json'));
        $response = json_decode(curl_exec($ch),false);
        print_r($response);
    }

    public function setToken(){

        $client_id = $config["clearbit"]["client_id"];
        $client_secret = $config["clearbit"]["client_secret"];
        $code = $_POST["code"];
        $ch =  curl_init($config['discover']['baseEndpoint'].'oauth/access_token');
        $data_string='{"client_id": "'.$client_id.'","client_secret": "'.$client_secret.'","code":"'.$code.'"}';
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch,  CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('accept: application/json','Content-Type: application/json'));
        $response = json_decode(curl_exec($ch),false);
        $this->token = $response->access_token; 
    }

    public function searchPersonByEmail($emails){
        
        global $config;
        $ch = curl_init($config['clearbit']['endpointPerson'].'people/find?email='.$emails);
        $authorization = "Authorization: Bearer ".$config['clearbit']['token'];
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                     
        curl_setopt($ch,  CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('accept: application/json','Content-Type: application/json',$authorization));
        
        $response = json_decode(curl_exec($ch),false);
        curl_close($ch);
        return $response;
    }

    public function searchCompanyByDomain($domain){
        
        global $config;
        $ch = curl_init($config['clearbit']['endpointCompany'].'companies/find?domain='.$domain);
        $authorization = "Authorization: Bearer ".$config['clearbit']['token'];
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                     
        curl_setopt($ch,  CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('accept: application/json','Content-Type: application/json',$authorization));
        $response = json_decode(curl_exec($ch),false);
        curl_close($ch);
        return $response ;
    }
}

// $instance = new Clearbit();
// $instance->searchPersonByEmail('matt@canpango.com');