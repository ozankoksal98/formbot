<?php

    include_once "Form.php";
    include_once "DiscordClient.php";

    class DiscordIntegration{
        private $partnerName = 'discord';
        private $apiKey = "fb164eb729c73fd5456f005f93a2715c";
        private $user;
        private $submissionID;
        private $api;
        
        

        public function __construct(  $token ,$guildID){
            $this->api = new DiscordClient($token ,$guildID);
        }

        public function getApiKey(){
            return $this->apiKey;
        }
        
        public function getSubmission($submID,$formID){
            $temp = Requests::getRequest("https://api.jotform.com/form/".$formID."/submissions?apiKey=".$this->apiKey,null);
            foreach(json_decode($temp,true)["content"] as $key){
                if($key["id"] == $submID){
                    return $key;
                }
            }
            return null;
        }

        public function getFormNew($formID){
            return new Form($formID,$this->apiKey);
        }


        public function listForms(){
            //only list enabled forms
            $formData = json_decode(Requests::getRequest("https://api.jotform.com/user/forms?".'filter=%7B"status"%3A"ENABLED"%7D',
                array("APIKEY: ".$this->apiKey)),true)["content"];

            foreach($formData as $f=>$v){
                echo $f." ) ";
                echo "Title: ". $v["title"] . "<br>";
                echo "Submission count: ". $v["count"] . "<br>";
                echo "Last submission: ". $v["last_submission"] . "<br>";
                echo "<br>";

            }
        }

        public function getForms(){
            return json_decode(Requests::getRequest("https://api.jotform.com/user/forms",
                array("APIKEY: ".$this->apiKey)),true)["content"];
        }
        
        
        public function buildMessage($questions,$formID,$submID,$notes){
            $form = new Form($formID, $this->apiKey);
            $value = '{"title" : "Form Submission",';
            $value .= ' "description": "You have received a new submission for your form: ['. $form->getTitle().'](https://www.jotform.com/'.$formID.')" ,';
            $value .= '"url": "https://www.jotform.com/inbox/'.$formID.'/'.$submID .'",';
            $value .= '"color": 16482326,';
            $value .= '"footer": {
                "icon_url": "https://cdn.discordapp.com/attachments/744853637385420924/746281636680695828/jotform-icon-transparent-560x560.png",
                "text": "Jotform "
              },"thumbnail": {
                "url": "https://cdn.discordapp.com/attachments/744853637385420924/746281306832371742/jotform-logo-orange-800x800.png"
              },"author": {
                "name": "JotForm",
                "url": "https://jotform.com",
                "icon_url": "https://cdn.discordapp.com/attachments/744853637385420924/746281636680695828/jotform-icon-transparent-560x560.png"
              },"fields": [';
            
            $answers = $this->getSubmission($submID,$formID)['answers'];
            //print_r($answers);
            usort($answers,function($a,$b){
                return intval($a['order'])- intval($b['order']);
            });

            $val_arr = array();
            $skippedFields = ['control_button','control_head','control_captcha','control_divider','control_text','control_image'];
            foreach($answers as $key){
                if(!in_array($key['type'],$skippedFields) && in_array($key['order'],$questions)){
                    if(isset($key['prettyFormat'])){
                        if($key['type']=='control_address'){
                            $str = $key['answer']['addr_line1'].','.$key['answer']['addr_line2'].'\n';
                            $str .= $key['answer']['city'].','.$key['answer']['state'].','.$key['answer']['postal'].'\n';
                            $str .= $key['answer']['country'].'\n';
                            $val_arr[] =  '{
                                "name": "'.$key['text'].'",
                                "value": "'.$str.'"
                              }';
                            
                        }else if($key['type']=='control_datetime'){
                            $dateFormat = array();
                            foreach($key['answer'] as $k=>$v){
                                $dateFormat[] = $k[0];
                            }
                            $dateFormat = implode('-',$dateFormat);
                            $val_arr[] =  '{
                                "name": "'.$key['text'].'",
                                "value": "'.$key['prettyFormat'].','.$dateFormat.'"
                              }';

                        }else if($key['type']=='control_inline'){
                            $str = "";
                            $i =1 ;
                            foreach(array_values($key['answer']) as $val){
                                $str .=  'Blank '.strval($i).' : '. $val . '\n';
                                $i++;
                            }
                            $val_arr[] =  '{
                                "name": "Blanks :",
                                "value": "'.$str.'"
                              }';

                        }else if($key['type']=='control_fileupload'){
                            //bury links
                            $url = $key['prettyFormat'];
                            $joinedValue= [];
                            $allLinks;
                            preg_match_all('/href=".*?"/', $url, $allLinks);
                            foreach($allLinks[0] as $link){
                                $link = str_replace(['href="','"'], '', $link);
                                array_push($joinedValue, $link);
                            }
                            $titles = [];
                            foreach($key["answer"] as $k){
                                $title = explode($submID."/",$k);
                                $titles[] = $title[count($title)-1];
                            };
                            $str = [];
                            for($i = 0 ;$i <count($titles);$i++ ){
                                $str[]='['.$titles[$i].']('.$joinedValue[$i].')';
                            }
                            
                            $val_arr[] =  '{
                                "name": "'.$key['text'].'",
                                "value": "'.implode('\n',$str).'"
                              }';
                        }
                        
                        else if($key['type']=='control_matrix'){
                            $str = "";
                            foreach($key['answer'] as $k=>$v){
                                $str .= $k.' : '.$v.'\n';
                            }
                            $val_arr[] =  '{
                                "name": "'.$key['text'].'",
                                "value": "'.$str.'"
                              }';
                        }
                        
                        else if($key['type']=='control_payment'){
                            $payArr = json_decode($key['answer']['paymentArray'],true);
                            $str="";
                            foreach($payArr['product'] as $k=> $v){
                                $temp = explode('(',$v);
                                $str .= $temp[0];
                                $temp = explode(',',$temp[1]);
                                if(count($temp)>1){
                                    $str .= '('.str_replace([' Quantity: ',')'],'',$temp[1])  .') : ';
                                    $str .= str_replace(['Amount: ',')'],'',$temp[0]). '\n';

                                }else{
                                    $str .= ' : '.str_replace(['Amount: ',')'],'',$temp[0]) . '\n';
                                }
                            

                            };
                            $str .= 'Total : '.$payArr['total'].' '.$payArr['currency'].'\n';
                            $val_arr[] =  '{
                                "name": "'.$key['text'].'",
                                "value": "'.$str.'"
                              }';

                            
                        }

                        else{
                            $val_arr[] =  '{
                                "name": "'.$key['text'].'",
                                "value": "'.$key['prettyFormat'].'"
                              }';
                        }
                        
                    }else{
                        if(is_array($key['answer'])){
                            $str .= implode(', ',array_values($key['answer']));
                            $str .= '\n';
                            $val_arr[] =  '{
                                "name": "'.$key['text'].'",
                                "value": "'.$str.'"
                              }';
                        }else if($key["type"]=="control_signature"){
                            $val_arr[] =  '{
                                "name": "'.$key['text'].'",
                                "value": "[Signature]('.$key['answer'].')"
                              }';
                        
                        }else{
                            $val_arr[] =  '{
                                "name": "'.$key['text'].'",
                                "value": "'.$key['answer'].'"
                              }';
                        }
                    }
                }
            }
            $value .= implode(",",$val_arr);
            if($notes !="" && !empty($questions)){
                $value .= ',{
                "name": "Notes and comments",
                "value": "'.$notes.'"
              }';
            }else if ($notes !=""){
                $value .= '{
                    "name": "Notes and comments",
                    "value": "'.$notes.'"
                  }';
            }
            
            $value .= ' ]
        }';
            return $value;
        }

        public function getApi()
        {
                return $this->api;
        }
    }




    

    //print_r($int->buildMessage());