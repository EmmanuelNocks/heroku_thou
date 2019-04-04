<?php
require_once __DIR__.'/vendor/autoload.php';
require_once dirname(__FILE__).'/config.php';
require_once dirname(__FILE__).'/Clearbit.php';
require_once dirname(__FILE__).'/Pardot.php';
require_once dirname(__FILE__).'/Discover.php';
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
date_default_timezone_set("America/Chicago");
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
        global $config;
        $plainEmail = $email;
        $email = array($email);
        $domain = explode('@',$plainEmail);
        $person = $this->discover->searchPersonByEmail($email);
        $company =  $this->discover->searchCompanyByDomain(array($domain[1]));
        $allData = array();
        $keys = array(
            'Data_Enrichment_Complete'=>true,
            'api_key' =>$this->pardot->getApiKey(),
            'user_key' => $config["pardot"]["user_key"],
            'format'=>'json'
        );

       $discoverdata =  $this->discoverCallback($pID,$company,$person);

       if(count($discoverdata)>0){

        $person1 =  $this->clearbit->searchPersonByEmail($plainEmail);
        $company1 =  $this->clearbit->searchCompanyByDomain($domain[1]); 

        if(!isset($person1->error)&&!isset($company1->error)){

            $clearbitData = $this->clearbitCallback($pID,$company1,$person1);
        }
        else{

            sleep(5); //retry
            $person1 =  $this->clearbit->searchPersonByEmail($plainEmail);
            $company1 =  $this->clearbit->searchCompanyByDomain($domain[1]); 

            $clearbitData = $this->clearbitCallback($pID,$company1,$person1);

        }


       }
       else{

        $person1 =  $this->clearbit->searchPersonByEmail($plainEmail);
        $company1 =  $this->clearbit->searchCompanyByDomain($domain[1]); 

        if(!isset($person1->error)&&!isset($company1->error)){

            $clearbitData = $this->clearbitCallback($pID,$company1,$person1);
        }
        else{

            sleep(5); //retry
            $person1 =  $this->clearbit->searchPersonByEmail($plainEmail);
            $company1 =  $this->clearbit->searchCompanyByDomain($domain[1]); 

            $clearbitData = $this->clearbitCallback($pID,$company1,$person1);

        }

       }

       if(count($clearbitData)>0||count($discoverdata)>0){

        $allData = array_merge($clearbitData,$discoverdata,$keys);
        $results= $this->pardot->runUpdate($pID,$allData);
       
        if($results['code']==200){
            echo 'Successful \n';
        }
        else{
            echo $results['res']->err;
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
                echo 'Successful , but data not found';
            }
            else{
                echo $results['res']->err;
            }

        }
        
    }


    public function post($data){
        global $config;
        if(count($data)>0){
                
                $pID = trim($data[0]);
                $email = array(trim($data[1]));
                $plainEmail = trim($data[1]);
                $domain = explode('@',$plainEmail);
                $person = $this->discover->searchPersonByEmail($email);
                $company =  $this->discover->searchCompanyByDomain(array($domain[1]));
                $allData = array();
                $keys = array(
                    'Data_Enrichment_Complete'=>true,
                    'api_key' =>$this->pardot->getApiKey(),
                    'user_key' => $config["pardot"]["user_key"],
                    'format'=>'json'
                );
        
               $discoverdata =  $this->discoverCallback($pID,$company,$person);
        
               if(count($discoverdata)>0){
        
                $person1 =  $this->clearbit->searchPersonByEmail($plainEmail);
                $company1 =  $this->clearbit->searchCompanyByDomain($domain[1]); 
        
                if(!isset($person1->error)&&!isset($company1->error)){
        
                    $clearbitData = $this->clearbitCallback($pID,$company1,$person1);
                }
                else{
        
                    sleep(5); //retry
                    $person1 =  $this->clearbit->searchPersonByEmail($plainEmail);
                    $company1 =  $this->clearbit->searchCompanyByDomain($domain[1]); 
        
                    $clearbitData = $this->clearbitCallback($pID,$company1,$person1);
        
                }
        
        
               }
               else{
        
                $person1 =  $this->clearbit->searchPersonByEmail($plainEmail);
                $company1 =  $this->clearbit->searchCompanyByDomain($domain[1]); 
        
                if(!isset($person1->error)&&!isset($company1->error)){
        
                    $clearbitData = $this->clearbitCallback($pID,$company1,$person1);
                }
                else{
        
                    sleep(5); //retry
                    $person1 =  $this->clearbit->searchPersonByEmail($plainEmail);
                    $company1 =  $this->clearbit->searchCompanyByDomain($domain[1]); 
        
                    $clearbitData = $this->clearbitCallback($pID,$company1,$person1);
        
                }
        
               }
        
               if(count($clearbitData)>0||count($discoverdata)>0){
       
                $allData = array_merge($clearbitData,$discoverdata,$keys);
               
                $results= $this->pardot->runUpdate($pID,$allData);
              
                if($results['code']==200){
                    echo 'Successful \n';
                }
                else{
                    echo $results['res']->err;
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
                        echo 'Successful, but data not found\n';
                    }
                    else{
                        echo $results['res']->err;
                    }
                }
                


    }
}

