
<?php

include_once "requests.php";
//to-do implement splitting longer messages
class DiscordClient{
    private $baseUrl = "https://discord.com/api";
    private $token ;
    private $authHeader ;
    private $guildID ;
    private $integration;

    public function __construct($token,$guildID){
        $this->token = $token;
        $this->authHeader = 'Authorization: Bot ' . $token;
        $this->guildID = $guildID;
    }

    public function getauthHeader(){
        //get authentication header
        return $this->authHeader;
    }

    public function getGateway(){
        //get gateway api 
        return Requests::getRequest($this->baseUrl."/gateway",array($this->authHeader));
    }

    public function getGatewayBot(){
        //get gateway bot info
        return Requests::getRequest($this->baseUrl."/gateway/bot",array($this->authHeader));
    }

    public function getUser(){
        //Gets info about user object
        return Requests::getRequest($this->baseUrl."/users/@me",array($this->authHeader));
    }

    public function getCurrentUserGuilds(){
        //Gets info about servers that the current user is in
        return Requests::getRequest($this->baseUrl."/users/@me/guilds",array($this->authHeader));
    }

    public function getUserConnections(){
        //get user connections spotify etc.
        return Requests::getRequest($this->baseUrl."/users/@me/connections",array($this->authHeader));
    }

    public function getGuildMembers(){
        return Requests::getRequest($this->baseUrl."/guilds/".$this->guildID."/members?limit=1000",array($this->authHeader));
    }

    public function getGuildChannels(){
        return Requests::getRequest($this->baseUrl."/guilds/".$this->guildID."/channels",array($this->authHeader));
    }

    public function createDmChannel($user_id){
        return Requests::postRequest($this->baseUrl."/users/@me/channels",json_encode(array("recipient_id"=>$user_id)),array("Content-Type: application/json",$this->authHeader));
    }
    

    public function sendTestMessage($channelID){
        //Send test message
        echo Requests::postRequest($this->baseUrl."/channels/".$channelID."/messages", json_decode('{
            "content": "Hello, World!",
            "tts": "false"
        }',true),array("Content-Type: multipart/form-data",$this->getauthHeader()));
    }

    public function sendMessage($channelID,$content,$embed){
    //Send custom message
    //can only send text up to 2000 characters
    return Requests::postRequest($this->baseUrl."/channels/".$channelID."/messages", array("content"=>$content,"tts"=> "false","payload_json"=>'{"embed": '.$embed."}"),
            array("Content-Type: multipart/form-data",$this->getauthHeader()));
    }

    public function sendTextMessage($channelID,$content){
        return Requests::postRequest($this->baseUrl."/channels/".$channelID."/messages", array("content"=>$content,"tts"=> "false"),
            array("Content-Type: multipart/form-data",$this->getauthHeader()));
    }

    public function parseResponse($json_string){
        return json_decode($json_string,true);
    }

    public function getTextChannels(){
        $channelArr = json_decode($this->getGuildChannels(),true);
        foreach($channelArr as $channel=>$v){
            //remove all channels and categories except text channels
            if($v["type"]!="0" ){
                unset($channelArr[$channel]);
            }
        }
        usort($channelArr,function($a,$b){
            return intval($a["position"])-intval($b["position"]);
        });
        return $channelArr;
    }
    
}
// "genel" channel id = 744853637385420924
// guild id = 744853637385420921
?>