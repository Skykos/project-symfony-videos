<?php

namespace App\Services;

use Firebase\JWT\JWT;
use App\Entity\User;

class JwtAuth{
    public $manager;
    private $key;
    
    public function __construct($manager) {
        $this->manager = $manager;
        $this->key = 'parangatirimicuaro-estornecleidomastoideo__';
    }
    public function signup( $email, $password,$gettoken = null){
        //Comprobar si el usuario existe
        $user  = $this->manager->getRepository(User::class)->findOneBy([
            'email' => $email,
            'password' => $password
        ]);
        
        $signup = false;
        if(is_object($user)){
            $signup = true;
        }
        //si existe, generar el token de jwt
        if($signup){
            $token = [
                'sub' => $user->getId(),
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'email' => $user->getEmail(),
                'iat' => time(),
                'exp' => time()+(60*24*60*60)
                ];
                //Comprobar el flag gettoken, si llega; condicional
               $jwt = JWT::encode($token, $this->key,'HS256');
               if(!empty($gettoken)){
                   $data = $jwt;
               }else{
                   $decoded = JWT::decode($jwt, $this->key,['HS256']);
                   $data = [
                        'status' => 'success',
                        'message' => 'Login correcto',
                        'code' => 200,
                        'user' => $decoded
                    ];
               }
        }else{
            $data = [
                'status' => 'error',
                'message' => 'Login incorrecto',
                'code' => 400
            ];
        }
        //Comprobar el flag gettoken, si llega; condicional
        //Devolver los datos 
        return $data;
    }
    
    public function checkToken($jwt, $identity = false){
        $auth = false;
        
        try{
            $decoded = JWT::decode($jwt, $this->key, ['HS256']);
        }catch(\UnexpectedValueException $e){
            $auth = false;
        }catch(\DomainException $e){
            $auth = false;
        }
        
        
        if(isset($decoded) && !empty($decoded) && is_object($decoded) && isset($decoded->sub)){
            $auth = true;
        }else{
            $auth = false;
        }
        
        if($identity != false){
            $auth = $decoded;
        }else{}
         
        return $auth;
    }
}
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

