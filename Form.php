<?php
    include_once "requests.php";
    
    class Form{
        private $apiKey ;
        private $formInfo;
        private $title;
        private $text_val;      //raw text form of get requests return value
        private $questions;     //form questions array
        private $properties;    //form properties array
        private $formID;



        public function __construct($formID,$apiKey){
            $this->formID = $formID;
            $this->apiKey = $apiKey;
            $this->formInfo = json_decode(Requests::getRequest("https://api.jotform.com/form/".$this->formID."?apiKey=".$this->apiKey,null),true)["content"];
            $this->questions = json_decode(Requests::getRequest("https://api.jotform.com/form/".$this->formID."/questions?apiKey=".$this->apiKey,null),true)["content"];
            $this->title = $this->formInfo["title"];
        }

        public function getSubmissions(){
            $temp = json_decode(Requests::getRequest("https://api.jotform.com/form/".$this->formID."/submissions?apiKey=".$this->apiKey,null),true)["content"];
            foreach($temp as $k =>$v){
                $t = $v["answers"];
                usort($t,function($a,$b){
                    return intval($a["order"]) - intval($b["order"]);
                });
                $temp[$k]["answers"] = $t;
            }
            return $temp;
        }


        public function getQuestions(){
            //sort questions
            
            
            return $this->questions;
        }

        public function getProperties(){
            return json_decode(Requests::getRequest("https://api.jotform.com/form/".$this->formID."/properties?apiKey=".$this->apiKey,null),true)["content"];
        }
        

        public function getTitle()
        {
                return $this->title;
        }
    }
