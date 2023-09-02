<?php

namespace App\Controller;

use App\Controller\Functions;
use App\Entity\Subscribers;
use App\Entity\RouteSub;
use App\Repository\RoutesRepository;
use App\Repository\SubscribersRepository;
use App\Repository\RouteSubRepository;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Contract\Messaging;

class Notifications
{
    private $entityManager;
    private $params;

    private Messaging $messaging;
    private RoutesRepository $routesRepository;
    private RouteSubRepository $routeSubRepository;
    private SubscribersRepository $subscribersRepository;
    
    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, Messaging $messaging, RoutesRepository $routesRepository, RouteSubRepository $routeSubRepository, SubscribersRepository $subscribersRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;
        
        $this->messaging = $messaging;
        $this->routesRepository = $routesRepository;
        $this->routeSubRepository = $routeSubRepository;
        $this->subscribersRepository = $subscribersRepository;
    }
 
    /**
     * Register notification subscription
     * 
     * Register notification subscription
     */
    #[Route('/notification/subscribe', name: 'add_notification_subscription', methods: ['POST'])]
    #[OA\Tag(name: 'Notifications')]
    #[OA\Parameter(
        name:"token",
        in:"path",
        description:"FCM Token",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name:"line",
        in:"path",
        description:"Line ID",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name:"type",
        in:"path",
        description:"type of alert",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name:"days",
        in:"path",
        description:"days of alert",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name:"start_time",
        in:"path",
        description:"start time alert",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name:"end_time",
        in:"path",
        description:"end time of alert",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]

    #[OA\Response(
        response: 200,
        description: 'Return near objects'
    )]    
    #[OA\Response(
        response: 400,
        description: 'Bad request'
    )]

    public function addNotificationSubscription(Request $request)
    {
        $token = $request->request->get('token');
        $line = $request->request->get('line');
        $type = $request->request->get('type');

        $days = $request->request->get('days');
        $days = json_decode($days, true);

        $start_time = $request->request->get('start_time');
        $end_time = $request->request->get('end_time');

        if ( $token == null || $line == null || $type == null || $days == null || $start_time == null || $end_time == null) {
            return new JsonResponse(Functions::ErrorMessage(400, 'One or more parameters are missing or null, have you "token", "line", "type", "days", "start_time" and "end_time" ?'), 400);
        }

        // ---

        $subscriber = $this->subscribersRepository->findOneBy(['fcm_token' => $token]);
        if ($subscriber == null) {
            $subscriber = new Subscribers();
            $subscriber->setFcmToken($token);
            $subscriber->setCreatedAt( new DateTime() );
            $this->entityManager->persist($subscriber);
        }

        // On vérfie que la ligne existe bien
        $route = $this->routesRepository->findOneBy(['route_id' => $line ]);
        if ($route == null) {
            return new JsonResponse(Functions::ErrorMessage(400, 'Nothing where found for this route'), 400);
        }

        // On regarde si il y'a déja un abonnement
        $routeSub = $this->routeSubRepository->findOneBy(['subscriber_id' => $subscriber->getId(), 'route_id' => $route->getRouteId() ]);
        if ($routeSub != null) {
            // on supprime l'abonnement deja existant
            $this->entityManager->remove($routeSub);
        }

        // On cree l'abonnement
        $routeSub = new RouteSub();
        $routeSub->setSubscriberId( $subscriber );
        $routeSub->setRouteId( $route );

        $routeSub->setType( $type );

        $routeSub->setMonday(    $days['monday']    ? '1' : '0' );
        $routeSub->setTuesday(   $days['tuesday']   ? '1' : '0' );
        $routeSub->setWednesday( $days['wednesday'] ? '1' : '0' );
        $routeSub->setThursday(  $days['thursday']  ? '1' : '0' );
        $routeSub->setFriday(    $days['friday']    ? '1' : '0' );
        $routeSub->setSaturday(  $days['saturday']  ? '1' : '0' );
        $routeSub->setSunday(    $days['sunday']    ? '1' : '0' );

        $routeSub->setStartTime( \DateTime::createFromFormat('H:i:s', $start_time) );
        $routeSub->setEndTime( \DateTime::createFromFormat('H:i:s', $end_time) );

        $this->entityManager->persist($routeSub);
        
        $this->entityManager->flush();

        //--

        $title = 'Alerte créée avec succès';
        $body = 'Vous serez alerté à chaque perturbation sur votre ligne.';

        $notif = new Notify($this->messaging);
        $notif->sendNotification($token, $title, $body);

        //--

        $routeSub = $this->routeSubRepository->findOneBy(['subscriber_id' => $subscriber->getId(), 'route_id' => $route->getRouteId() ]);

        $json = array(
            "id" => $routeSub->getId(),

            "message" => 'Subscription created',
        );

        return new JsonResponse(Functions::SuccessMessage(200, 'Subscription created'), 200);
    }
    
    /**
     * Get subscription
     * 
     * 
     */
    #[Route('/notification/get/{id}', name: 'get_subscription', methods: ['GET'])]
    #[OA\Tag(name: 'Notifications')]
    #[OA\Parameter(
        name:"id",
        in:"path",
        description:"Subscription id",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]

    #[OA\Response(
        response: 200,
        description: 'Return near objects'
    )]    
    #[OA\Response(
        response: 400,
        description: 'Bad request'
    )]

    public function getNotification($id, Request $request)
    {
        
        $routeSub = $this->routeSubRepository->findOneBy(['id' => $id ]);
        if ($routeSub == null) {
            return new JsonResponse(Functions::ErrorMessage(400, 'Nothing where found for this id'), 400);
        }

        $json = array(
            "id" => $id,
            "line" => $routeSub->getRouteId()->getRouteId(),
            "type" => $routeSub->getType(),
            "days" => array(
                "monday"    => $routeSub->getMonday(),
                "tuesday"   => $routeSub->getTuesday(),
                "wednesday" => $routeSub->getWednesday(),
                "thursday"  => $routeSub->getThursday(),
                "friday"    => $routeSub->getFriday(),
                "saturday"  => $routeSub->getSaturday(),
                "sunday"    => $routeSub->getSunday(),
            ),
            "times" => array(
                "start_time" => $routeSub->getStartTime()->format('H:i:s'),
                "end_time" => $routeSub->getEndTime()->format('H:i:s'),
            ),
        );

        return new JsonResponse($json);
    }
    
    /**
     * Get subscription
     * 
     * 
     */
    #[Route('/notification/unsubscribe/{id}', name: 'remove_notification_subscription', methods: ['GET'])]
    #[OA\Tag(name: 'Notifications')]
    #[OA\Parameter(
        name:"id",
        in:"path",
        description:"Subscription id",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]

    #[OA\Response(
        response: 200,
        description: 'Return near objects'
    )]    
    #[OA\Response(
        response: 400,
        description: 'Bad request'
    )]

    public function removeNotificationSubscription($id, Request $request)
    {
        $routeSub = $this->routeSubRepository->findOneBy(['id' => $id ]);
        if ($routeSub == null) {
            return new JsonResponse(Functions::ErrorMessage(400, 'Nothing where found for this id'), 400);
        }
        
        $this->entityManager->remove($routeSub);
        
        $this->entityManager->flush();

        return new JsonResponse(Functions::SuccessMessage(200, 'Subscription removed'), 200);
    }
}
