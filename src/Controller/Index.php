<?php

namespace App\Controller;

use App\Entity\Stats;
use DateTime;
use App\Repository\MessagesRepository;
use OpenApi\Attributes as OA;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Service\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Index
{
    private $params;
    private $entityManager;

    private Logger $logger;

    private MessagesRepository $messagesRepository;


    public function __construct(ParameterBagInterface $params, EntityManagerInterface $entityManager, MessagesRepository $messagesRepository, Logger $logger)
    {
        $this->params = $params;
        $this->entityManager = $entityManager;
        
        $this->logger = $logger;

        $this->messagesRepository = $messagesRepository;
    }
 
    /**
     * Index
     * 
     * Get general message and support about the app and api
     */
    #[Route('/index', name: 'get_index', methods: ['GET'])]
    #[OA\Tag(name: 'Index')]
    #[OA\Parameter(
        name:"v",
        in:"query",
        description:"App version",
        required: true,
        schema: new OA\Schema(type: 'string', default: '0.5')
    )]

    #[OA\Response(
        response: 200,
        description: 'OK'
    )] 
    
    public function getIndex(Request $request)
    {
        $messages = [];
        $severity_i = array(
            0 => 4,
            1 => 1,
            2 => 1,
        );

        // --- Version
        $app_version = $request->get('v') ?? 'null';

        // --- Stats

        $stats = new Stats();
        $stats->setDatetime( new DateTime());
        $stats->setUuid( $request->get('uuid') ?? '' );
        $stats->setVersion( $request->get('v') ?? '' );
        $stats->setPlatform( $request->get('platform') ?? '' );

        $this->entityManager->persist($stats);
        $this->entityManager->flush();

        // --- Message de la base de données
        $_messages = $this->messagesRepository->findAll();

        foreach ($_messages as $message) {
            $messages[] = array(
                "id"            =>  (string)    $message->getId(),
                "status"        =>  (string)    $message->getStatus(),
                "severity"      =>  (int)       $message->getSeverity(),
                "effect"        =>  (string)    $message->getEffect(),
                "updated_at"    =>              $message->getUpdatedAt()->format('Y-m-d\TH:i:sO'),
                "message"       =>  array(
                    "title"     =>      $message->getTitle(),
                    "text"      =>      $message->getText(),
                    "button"      =>    $message->getButton(),
                    "link"      =>      $message->getLink(),
                ),
            );
        }
        
        $messages[] = array(
            "id"            =>  (string)    "ADMIN:JO",
            "status"        =>  (string)    "active",
            "severity"      =>  (int)       1,
            "effect"        =>  (string)    "OTHER",
            "updated_at"    =>  (string)    '2024-07-14T13:00:00Z',
            "message"       =>  array(
                "title"     =>      'Paris 2024 : Anticiper vos déplacements',
                "text"      =>      'Anticiper vos déplacements',
                // "icon"      =>      '',
                "img"       =>      'https://app.navika.hackernwar.com/img/JO2.jpg',
                "is_reduced"=>       true,
                "button"    =>      'En savoir plus',
                "link"      =>      'https://anticiperlesjeux.gouv.fr/je-minforme/anticiper-ses-deplacements#jeminforme',
            ),
        );

        $messages[] = array(
            "id"            =>  (string)    "ADMIN:JO",
            "status"        =>  (string)    "active",
            "severity"      =>  (int)       4,
            "effect"        =>  (string)    "OTHER",
            "updated_at"    =>  (string)    '2024-07-14T13:00:00Z',
            "message"       =>  array(
                "title"     =>      'Paris 2024 : Modification des lignes de bus pendant les Jeux',
                "text"      =>      'Durant les Jeux Olympiques et Paralympiques de Paris 2024, certaines lignes de bus franciliennes vont être modifiées. Nouveaux itinéraires, horaires, infos trafic, ...',
                "button"      =>    'En savoir plus',
                "link"      =>      'https://www.iledefrance-mobilites.fr/modification-trafic-bus-jeux-paris-2024-infos-trafic',
            ),
        );

        // --- Message de IDFM
        // $client = HttpClient::create();
        // 
        // $response = $client->request('GET', 'https://api-iv.iledefrance-mobilites.fr/banners');
        // $status = $response->getStatusCode();
        //
        // if ($status == 200){
        //     $content = $response->getContent();
        //     $results = json_decode($content);
        //
        //     foreach ($results as $result) {
        //     
        //         $url = $result->link;
        //         if (strpos($url, 'iledefrance-mobilites.fr') == false) {
        //             $url = 'https://me-deplacer.iledefrance-mobilites.fr' . $url;
        //         }
        //     
        //         $messages[] = array(
        //             "id"            =>  (string)    $result->id,
        //             "status"        =>  (string)    "active",
        //             "severity"      =>  (int)       $severity_i[$result->type],
        //             "effect"        =>  (string)    "OTHER",
        //             "updated_at"    =>  (string)    $result->updatedDate,
        //             "message"       =>  array(
        //                 "title"     =>      $result->title,
        //                 "text"      =>      $result->description,
        //                 "button"      =>    $result->buttonText,
        //                 "link"      =>      $url,
        //             ),
        //         );
        //     }
        // }
        // ---

        $json = array(
            "api"         => array(
                "version"              =>  (string)       $this->params->get('api.version.current'),
            ),
            "message"     => $messages,
        );

        return new JsonResponse($json);
    }
}
