<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Email;

use Knp\Component\Pager\PaginatorInterface;

use App\Entity\User;
use App\Entity\Video;
use App\Services\JwtAuth;


class VideoController extends AbstractController
{
    
    public function index(): Response
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/VideoController.php',
        ]);
    }
    
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
    
    public function  create (Request $request, JwtAuth $jwt_auth, $id = null){
        //recoger el token
        $token = $request->headers->get('Authorization');
        //comprobar si es correcto 
        $check = $jwt_auth->checkToken($token);
        //Respuensta por defecto
        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'El video no se ha podido crear '
        ];
        
        if($check){
        //recoger datos por post
        $json = $request->get('json',null);
        $params = json_decode($json);
        //recoger objeto del usuario identificado
        $identity = $jwt_auth->checkToken($token,true);
        //comprobar y validar datos 
        if(!empty($json)){
            $user_id = ($identity->sub != null) ? $identity->sub : null;
            $title = (!empty($params->title)) ? $params->title : null;
            $description = (!empty($params->description)) ? $params->description : null;
            $url = (!empty($params->url)) ? $params->url : null;
            
            
            if(!empty($user_id) && !empty($title)){
                //Persistir y localizar el usuario a subir video
                $em = $this->getDoctrine()->getManager();
                $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['id' => $user_id]);
                if($id == null){
                    //crear y guardar el objeto de usuario
                    $video = new Video();
                    $video->setUser($user);
                    $video->setTitle($title);
                    $video->setDescription($description);
                    $video->setUrl($url);
                    $video->setStatus('normal');

                    $createdAt = new \DateTime('now');
                    $updatedAt = new \DateTime('now');
                    $video->setCreatedAt($createdAt);
                    $video->setUpdatedAt($updatedAt);
                    //guardar el nuevo video favorito en db
                    $em->persist($video);
                    $em->flush();

                    $data = [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'El video guardado satisfactoriamente',
                        'video' => $video
                    ];
                }else{
                    $video = $this->getDoctrine()->getRepository(Video::class)->findOneBy([
                        'id' => $id,
                        'user' => $identity->sub
                    ]);
                    if($video && is_object($video)){
                        
                        $video->setTitle($title);
                        $video->setDescription($description);
                        $video->setUrl($url);
                        $updatedAt = new \DateTime('now');
                        $video->setUpdatedAt($updatedAt);
                        
                        $em->persist($video);
                        $em->flush();
                        
                        $data = [
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'El video se ha actualizado satisfactoriamente',
                            'video' => $video
                        ];
                    }else{
                        $data = [
                            'status' => 'error',
                            'code' => 404,
                            'message' => 'El video NO te pertenece'
                            
                        ];
                    }
                }
            }
        }
        //devolver respuesta
        }

        return $this->resjson($data);
    }
    
    public function videos(Request $request, JwtAuth $jwt_auth, PaginatorInterface $paginator){
        
        
        
        //recoger la cabecera de autenticacion
        $token = $request->headers->get('Authorization');
        //comprobar el token
        $check = $jwt_auth->checkToken($token);
         
        //si es valido:
        if($check){
           //conseguir el objeto de usuario
           $identity = $jwt_auth->checkToken($token,true);
           
           
            // hacer una consulta para paginar
            $em = $this->getDoctrine()->getManager();
            $dql = "SELECT v FROM App\Entity\Video v WHERE v.user = {$identity->sub} ORDER BY v.id DESC";
            $query = $em->createQuery($dql);
            // recoger el  el parametro page de la url
            $page = $request->query->getInt('page',1);
            $items_per_page = 5;
            //invocar paginacion
            $pagination = $paginator->paginate($query,$page,$items_per_page);
            $total = $pagination->getTotalItemCount();
            //generar o preparar array de datos para devolver
           $data =  array(
                'status' => 'success',
                'code' => 200,
                'total_items_count' => $total,
                'page_actual' => $page,
                'item_per_page' => $items_per_page,
                'total_pages' => ceil($total / $items_per_page),
                'videos' => $pagination,
                'User_id' => $identity->sub
               
            );
        }else{
            $data =  array(
                'status' => 'error',
                'code' => 404,
                'message' => 'no se puede listar los videos'
            );
        }
        
        
      
        return $this->resjson($data);
    }
    
    public function video (Request $request, JwtAuth $jwt_auth, $id = null){
        //Sacar el token y comprobar si es correcto
        $token = $request->headers->get('Authorization');
        $check = $jwt_auth->checkToken($token);
        
        //devolver respuesta
            $data = [
                'status' => 'error',
                'code' => 404,
                'message' => 'video no encotrado o no es tuyo erl video',
            ];
        if($check){
            //Sacar la identidad del usuario
            $identity = $jwt_auth->checkToken($token, true);
            // sacar el objeto del video con base al id
            $video = $this->getDoctrine()->getRepository(VIDEO::class)->findOneBy(['id' => $id] );
            
            // comprobar si el video existe y es propiedad de usuario identificado
            if(isset($video) && is_object($video) && $identity->sub == $video->getUser()->getId()){
                $data = [
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Video obtenido satisfactoriamente',
                    'video' => $video
                ];
            }
        }
        
        
        
        return $this->resjson($data);
    }
    
    public function remove(Request $request, JwtAuth $jwt_auth, $id = null){
        //recoger el token
        $token = $request->headers->get('Authorization');
        $check = $jwt_auth->checkToken($token);
        
        $data = [
                'status' => 'error',
                'code' => 404,
                'message' => 'video no borrado o no pertenece',
            ];
        if($check){
            $identity = $jwt_auth->checkToken($token, true);
            
            $em = $this->getDoctrine()->getManager();
            $video = $this->getDoctrine()->getRepository(Video::class)->findOneBy([
                'id' => $id
            ]);
            
            if($video && is_object($video) && $identity->sub == $video->getUser()->getId()){
                $em->remove($video);
                $em->flush();
                
                 $data = [
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Video borrado satisfactoriamente',
                    'video borrado' => $video
                ];
            }
        }
        return $this->resjson($data);
    }
    
    
    
}
