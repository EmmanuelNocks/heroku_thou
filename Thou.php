<?php
require_once __DIR__.'/vendor/autoload.php';
require_once dirname(__FILE__).'/config.php';
require_once dirname(__FILE__).'/Clearbit.php';
require_once dirname(__FILE__).'/Pardot.php';
require_once dirname(__FILE__).'/Discover.php';
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
date_default_timezone_set("America/New_York");
$app = new Silex\Application();
class Thou{

   public $log;
   private $clearbit;
   private $pardot;
   private $discover;

    function __construct(){

   
            try{

                $this->clearbit = new Clearbit();
                $this->pardot = new Pardot();
                $this->discover = new Discover();
            }
            catch(Exception $ex){
                
                // $this->log->warn($ex);
            }

    }

    public function lookUpProspect($pID,$email){

        $email = array($email);
        $plainEmail = $email;
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


    public function post(){
        global $config;
       
        if((isset($_POST["pardotid"]) && isset($_POST["email"]))||(isset($_GET["pardotid"]) && isset($_GET["email"]))){
        
            if(isset($_GET["pardotid"]) && isset($_GET["email"])){
                
                $pID = $_GET["pardotid"];
                $email = array($_GET["email"]);
                $plainEmail = $_GET["email"];
                $domain = explode('@',$plainEmail);
            }
            else{

                $pID = $_POST["pardotid"];
                $email = array($_POST["email"]);
                $plainEmail = $_POST["email"];
                $domain = explode('@',$plainEmail);
            }

       


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
        'sort_by'=>'created_at',
        'sort_order'=>'ascending',
        'format'=>'json',
        'limit'=>'200'
    );
 
    $results = $this->pardot->queryByDateTime($data)['res'];
    $toatresults = $results->result->prospect;

    while($results->result->total_results==200 && $results->result->total_results!=1){

        $data = array(
            'created_after'=>$results->result->prospect[$results->result->total_results-1]->created_at,
            'created_before'=>$t2,
            'api_key' =>$this->pardot->getApiKey(),
            'user_key' => $config["pardot"]["user_key"],
            'format'=>'json'
        );
        
        $results = $this->pardot->queryByDateTime($data)['res'];
       
        if($results->result->total_results>0){
            $toatresults = array_merge($toatresults,$results->result->prospect);
        }
    }
    
   return $toatresults;
}

//Testing
public function createProspects(){
    global $config;
    $data = array(
        'first_name'=>'Nocks',
        'last_name'=>'test',
        'api_key' =>$this->pardot->getApiKey(),
        'user_key' => $config["pardot"]["user_key"]
    );
   
    $results= $this->pardot->create($data)['res'];
    print_r($data);
}
}


// $instance = new Thou();
// $instance->post();

// RabbitMQ connection
//$rabbitmq = parse_url(getenv('CLOUDAMQP_URL'));
// $app->register(new Amqp\Silex\Provider\AmqpServiceProvider, [
//     'amqp.connections' => [
//         'default' => [
//             'host'     => $rabbitmq['host'],
//             'port'     => isset($rabbitmq['port']) ? $rabbitmq['port'] : 5672,
//             'username' => $rabbitmq['user'],
//             'password' => $rabbitmq['pass'],
//             'vhost'    => substr($rabbitmq['path'], 1) ?: '/',
//         ],
//     ],
// ]);
try{
    $rabbitmq = parse_url(getenv('CLOUDAMQP_URL'));
    $app->register(new Amqp\Silex\Provider\AmqpServiceProvider, [
        'amqp.connections' => [
            'default' => [
                'host'     => $rabbitmq['host'],
                'port'     => isset($rabbitmq['port']) ? $rabbitmq['port'] : 5672,
                'username' => $rabbitmq['user'],
                'password' => $rabbitmq['pass'],
                'vhost'    => substr($rabbitmq['path'], 1) ?: '/',
            ],
        ],
    ]);
            $connection = $app['amqp']['default'];
            $channel = $connection->channel();

$channel->queue_declare('task_queue', false, true, false, false);
$msg = new AMQPMessage('Hello World!');
$channel->basic_publish($msg, '', 'hello');
echo " [x] Sent 'Hello World!'\n";
$channel->close();
$connection->close();
}
catch(Exeption $e){

    print_r($e);
}
?>