public function clearbitCallback($pID,$company,$person){
    global $config;
    $foud=false;
    if(!isset($company->error)&&!isset($person->error)){

        $found =true;
        $data = array(
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
            'Clearbit_Industry'=>$company->category->industry
        );

    }
    else{

        if(!isset($company->error)){

            $found =true;
            $data = array(
                'clearbitCompanyLogo'=>$company->logo,
                'Clearbit_Phone'=>count($company->phone->site->phoneNumbers)>0?$company->phone->site->phoneNumbers[0]:null,
                'Clearbit_HQ_Street_Number'=>$company->geo->streetNumber,
                'Clearbit_HQ_Street_Name'=>$company->geo->streetName,
                'Clearbit_HQ_State'=>$company->geo->state,
                'Clearbit_HQ_Zip_Code'=>$company->geo->postalCode,
                'Clearbit_HQ_Country'=>$company->geo->country,
                'Clearbit_HQ_City'=>$company->geo->city,
                'Clearbit_Industry'=>$company->category->industry
            );
        }
        elseif(!isset($person->error)){

            $found =true;
            $data = array(
                'Clearbit_State'=>$person->geo->state,
                'Clearbit_Country'=>$person->geo->country,
                'Clearbit_Title'=>$person->employment->title
            );
        }
        else{
            $found =false;
        }
    }

    if($found){
        return $data;
    }
    else{

    return array();

    }
}

//__________________________________________________________________________________________________________
public function discoverCallback($pID,$company){
    global $config;
    $foud=false;
        if(count($company)>0&&count($person)>0){
            $company = $company[0];
            $person = $person[0];
            $found =true;
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
        }
        else{

            if(count($company)>0){
                $company = $company[0];
                $found =true;
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
                    'DO_Phone'=>$company->mainPhoneNumber
                );
            }
            elseif(count($person)>0){
                $person = $person[0];
                $found =true;
                $data = array(
                    'Data_Enrichment_Complete'=>true,
                    'DO_Employees__c_lead'=>'',
                    'DO_Account_Zip_Code'=>$person->location->postalCode,
                    'DO_Address'=>$person->location->streetAddress1,
                    'DSCORGPKG_DiscoverOrg_ID'=>$person->id,
                    'DO_MobilePhone'=>$person->officeTelNumber,
                    'DO_State'=>$person->location->stateProvinceRegion,
                    'DO_Title'=>$person->title,
                    'DO_City' =>$person->location->city,
                    'DO_Country' => $person->location->countryName
                );
            }
            else{
                $found =false;
            }
        }



        if($found){

        return $data;

        }
        else{
            
            return array();
        }
}

public function getProspects($t1,$t2){
    global $config;
    $data = array(
        'Data_Enrichment_Complete'=>0,
        'created_after'=>$t1,
        'created_before'=>$t2,
        'api_key' =>$this->pardot->getApiKey(),
        'user_key' => $config["pardot"]["user_key"],
        'sort_by'=>'id',
        'format'=>'json',
        'limit'=>'200'
    );

    $results = $this->pardot->queryByDateTime($data)['res'];
    $toatresults = $results->result->prospect;
    $offset =200;
    
    while(count($results->result->prospect)){
       
        $data = array(
            'created_after'=>$t1,
            'created_before'=>$t2,
            'api_key' =>$this->pardot->getApiKey(),
            'user_key' => $config["pardot"]["user_key"],
            'format'=>'json',
            'offset'=>$offset
        );

        $results = $this->pardot->queryByDateTime($data)['res'];

        if(count($results->result->prospect)!=0)
        {
            $toatresults = array_merge($toatresults,$results->result->prospect);
        }

        $offset =  $offset +200;
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
//]);
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
    if(isset($_POST["pardotid"]) && isset($_POST["email"])){

        $connection = $app['amqp']['default'];
        $channel = $connection->channel();

        $channel->queue_declare('post_queue', false, true, false, false);
        $msg = new AMQPMessage($_POST["pardotid"].";".$_POST["email"], array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
        $channel->basic_publish($msg, '', 'post_queue');
        echo " [x] Sent'\n";
        $channel->close();
        $connection->close();
    }
    elseif (isset($_GET["pardotid"]) && isset($_GET["email"])) {
        $connection = $app['amqp']['default'];
        $channel = $connection->channel();

        $channel->queue_declare('post_queue', false, true, false, false);
        $msg = new AMQPMessage($_GET["pardotid"].";".$_GET["email"], array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
        $channel->basic_publish($msg, '', 'post_queue');
        echo " [x] Sent'\n";
        $channel->close();
        $connection->close();
    }


}
catch(Exeption $e){

    print_r($e);
}
?>