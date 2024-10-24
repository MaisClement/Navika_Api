<?php

namespace App\Controller;

use App\Controller\Functions;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Entity\RouteSub;
use App\Entity\Subscribers;
use App\Repository\RoutesRepository;
use App\Repository\RouteSubRepository;
use App\Repository\SubscribersRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Kreait\Firebase\Contract\Messaging;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Logger;

class Notifications
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;

    private Logger $logger;

    private Messaging $messaging;
    private RoutesRepository $routesRepository;
    private RouteSubRepository $routeSubRepository;
    private SubscribersRepository $subscribersRepository;

    public function __construct(EntityManagerInterface $entityManager, Messaging $messaging, RoutesRepository $routesRepository, RouteSubRepository $routeSubRepository, SubscribersRepository $subscribersRepository)
    {
        $this->entityManager = $entityManager;

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
        name: "token",
        in: "path",
        description: "FCM Token",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: "line",
        in: "path",
        description: "Line ID",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: "type",
        in: "path",
        description: "type of alert",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: "days",
        in: "path",
        description: "days of alert",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: "start_time",
        in: "path",
        description: "start time alert",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: "end_time",
        in: "path",
        description: "end time of alert",
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

        if ($token == null || $line == null || $type == null || $days == null || $start_time == null || $end_time == null) {
            $this->logger->logHttpErrorMessage($request, 'At least one required parameter is missing or null, have you "token", "line", "type", "days", "start_time" and "end_time" ?', 'WARN');
            return new JsonResponse(Functions::httpErrorMessage(400, 'At least one required parameter is missing or null, have you "token", "line", "type", "days", "start_time" and "end_time" ?'), 400);
        }

        // ---

        $subscriber = $this->subscribersRepository->findOneBy(['fcm_token' => $token]);
        if ($subscriber == null) {
            $subscriber = new Subscribers();
            $subscriber->setFcmToken($token);
            $subscriber->setCreatedAt(new DateTime());
            $this->entityManager->persist($subscriber);
            $this->logger->log(["message" => "A new subscriber ws created : $token"], 'INFO');
        }

        // On vérfie que la ligne existe bien
        $route = $this->routesRepository->findOneBy(['route_id' => $line]);
        if ($route == null) {
            $this->logger->logHttpErrorMessage($request, 'Nothing where found for this route', 'WARN');
            return new JsonResponse(Functions::httpErrorMessage(400, 'Nothing where found for this route'), 400);
        }

        // On regarde si il y'a déja un abonnement
        $routeSub = $this->routeSubRepository->findOneBy(['subscriber_id' => $subscriber->getId(), 'route_id' => $route->getRouteId()]);
        if ($routeSub != null) {
            // on supprime l'abonnement deja existant
            $this->entityManager->remove($routeSub);
        }

        // On cree l'abonnement
        $routeSub = new RouteSub();
        $routeSub->setSubscriberId($subscriber);
        $routeSub->setRouteId($route);

        $routeSub->setType($type);

        $routeSub->setMonday($days['monday'] ? '1' : '0');
        $routeSub->setTuesday($days['tuesday'] ? '1' : '0');
        $routeSub->setWednesday($days['wednesday'] ? '1' : '0');
        $routeSub->setThursday($days['thursday'] ? '1' : '0');
        $routeSub->setFriday($days['friday'] ? '1' : '0');
        $routeSub->setSaturday($days['saturday'] ? '1' : '0');
        $routeSub->setSunday($days['sunday'] ? '1' : '0');

        $routeSub->setStartTime(DateTime::createFromFormat('H:i:s', $start_time));
        $routeSub->setEndTime(DateTime::createFromFormat('H:i:s', $end_time));

        $this->entityManager->persist($routeSub);

        $this->entityManager->flush();

        $this->logger->log([
            "message" => "$token has subscribed to $line",
            "token" => $token,
            "line" => $line,
            "type" => $type,
            "days" => $days,
            "start_time" => $start_time,
            "end_time" => $end_time,
        ], 'INFO');

        //--

        $title = 'Alerte créée avec succès';
        $body = 'Vous serez alerté à chaque perturbation sur votre ligne.';

        $notif = new Notify($this->messaging);
        $notif->sendNotificationToUser(
            $this->logger,
            $token,
            $title,
            $body,
            []
        );

        //--

        $routeSub = $this->routeSubRepository->findOneBy(['subscriber_id' => $subscriber->getId(), 'route_id' => $route->getRouteId()]);

        $json = array(
            "id" => $routeSub->getId(),
            "line" => $routeSub->getRouteId()->getRouteId(),
            "type" => $routeSub->getType(),
            "days" => array(
                "monday" => $routeSub->getMonday() == "1" ? true : false,
                "tuesday" => $routeSub->getTuesday() == "1" ? true : false,
                "wednesday" => $routeSub->getWednesday() == "1" ? true : false,
                "thursday" => $routeSub->getThursday() == "1" ? true : false,
                "friday" => $routeSub->getFriday() == "1" ? true : false,
                "saturday" => $routeSub->getSaturday() == "1" ? true : false,
                "sunday" => $routeSub->getSunday() == "1" ? true : false,
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
    #[Route('/notification/get/{id}', name: 'get_subscription', methods: ['GET'])]
    #[OA\Tag(name: 'Notifications')]
    #[OA\Parameter(
        name: "id",
        in: "path",
        description: "Subscription id",
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
        $routeSub = $this->routeSubRepository->findOneBy(['id' => $id]);
        if ($routeSub == null) {
            $this->logger->logHttpErrorMessage($request, "Nothing where found for this id", 'WARN');
            return new JsonResponse(Functions::httpErrorMessage(400, 'Nothing where found for this id'), 400);
        }

        $json = array(
            "id" => $id,
            "line" => $routeSub->getRouteId()->getRouteId(),
            "type" => $routeSub->getType(),
            "days" => array(
                "monday" => $routeSub->getMonday() == "1" ? true : false,
                "tuesday" => $routeSub->getTuesday() == "1" ? true : false,
                "wednesday" => $routeSub->getWednesday() == "1" ? true : false,
                "thursday" => $routeSub->getThursday() == "1" ? true : false,
                "friday" => $routeSub->getFriday() == "1" ? true : false,
                "saturday" => $routeSub->getSaturday() == "1" ? true : false,
                "sunday" => $routeSub->getSunday() == "1" ? true : false,
            ),
            "times" => array(
                "start_time" => $routeSub->getStartTime()->format('H:i:s'),
                "end_time" => $routeSub->getEndTime()->format('H:i:s'),
            ),
        );

        return new JsonResponse($json);
    }

    /**
     * Remove subscription
     * 
     * 
     */
    #[Route('/notification/unsubscribe/{id}', name: 'remove_notification_subscription', methods: ['GET'])]
    #[OA\Tag(name: 'Notifications')]
    #[OA\Parameter(
        name: "id",
        in: "path",
        description: "Subscription id",
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
        $routeSub = $this->routeSubRepository->findOneBy(['id' => $id]);
        if ($routeSub == null) {
            $this->logger->logHttpErrorMessage($request, "Nothing where found for this id", 'WARN');
            return new JsonResponse(Functions::httpErrorMessage(400, 'Nothing where found for this id'), 400);
        }

        $this->entityManager->remove($routeSub);
        $this->entityManager->flush();

        $this->logger->log(["message" => "Subscription $id removed"], 'INFO');
        return new JsonResponse(Functions::httpSuccesMessage(200, 'Subscription removed'), 200);
    }

    /**
     * Remove subscription
     * 
     * 
     */
    #[Route('/notification/renew', name: 'renew_notification_token', methods: ['POST'])]
    #[OA\Tag(name: 'Notifications')]
    #[OA\Parameter(
        name: "old_token",
        in: "path",
        description: "Old FCM Token",
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: "new_token",
        in: "path",
        description: "New FCM Token",
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

    public function renewNotificationToken(Request $request)
    {
        $old_token = $request->request->get('old_token');
        $new_token = $request->request->get('new_token');

        $subscriber = $this->subscribersRepository->findOneBy(['fcm_token' => $old_token]);

        if ($subscriber == null) {
            $this->logger->logHttpErrorMessage($request, "Nothing where found for this token", 'WARN');
            return new JsonResponse(Functions::httpErrorMessage(400, 'Nothing where found for this token'), 400);
        }

        $subscriber->setFcmToken($new_token);

        $this->entityManager->flush();

        $this->logger->log(["message" => "Token renewed $old_token to $new_token"], 'INFO');
        return new JsonResponse(Functions::httpSuccesMessage(200, 'Token updated'), 200);
    }
}
