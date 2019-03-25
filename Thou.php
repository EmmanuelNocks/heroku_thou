<?php

require_once dirname(__FILE__).'/config.php';
require_once dirname(__FILE__).'/Clearbit.php';
require_once dirname(__FILE__).'/Pardot.php';
require_once dirname(__FILE__).'/Discover.php';
// Logger::configure(dirname(__FILE__).'/systemlog/syslog2.xml');
date_default_timezone_set("Africa/Johannesburg");
class Thou{

   public $log;
   private $clearbit;
   private $pardot;
   private $discover;

    function __construct(){

            // $this->log = Logger::getRootLogger(); //track logs
            try{

                $this->clearbit = new Clearbit();
                $this->pardot = new Pardot();
                $this->discover = new Discover();
            }
            catch(Exception $ex){
                
                // $this->log->warn($ex);
            }

    }

    public function post(){
        global $config;

        if(isset($_POST["pardotid"]) && isset($_POST["email"])){

            $pID = $_POST["pardotid"];
            $email = array($_POST["email"]);
            $plainEmail = $_POST["email"];
            $domain = explode('@',$plainEmail);
       


       $person = $this->discover->searchPersonByEmail($email);
       $company =  $this->discover->searchCompanyByDomain(array($domain[1]));
       if(count($person)>0 && count($company)>0){

        $this->discoverCallback($pID,$company[0],$person[0],true);
           
       }
       else{

            $person =  $this->clearbit->searchPersonByEmail($plainEmail);
            $company =  $this->clearbit->searchCompanyByDomain($domain[1]); 

            if(!isset($person->error)&&!isset($company->error)){
            
                $this->clearbitCallback($pID,$company,$person,true);
            }
            else{
              
                sleep(5); //retry
                $person =  $this->clearbit->searchPersonByEmail($plainEmail);
                $company =  $this->clearbit->searchCompanyByDomain($domain[1]); 

                if(!isset($person->error)&&!isset($company->error)){
                    $this->clearbitCallback($pID,$company,$person,true);
                }
                else{
                    $this->clearbitCallback($pID,$company,$person,false);
                }

            }


       }


    }
}

public function clearbitCallback($pID,$company,$person,$found){
    global $config;

    if($found){
    $data = array(
        'Data_Enrichment_Complete'=>true,
        'clearbitCompanyLogo'=>$company->logo,
        'Clearbit_State'=>$person->geo->state,
        'Clearbit_Country'=>$person->geo->country,
        'Clearbit_Title'=>$person->employment->title,
        'Clearbit_Phone'=>count($company->phone->site->phoneNumbers)>0?$company->phone->site->phoneNumbers[0]:null,
        'Clearbit_HQ_Street_Number'=>$company->geo->streetNumber,
        'Clearbit_HQ_Street_Name'=>$company->geo->streetName,
        'Clearbit_HQ_State'=>$company->geo->state,
        'Clearbit_HQ_Zip_Code'=>$company->geo->postalCode,
        'Clearbit_HQ_Country'=>$company->geo->country,
        'Clearbit_HQ_City'=>$company->geo->city,
        'Clearbit_Industry'=>$company->category->industry,
        'api_key' =>$this->pardot->getApiKey(),
        'user_key' => $config["pardot"]["user_key"],
        'format'=>'json'
    );

    $results= $this->pardot->runUpdate($pID,$data);

    if($results['code']==200){
        echo 'Successful';
    }
}
else{

    $data = array(
        'Data_Enrichment_Complete'=>true,
        'api_key' =>$this->pardot->getApiKey(),
        'user_key' => $config["pardot"]["user_key"],
        'format'=>'json'
    );

    $results= $this->pardot->runUpdate($pID,$data);

    if($results['code']==200){
        echo 'Successful, but data not found';
    }
}
}

//__________________________________________________________________________________________________________
public function discoverCallback($pID,$company,$person,$found){
    global $config;

    if($found){

    $data = array(
        'Data_Enrichment_Complete'=>true,
        'DO_Account_Address'=>$company->location->streetAddress1,
        'DO_Account_City'=>$company->location->city,
        'DO_Account_Country'=>$company->location->countryName,
        'DO_Employees__c_lead'=>'',
        'DO_Industry'=>$company->industry,
        'DO_Company'=>$company->name,
        'DO_Account_Phone'=>$company->mainPhoneNumber,
        'DO_Revenue'=>$company->revenue,
        'DO_Account_State'=>$company->location->stateProvinceRegion,
        'DO_Website'=>$company->websiteUrl,
        'DO_Account_Zip_Code'=>$person->location->postalCode,
        'DO_Address'=>$person->location->streetAddress1,
        'DSCORGPKG_DiscoverOrg_ID'=>$person->id,
        'DO_MobilePhone'=>$person->officeTelNumber,
        'DO_Phone'=>$company->mainPhoneNumber,
        'DO_State'=>$person->location->stateProvinceRegion,
        'DO_Title'=>$person->title,
        'DO_City' =>$person->location->city,
        'DO_Country' => $person->location->countryName
    );

    $results= $this->pardot->runUpdate($pID,$data);

    if($results['code']==200){
        echo 'Successful';
    }
}
else{

    $data = array(
        'Data_Enrichment_Complete'=>true,
        'api_key' =>$this->pardot->getApiKey(),
        'user_key' => $config["pardot"]["user_key"],
        'format'=>'json'
    );

    $results= $this->pardot->runUpdate($pID,$data);

    if($results['code']==200){
        echo 'Successful, but data not found';
    }
}
}

public function getProspects($t1,$t2){
    global $config;
    $data = array(
        'created_after'=>$t1,
        'created_before'=>$t2,
        'api_key' =>$this->pardot->getApiKey(),
        'user_key' => $config["pardot"]["user_key"],
        'format'=>'json'
    );
    $results= $this->pardot->queryByDateTime($data);

    print_r( $data);
}

}


$instance = new Thou();
$instance->post();
