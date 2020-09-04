<?php

    include_once("Client.php");
    include_once("DiscordIntegration.php");
    include_once("requests.php");

    $token = "NzQ1NjcwOTUzMTc4MjM0OTQw.Xz1KMQ.1tJN2Pd7AWPD7Pnaqv2VQyqzZ3Y";
    $integration = new DiscordIntegration($token, 744853637385420921);
    //202314428827050
    //202251323327039
    //$formID= "202251323327039";
    $client = new Client("wss://gateway.discord.gg:443");
    $client->receive() . "\n";
    $client->send('{
        "op": 2,
        "d": {
            "token": "'.$token.'",
            "properties": {
                "$os": "windows",
                "$browser": "chrome",
                "$device": "ozan2"
            },
            "intents": 4608
        }
    }');
    //$sessionID =json_decode($client->receive(), true)["d"]["session_id"];
    $author_id = "";
    $author_channel_id="";
    $content = "";
    $t = 0;
    $q = 0;
    $k = 0;
    $counter = 0;
    $counter_on = false;
    $questionAsked = false;
    $current_question = "";
    $skip = ['control_button','control_payment','control_captcha','control_divider','control_image','control_widget','control_signature','control_appointment','control_matrix'];
    $answers = [];
    $connectionStatus;
    $seqNumber='0';
    $tries = 0;
    $questions = null;
    $finished = false;
    $formChosen = false;

    try {
        while (true) {
            usleep(100000);
            if ($content=="-kick") {
                break;
            }
            if (!$finished) {
                //to do send heartbeat every 35 seconds
                if ($t == 400) {
                    $client->send('{
                        "op": 1,
                        "d":null
                    }');
                    echo "<BR>".$t."HEARTBEAT SENT<BR>"." at : ".date("h:i:s")."<BR>";
                    $t = 0;
                    
                }echo($t);
                $t++;

                //$integration->getApi()->sendTextMessage($author_channel_id, $client->receive(), "");
            }
            $sent = false;
            $break = false;
            while ($finished) {
                if (!$sent) {
                    //show submission summary
                    $str = "[";
                    $arr = [];
                    $qs = $form->getQuestions();
                    foreach ($answers as $k => $v) {
                        if (isset($qs[$k]["text"]) &&  $v["text"] != "No answer") {
                            if ($qs[$k]["type"] == "control_inline") {
                                $arr[] = '{"name":"Blanks","value":"'.implode(",", array_values($v)).'"}';
                            } elseif (count(array_values($v))==0) {
                                $arr[] = '{"name":"'.$qs[$k]["text"].'","value":"None"}';
                            } else {
                                $arr[] = '{"name":"'.$qs[$k]["text"].'","value":"'.implode(",", array_values($v)).'"}';
                            }
                        }
                    }
                    $str .= implode(",", $arr);
                    $str .= "]";
                    echo "<br><br>".$str."<br><br>";
                    echo $integration->getApi()->sendMessage($author_channel_id, " ", '{"title":"**Final preview**","color": 16328500,"fields": '.$str.'}');
                    usleep(2000000);
                    $integration->getApi()->sendTextMessage($author_channel_id, "Are you sure you want to submit the form with these answers?\nType:\n`-yes` to submit,\n`-no` to exit without submitting,\n`-refill` to fill the form again. ", "");
                    $sent = true;
                }
                $message = json_decode($client->receive(), true);
                if ($message["op"]=="0" && isset($message["d"]["content"]) && $message["d"]["author"]["id"]== $author_id && $message["d"]["channel_id"]==  $author_channel_id) {
                    if ($message["d"]["content"] == "-yes") {
                        $params = json_encode($answers);
                        echo "<br>".$params."<br>";
                        $params = "[".$params."]";
                        $headers = array(
                        "Content-Type: application/json"
                    );
                        $ch = curl_init();
                        curl_setopt_array($ch, array(
                        CURLOPT_URL => "https://api.jotform.com/form/".$formID."/submissions?apiKey=fb164eb729c73fd5456f005f93a2715c",
                        CURLOPT_SSL_VERIFYPEER=>false,
                        CURLOPT_POSTFIELDS =>$params,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_CUSTOMREQUEST => "PUT",
                        CURLOPT_HTTPHEADER => $headers,
                    ));
                        $result = curl_exec($ch);
                        curl_close($ch);
                        $integration->getApi()->sendTextMessage($author_channel_id, "Form Submitted", "");
                        $break = true;
                        break;
                    } elseif ($message["d"]["content"] == "-refill") {
                        $integration->getApi()->sendTextMessage($author_channel_id, "You can fill the form again", "");
                        $q = 0;
                        $answers = [];
                        $questionAsked = false;
                        $current_question = "";
                        $finished = false;
                    } elseif ($message["d"]["content"] == "-no") {
                        $integration->getApi()->sendTextMessage($author_channel_id, "Form Not Submitted", "");
                        $break = true;
                        break;
                    }
                }
            }

            if ($break) {
                break;
            }
            
        
            if (!$finished) {
                if ($content =="-spawn") {
                    $author_id = $message["d"]["author"]["id"];
                    $author_channel_id = $message["d"]["channel_id"];
                    $sent2 = false;
                    while (!$formChosen) {
                        //show forms
                        if (!$sent2) {
                            $temp = $integration->getForms();
                            $forms = [];
                            foreach ($temp as $k => $v) {
                                $forms[] = ($k+1)." : ".$v["title"];
                            }
                            $integration->getApi()->sendTextMessage($author_channel_id, "Available forms\n".implode("\n", $forms), "");
                            $sent2 = true;
                        }
                    
                        $message = json_decode($client->receive(), true);
                        if (isset($message) && substr($message["d"]["content"], 0, 3)=="-a "&& $message["op"]=="0" && isset($message["d"]["content"]) && $message["d"]["author"]["id"]== $author_id && $message["d"]["channel_id"]==  $author_channel_id) {
                            //set form id
                            $idx = intval(str_replace("-a ", "", $message["d"]["content"]))-1 ;
                            $formID = $temp[$idx]["id"];
                            $form = $integration->getFormNew(intval($formID));
                            $questions = $form->getQuestions();
                            usort($questions, function ($a, $b) {
                                return intval($a["order"])- intval($b["order"]);
                            });
                            $formChosen = true;
                        }
                    }
                    $integration->getApi()->sendTextMessage($author_channel_id, "Started filling form\nForm Name: ".$form->getTitle()."\nYou can skip any question with `-skip`", "");
                }
                if (!$questionAsked && $author_id!="") {
                    //ask question
                    $current_question = $questions[$q];
                    $current_question_text= "";
                    if (!in_array($current_question["type"], $skip)) {
                        if ($current_question["type"]== "control_head") {
                            $integration->getApi()->sendTextMessage($author_channel_id, "**__".$current_question["text"]."__**", "");
                            usleep(1000000);
                            $questionAsked = false;
                        } elseif ($current_question["type"]== "control_text") {
                            $temp = $current_question["text"];
                            $text = str_replace(['<p>','</p>',"\"","\'"], "", $temp);
                            $integration->getApi()->sendTextMessage($author_channel_id, $text, "");
                            usleep(1000000);
                            $questionAsked = false;
                        } elseif ($current_question["type"]== "control_fullname") {
                            $current_question_text= "**".$current_question["text"]."**"."\n"."Input your name and surname seperated with a comma `-a name(s),surname`";
                            $integration->getApi()->sendTextMessage($author_channel_id, $current_question_text, "");
                            $questionAsked = true;
                        } elseif ($current_question["type"]== "control_address") {
                            $current_question_text="**".$current_question["text"]."**"."\n"."Input your address in this format `-a Street Address, State, City, Zip Code`";
                            $integration->getApi()->sendTextMessage($author_channel_id, $current_question_text, "");
                            $questionAsked = true;
                        } elseif ($current_question["type"]== "control_datetime") {
                            $current_question_text= "**". $current_question["text"]."**"."\n"."Input the date in this format day,month,year;hours,minutes\nExample: `-a 31,08,2020;10,57`";
                            $integration->getApi()->sendTextMessage($author_channel_id, $current_question_text, "");
                            $questionAsked = true;
                        } elseif ($current_question["type"]== "control_fileupload") {
                            $current_question_text="**". $current_question["text"]."**"."\n"."Input the links in comma seperated form as `-a url1,url2,url3`";
                            $integration->getApi()->sendTextMessage($author_channel_id, $current_question_text, "");
                            $questionAsked = true;
                        } elseif ($current_question["type"]== "control_inline") {
                            $template = $current_question["template"];
                            $fields = json_decode($current_question["fields"], true);
                            var_dump($fields);
                            $template = str_replace(["<p>","</p>"], "", $template);
                            $temp = [];
                            foreach ($fields as $k) {
                                $template = str_replace("{".$k["options"]["0"]["id"]."}", "__  ".$k["options"]["0"]["label"]."  __", $template);
                                $temp[] = $k["options"]["0"]["label"];
                            }
                            $current_question_text= "**Fill in the blanks**\n".$template. "\nFill the blanks as `-a ".implode(", ", $temp)."`";
                            $integration->getApi()->sendTextMessage($author_channel_id, $current_question_text, "");
                            $questionAsked = true;
                        } elseif ($current_question["type"]== "control_dropdown" || $current_question["type"]== "control_radio") {
                            $options = explode("|", $current_question["options"]);
                            $current_question_text = "**".$current_question["text"]."**"."\nPick one of the options below by inputting a number from 1 to ".count($options)."\n".implode("\n", $options)."\nExample: `-a 2`";
                            $integration->getApi()->sendTextMessage($author_channel_id, $current_question_text, "");
                            $questionAsked = true;
                        } elseif ($current_question["type"]== "control_checkbox") {
                            $options = explode("|", $current_question["options"]);
                            $current_question_text ="**".$current_question["text"]."**"."\nPick options below by inputting comma seperated numbers x,y,z from 1 to".count($options)."\n".implode("\n", $options)."\nExample: `-a 1,3,4`";
                            $integration->getApi()->sendTextMessage($author_channel_id, $current_question_text, "");
                            $questionAsked = true;
                        } elseif ($current_question["type"]== "control_rating") {
                            $current_question_text = "**".$current_question["text"]."**"."\nFrom ".$current_question["scaleFrom"]." to ".$current_question["stars"]."\nExample: `-a 2`";
                            $integration->getApi()->sendTextMessage($author_channel_id, $current_question_text, "");
                            $questionAsked = true;
                        } elseif ($current_question["type"]== "control_scale") {
                            $current_question_text = "**".$current_question["text"]."**"."\nFrom ".$current_question["scaleFrom"]." to ".$current_question["scaleAmount"]."\nExample: `-a 2`";
                            $integration->getApi()->sendTextMessage($author_channel_id, $current_question_text, "");
                            $questionAsked = true;
                        } elseif ($current_question["type"]== "control_time") {
                            $current_question_text =  "**".$current_question["text"]."**"."\nInput the time as -a hours,minutes,{AM or PM}\nExample: `-a 12,50,AM`";
                            $integration->getApi()->sendTextMessage($author_channel_id, $current_question_text, "");
                            $questionAsked = true;
                        } else {
                            $current_question_text = "**".$current_question["text"]."**"."\nInput your answer as `-a answer`";
                            $integration->getApi()->sendTextMessage($author_channel_id, $current_question_text, "");
                            $questionAsked = true;
                        }
                    } else {
                        $integration->getApi()->sendTextMessage($author_channel_id, "Question skipped ~".$current_question["type"]."~", "");
                        usleep(1000000);
                        $questionAsked = false;
                    }
                    echo $current_question["type"];
                    $q++;
                }
          
                if ($questions!=null) {
                    if ($q==count($questions)) {
                        $finished = true;
                        continue;
                    }
                }
        

            
                try {
                    $message = json_decode($client->receive(), true);
                    
                    if (!isset($message)) {
                        continue;
                    } 
                } catch (Exception $ex) {
                    //var_dump($ex);
                }
            
               
            
                //print_r($message);
                if (isset($message) && $message["op"]=="0" && isset($message["d"]["content"])) {
                    $content = $message["d"]["content"];
                    if ($author_id!="" && $questionAsked) {
                        if (substr($message["d"]["content"], 0, 3)=="-a "&& $message["d"]["author"]["id"]== $author_id && $message["d"]["channel_id"]==  $author_channel_id) {
                            //question answered by the spawner
                            $answer = str_replace("-a ", "", $message["d"]["content"]);
                            echo "<br>".$answer."<br>";
                            //todo ,answer validation conditions will go below
                            if ($current_question["type"] == "control_fullname") {
                                $temp = explode(",", $answer);
                                $answers[$current_question["qid"]]["last"] = $temp[count($temp)-1];
                                unset($temp[count($temp)-1]);
                                $answers[$current_question["qid"]]["first"] = implode(" ", $temp);
                            } elseif ($current_question["type"] == "control_address") {
                                $temp = explode(",", $answer);
                                $answers[$current_question["qid"]]["addr_line1"] = $temp[0];
                                $answers[$current_question["qid"]]["state"] = $temp[1];
                                $answers[$current_question["qid"]]["city"] = $temp[2];
                                $answers[$current_question["qid"]]["postal"] = $temp[3];
                            } elseif ($current_question["type"] == "control_datetime") {
                                //day,month,year;hour,minutes
                                $temp = explode(";", $answer);
                                var_dump($temp);
                                $date = explode(",", $temp[0]);
                                $time = explode(",", $temp[1]);
                                $answers[$current_question["qid"]]["day"] = $date[0];
                                $answers[$current_question["qid"]]["month"] = $date[1];
                                $answers[$current_question["qid"]]["year"] = $date[2];
                            //$answers[$current_question["qid"]]["hour"] = $time[0];
                            //$answers[$current_question["qid"]]["minutes"] = $time[1];
                            } elseif ($current_question["type"] == "control_phone") {
                                $answers[$current_question["qid"]]["full"] = $answer;
                            } elseif ($current_question["type"] == "control_checkbox") {
                                $temp = [];
                                $i = 1;
                                $options = explode("|", $current_question["options"]);
                                $chosen = explode(",", $answer);
                                foreach ($options as $k) {
                                    if (in_array(intval($i), $chosen)) {
                                        $temp[] = $k;
                                    }
                                    $i++;
                                }
                                $answers[$current_question["qid"]]["text"] = implode("\n", $temp);
                            } elseif ($current_question["type"] == "control_radio" || $current_question["type"] == "control_dropdown") {
                                $temp = explode("|", $current_question["options"])[intval($answer)-1];
                                $answers[$current_question["qid"]]["text"] = $temp;
                            } elseif ($current_question["type"] == "control_time") {
                                $temp = explode(",", $answer);
                                //$answers[$current_question["qid"]]["timeInput"] = implode(";",array($temp[0],temp[1]));
                                $answers[$current_question["qid"]]["hourSelect"] = $temp[0];
                                $answers[$current_question["qid"]]["minuteSelect"] = $temp[1];
                                $answers[$current_question["qid"]]["ampm"] = $temp[2];
                            } else {
                                $answers[$current_question["qid"]]["text"] = $answer;
                            }
                        
                            //if the answer is valid set question asked to false
                            //else , keep it true
                            $questionAsked = false;
                        } elseif ($content == "-skip") {
                            $answers[$current_question["qid"]]["text"] = "No answer";
                            $questionAsked = false;
                        } elseif ($content == "-preview") {
                            //show preview
                            $str = "[";
                            $arr = [];
                            $qs = $form->getQuestions();
                            foreach ($answers as $k => $v) {
                                if (isset($qs[$k]["text"]) &&  $v["text"] != "No answer") {
                                    if ($qs[$k]["type"] == "control_inline") {
                                        $arr[] = '{"name":"Blanks","value":"'.implode(",", array_values($v)).'"}';
                                    } elseif (count(array_values($v))==0) {
                                        $arr[] = '{"name":"'.$qs[$k]["text"].'","value":"None"}';
                                    } else {
                                        $arr[] = '{"name":"'.$qs[$k]["text"].'","value":"'.implode(",", array_values($v)).'"}';
                                    }
                                }
                            }
                            $str .= implode(",", $arr);
                            $str .= "]";
                            $integration->getApi()->sendMessage($author_channel_id, " ", '{"title":"**Preview of answers so far**:","color": 16328500,"fields": '.$str.'}');
                            usleep(2000000);
                            $integration->getApi()->sendTextMessage($author_channel_id, $current_question_text, "");
                            $questionAsked =true;
                        }
                    }
                
                }
            }
           
        }
    } catch (Exception $e) {
        var_dump($e->getMessage());
    }
    /*


    $integration->getApi()->sendTextMessage($author_channel_id, "Bot Left", "");


//handle disconnection
        $seqNumber = $message["s"];
        //echo $seqNumber;
        /*
        $connectionStatus = stream_get_meta_data($client->getSocket());
        $timedOut = $connectionStatus["timed_out"];
        while ($timedOut== "1") {
            $integration->getApi()->sendTextMessage($author_channel_id, "Disconnected", "");
            sleep(1);
            $client->close();
            $client = new Client("wss://gateway.discord.gg:443");;
            $client->receive() . "\n";
            $client->send('{
                "op": 6,
                "d": {
                    "token": "'.'NzQ1NjcwOTUzMTc4MjM0OTQw.Xz1KMQ.1tJN2Pd7AWPD7Pnaqv2VQyqzZ3Y'.'",
                    "session_id": "'.$sessionID.'",
                    "seq":2
                }
            }');
            $connectionStatus = stream_get_meta_data($client->getSocket());
            print_r($connectionStatus);
            $timedOut = $connectionStatus["timed_out"];
            if($timedOut==""){
                $integration->getApi()->sendTextMessage($author_channel_id,$client->receive() , "");
                print_r(json_decode($client->receive(),true));
                $client->send('{
                    "op": 1,
                    "d":0
                }');
            }
            $tries++;
        }*/
