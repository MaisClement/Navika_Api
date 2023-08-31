<?php

namespace App\Controller;

use App\Repository\MessagesRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use OpenApi\Attributes as OA;

class Index
{

    private MessagesRepository $messagesRepository;
    private ParameterBagInterface $params;

    public function __construct(MessagesRepository $messagesRepository, ParameterBagInterface $params)
    {
        $this->params = $params;

        $this->messagesRepository = $messagesRepository;
    }
 
    /**
     * Index
     * 
     * Get general message and support about the app and api
     * 
     * `/v0.1/index` is keep for backward compatibility
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
        description: ''
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
        $support = in_array( $app_version, $this->params->get('app.version.supported') );

        if ($_SERVER['APP_ENV'] == "dev"){
            $messages[] = array(
                "id"            =>  (string)    "dev",
                "status"        =>  (string)    "active",
                "severity"      =>  (int)       0,
                "effect"        =>  (string)    "OTHER",
                "updated_at"    =>              date(DATE_ATOM),
                "message"       =>  array(
                    "title"     =>      "Serveur de développement",
                    "text"      =>      "Serveur de développement, destiné uniquement à des fins de tests ou de développement.",
                    "button"      =>    null,
                    "link"      =>      null,
                ),
            );
        }

        if ($app_version != null && !$support) {
            $messages[] = array(
                "id"            =>  (string)    "update",
                "status"        =>  (string)    "active",
                "severity"      =>  (int)       1,
                "effect"        =>  (string)    "OTHER",
                "updated_at"    =>              date(DATE_ATOM),
                "message"       =>  array(
                    "title"     =>      "Mise à jour disponible",
                    "text"      =>      "Une mise à jour de l'application est disponible, profitez des dernières amélioration dès maintenant.",
                    "button"      =>    'En savoir plus',
                    "link"      =>      'https://cloud.hackernwar.com/index.php/s/MbPb4pt4TJZZt8a',
                ),
            );
        }

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

        // --- Message de IDFM
        $client = HttpClient::create();
        
        $response = $client->request('GET', 'https://api-iv.iledefrance-mobilites.fr/banners');
        $status = $response->getStatusCode();

        if ($status == 200){
            $content = $response->getContent();
            $results = json_decode($content);

            foreach ($results as $result) {
            
                $url = $result->link;
                if (strpos($url, 'iledefrance-mobilites.fr') == false) {
                    $url = 'https://me-deplacer.iledefrance-mobilites.fr' . $url;
                }
            
                $messages[] = array(
                    "id"            =>  (string)    $result->id,
                    "status"        =>  (string)    "active",
                    "severity"      =>  (int)       $severity_i[$result->type],
                    "effect"        =>  (string)    "OTHER",
                    "updated_at"    =>  (string)    $result->updatedDate,
                    "message"       =>  array(
                        "title"     =>      $result->title,
                        "text"      =>      $result->description,
                        "button"      =>    $result->buttonText,
                        "link"      =>      $url,
                    ),
                );
            }
        }
        // ---

        $json = array(
            "api"         => array(
                "version"              =>  (string)       $this->params->get('api.version.current'),
            ),
            "app"         => array(
                "current_version"      =>  (string)       $app_version,
                "lastest_version"      =>  (string)       $this->params->get('app.version.lastest'),
                "support"              =>  (bool)       $support !== '' && $support !== '0' ? true : false,
            ),
            "message"     => $messages,
        );

        return new JsonResponse($json);
    }
}
