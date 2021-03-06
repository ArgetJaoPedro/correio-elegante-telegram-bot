<?php

namespace App\Library;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Log;
class Api{
    private static $baseURL = 'https://api.telegram.org/bot';
    
    private static function getLastUpdateID(){
        $lastUID = Cache::get('last_update');
        if($lastUID != null){
            return $lastUID;
        }
        return 1;
    }

    private static function setLastUpdateID($id){
        Cache::put('last_update', $id);
    }

    private static function getResults($response){
        if(count($response['result']) == 0){
            return null;
        }
        return collect($response['result']);
    }

    public static function sendMessage($target, $message){
        $token = config('services.Telegram')['token'].'/';
        $method = 'sendMessage';
        $response = Http::get(Api::$baseURL.$token.$method, [
            'chat_id' => $target,
            'text' => $message
        ]);

        $response = json_decode($response);
        if(!isset($response->result)){
            Log::channel('telebot')->error("A mensagem:\n---------------\n$message\n---------------\npara <$target> não pode ser enviada. Faria as honras de entregar?\nPara saber quem é, basta olhar no banco de dados de quem é o id $target");
            return -1;
        }
        return $response->result;
    }
    
    public static function update(){
        error_log("update\n");
        $token = config('services.Telegram')['token'].'/';
        $method = 'getUpdates';
        $lastUID = self::getLastUpdateID();
        $response = Http::get(Api::$baseURL.$token.$method, [
            'offset' => $lastUID
        ]);
        $response = collect(json_decode($response));
        $results = self::getResults($response);
        if($results != null){
            $uID = $results->last()->update_id;
            $uID++;
            self::setLastUpdateID($uID);
            return collect($results);
        }
        return null;
    }
}