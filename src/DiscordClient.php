<?php

require_once "Requests.php";
/**
 * Undocumented class
 */
class DiscordClient
{
    protected $baseUrl = "https://discord.com/api";
    protected $token ;
    protected $authHeader ;
    protected $guildID ;
    protected $integration;

    public function __construct($token,$guildID)
    {
        $this->token = $token;
        $this->authHeader = 'Authorization: Bot ' . $token;
        $this->guildID = $guildID;
    }

    /**
     * This method returns the authentication header.
     *
     * @return string
     */
    public function getauthHeader()
    {
        return $this->authHeader;
    }

    //get gateway api uri
    public function getGateway()
    {
        return Requests::getRequest($this->baseUrl."/gateway", array($this->authHeader));
    }

    //get gateway bot info
    public function getGatewayBot()
    {
        return Requests::getRequest($this->baseUrl."/gateway/bot", array($this->authHeader));
    }

    //Gets info about user object
    public function getUser()
    {
        return Requests::getRequest($this->baseUrl."/users/@me", array($this->authHeader));
    }

    //Gets info about servers that the current user is in
    public function getCurrentUserGuilds()
    {
        return Requests::getRequest($this->baseUrl."/users/@me/guilds", array($this->authHeader));
    }

    public function getUserConnections()
    {
        return Requests::getRequest($this->baseUrl."/users/@me/connections", array($this->authHeader));
    }
    
    public function getGuildMembers()
    {
        return Requests::getRequest($this->baseUrl."/guilds/".$this->guildID."/members?limit=1000", array($this->authHeader));
    }

    public function getGuildChannels()
    {
        return Requests::getRequest($this->baseUrl."/guilds/".$this->guildID."/channels", array($this->authHeader));
    }

    public function createDmChannel($user_id)
    {
        return Requests::postRequest($this->baseUrl."/users/@me/channels", json_encode(array("recipient_id"=>$user_id)), array("Content-Type: application/json",$this->authHeader));
    }
    

    public function sendTestMessage($channelID)
    {
        echo Requests::postRequest(
            $this->baseUrl."/channels/".$channelID."/messages", json_decode(
                '{
            "content": "Hello, World!",
            "tts": "false"
        }', true
            ), array("Content-Type: multipart/form-data",$this->getauthHeader())
        );
    }

    public function sendMessage($channelID,$content,$embed)
    {
        //Send custom message
        //can only send text up to 2000 characters
        return Requests::postRequest(
            $this->baseUrl."/channels/".$channelID."/messages", array("content"=>$content,"tts"=> "false","payload_json"=>'{"embed": '.$embed."}"),
            array("Content-Type: multipart/form-data",$this->getauthHeader())
        );
    }

    public function sendTextMessage($channelID,$content)
    {
        return Requests::postRequest(
            $this->baseUrl."/channels/".$channelID."/messages", array("content"=>$content,"tts"=> "false"),
            array("Content-Type: multipart/form-data",$this->getauthHeader())
        );
    }

    public function parseResponse($json_string)
    {
        return json_decode($json_string, true);
    }

    public function getTextChannels()
    {
        $channelArr = json_decode($this->getGuildChannels(), true);
        foreach ($channelArr as $channel=>$v) {
            //remove all channels and categories except text channels
            if ($v["type"]!="0" ) {
                unset($channelArr[$channel]);
            }
        }
        usort(
            $channelArr, function ($a,$b) {
                return intval($a["position"])-intval($b["position"]);
            }
        );
        return $channelArr;
    }
    
}

?>
