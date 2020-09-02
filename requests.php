<?php

    class Requests{
        static function getRequest($url,$headers){
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER=>false,
            ));
            if($headers!=null){
                curl_setopt($ch,CURLOPT_HTTPHEADER , $headers);
            }
            $result =curl_exec($ch);
            curl_close($ch);
            return $result;
        }

        static function postRequest($url,$params,$headers){
                $ch = curl_init();
                curl_setopt_array($ch, array(
                    CURLOPT_URL => $url,
                    CURLOPT_POST => 1,
                    CURLOPT_SSL_VERIFYPEER=>false,
                    CURLOPT_POSTFIELDS => $params,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_RETURNTRANSFER => true,
                ));
                $result =curl_exec($ch);
                curl_close($ch);
                return $result;
            }
    }
   