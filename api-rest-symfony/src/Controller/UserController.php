<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Email;
use App\Entity\User;
use App\Entity\Video;
use App\Services\JwtAuth;


class UserController extends AbstractController
{
    private function resjson($data){
        $json = $this->get('serializer')->serialize($data,'json');
        //Serializar datos con servicio de serializer
        $response = new Response();
        //response con hhpfoundation 
        $response->setContent($json);
        // Asignar contenido a una 
        $response->headers->set('Content-Type','application/json');
        // indicar el formato de respuesta
        return $response;
    }
    public function index(): Response
    {
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $video_repo = $this->getDoctrine()->getRepository(Video::class); 
        
        $users = $user_repo->findAll();
        $videos = $video_repo->findAll();
        $user = $user_repo->find(1); 
        $data = [
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/UserController.php',
        ];
        /*
        foreach($users as $user){
            echo "<h1>{$user->getName()} {$user->getSurname()}</h1>";
            foreach($user->getVideos() as $video){
            echo "<h1>{$video->getTitle()} {$video->getUser()->getEmail()}</h1>";
        }
        }
        
            
        die();
       */ 
        return $this->resjson($videos);
    }
    
    public function register (Request $request){
        //Recoger los datos por post
        $json = $request->get('json', null);
        //convertirlos o decodificar el 
        $params = json_decode($json);
        
       // Hacer una respuneta por defecto
        $data = [
            'status' => 'error',
            'code'   => 400,
            'message' => 'El usuario no se ha creado'
        ];
        // Comprobar  datos
        if($json != null){
            $name =(!empty($params->name)) ? $params->name : null;
            $surname =(!empty($params->surname)) ? $params->surname : null;
            $email =(!empty($params->email)) ? $params->email : null;
            $password =(!empty($params->password)) ? $params->password : null;
            
            $validator = Validation::createValidator();
            $validate_email = $validator->validate($email,[new Email()]);
            
            if(!empty($email) && count($validate_email) == 0 && !empty($password) && !empty($name) && !empty($surname)){
                 //si la validacion es correcta, crear objeto de usuario
                 $user = new User();
                 $user->setName($name);
                 $user->setSurname($surname);
                 $user->setEmail($email);
                 $user->setRole('ROLE_USER');
                 $user->setCreatedAt(new \DateTime('now'));
                // cifrar la contraseña{
                $pwd = hash('sha256', $password);
                $user->setPassword($pwd);
                // Comprobar si el usuario existe (Control de duplicados)
                $doctrine = $this->getDoctrine();
                $em = $doctrine->getManager();
                $user_repo = $doctrine->getRepository(User::class);
                $isset_user = $user_repo->findBy(array(
                    'email' => $email
                ));
                
                // Si no existe , guardarlo en la base de datos
                if(count($isset_user)== 0){
                    //Guardar Objeto de usuario
                    //Lo persiste en el entityManager
                    $em->persist($user);
                    //Lo guarda en la DB
                    $em->flush();
                    $data = [
                        'status' => 'success',
                        'code'   => 200,
                        'message' => 'Usuario creado correctamente',
                        'User' => $user
                    ]; 
                }else{
                   $data = [
                        'status' => 'error',
                        'code'   => 400,
                        'message' => 'Usuario duplicado'
                    ]; 
                }
            }
            
        }
        //si la validacion es correcta, crear objeto de usuario
        // cifrar la contraseña
        // Comprobar si el usuario existe (Control de duplicados)
        // Si no existe , guardarlo en la base de datos
        // respuesta json
        return New JsonResponse($data);
    }
    
    public  function login(Request $request,JwtAuth $jwt_auth){
        //reibir los datos por post
        $json = $request->get('json',null);
        $params = json_decode($json);
        //Tener un array por defecto para devolver
        $data = [
                        'status' => 'error',
                        'code'   => 400,
                        'message' => 'no se enviaron datos'
                    ]; 
        
        //comprobar y validar los datos 
        
        if($json != null){
            
            $email = (!empty($params->email)) ? $params->email : null ;
            $password = (!empty($params->password)) ? $params->password : null ;
            $gettoken = (!empty($params->gettoken)) ? $params->gettoken : null ;
            
            $validator = Validation::createValidator();
            
            $validate_email = $validator->validate($email,[ new Email()]);
            
            if(!empty($email) && !empty($password) && count($validate_email) == 0){
                //cifrar la cntraseña
                $pwd = hash('sha256', $password);
                //llamaremos un segicio para identificar el usuario (jwt, token y objeto)
                if($gettoken){
                    $signup = $jwt_auth->signup($email, $pwd, $gettoken);
                }else{
                    
                    $signup = $jwt_auth->signup($email, $pwd);
                    
                }
                return new JsonResponse($signup);
                
            }
        }
        
        
        return new JsonResponse($data);
    }
    
    public function edit (Request $request, JwtAuth $jwt_auth){
        //recoger la cabecera de autenticacion
        $token = $request->headers->get("Authorization");
        //crear metodo para comprobar  si el token es correcto
        $authCheck = $jwt_auth->checkToken($token);
        
        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'Error en el metodo update del controlador ',
        ];
        //si es correcto hacer la actualizacion del usuario
    if($authCheck){
        //actualizar el usuario
        //conseguir entity manager
         $em = $this->getDoctrine()->getManager();
        //conseguir los datos  del usuario identificado
        $identity = $jwt_auth->checkToken($token, true);
        
        //Conseguir el usuario a actualizar completo
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $user = $user_repo->findOneBy(['id'=>$identity->sub]);
        
        //recoger los datos por post
        $json = $request->get('json', null);
        $params = json_decode($json);
        
        //comprobar y validar los datos 
        if(!empty($json)){
            $name =(!empty($params->name)) ? $params->name : null;
            $surname =(!empty($params->surname)) ? $params->surname : null;
            $email =(!empty($params->email)) ? $params->email : null;
            
            
            $validator = Validation::createValidator();
            $validate_email = $validator->validate($email,[new Email()]);
            
            if(!empty($email) && count($validate_email) == 0 && !empty($name) && !empty($surname)){
                //asignar nuevos datos al objeto 
                $user->setEmail($email);
                $user->setName($name);
                $user->setSurname($surname);
                //comprobar duplicados
                $isset_user = $user_repo->findBy([
                    'email' => $email
                ]);
                
                if(count($isset_user)== 0 || $identity->email == $email){
                    //guardar cambios en la db
                    $em->persist($user);
                    $em->flush();
                    $data = [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Usuario Actualizado  correctamente ',
                        'user' => $user
                    ];
                }else{
                    $data = [
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'No puedes usar ese email o usuario duplicado',
                    ];
                }
                
            }
        }  
    }
     
        return $this->resjson($data);
    }
}
