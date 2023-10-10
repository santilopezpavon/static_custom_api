<?php

namespace Drupal\static_custom_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Url;
use Drupal\static_custom_api\Service\Getter\EntityCache;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for API base queries.
 */
class ApiControllerBaseQueries extends ControllerBase {

    /**
     * The entity cache service.
     *
     * @var \Drupal\static_custom_api\Service\Getter\EntityCache
     */
    protected $entityCache;

    /**
     * The request stack.
     *
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    protected $requestStack;

    protected $aliasCache;


    /**
     * Constructor for ApiControllerBaseQueries.
     *
     * @param \Drupal\static_custom_api\Service\Getter\EntityCache $entityCache
     *   The entity cache service.
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     *   The request stack.
     */
    public function __construct(EntityCache $entityCache, RequestStack $requestStack, $aliasCache) {
        $this->entityCache = $entityCache;
        $this->requestStack = $requestStack;
        $this->aliasCache = $aliasCache;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('static_custom_api.entity_cache'),
            $container->get('request_stack'),
            $container->get("static_custom_api.alias_cache")
        );
    }

    /**
     * Gets a node by alias.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   A JSON response containing the node data.
     */
    public function getNodeByAlias() {
        $content = $this->requestStack->getCurrentRequest()->getContent();
        $force = $this->requestStack->getCurrentRequest()->query->get('force', false);
        $lang_code = \Drupal::languageManager()->getCurrentLanguage()->getId();

    
        if (empty($content)) {
            return new JsonResponse(["error" => "No content provided"], 400);
        }
    
        $decode = json_decode($content, true);
    
        if (!isset($decode["alias"])) {
            return new JsonResponse(["error" => "Alias not provided"], 400);
        }

        if($force) {
            $url = Url::fromUri('internal:' . $decode["alias"]);
    
            if (!$url->isRouted()) {
                return new JsonResponse(["error" => "Invalid alias"], 404);
            }
            $params = $url->getRouteParameters();
            $entity_type = key($params);
            $output["entity_type"] = $entity_type;
            $output["id"] = $params[$entity_type];
        } else {
            $mod_alias = $decode["alias"];
            $mod_alias = str_replace("/" . $lang_code . "/", "/", $mod_alias);

            $url = $this->aliasCache->getEntityCacheByAlias($mod_alias, $lang_code);
            if (empty($url)) {
                return new JsonResponse(["error" => "Invalid alias"], 404);
            }
            $output["entity_type"] = $url["entity_type"];
            $output["id"] = $url["id_entity"];
        } 
    
        $output["lang"] = $lang_code;

       
    
        $force = $this->requestStack->getCurrentRequest()->query->get('force', false);
    
        if ($force) {
            $entity = $this->entityCache->getEntityFromDatabase($entity_type, $params[$entity_type], $lang_code);
        } else {
            $entity = $this->entityCache->getEntityFromJSON($output["entity_type"], $output["id"], $lang_code);
        }
    
        if (!$entity) {
            return new JsonResponse(["error" => "Entity not found"], 404);
        }
    
        $output["entity"] = $entity;
        return new JsonResponse(["data" => $output], 200);
    }

    /**
     * Gets an entity by type and ID.
     *
     * @param string $entity_type
     *   The entity type.
     * @param int $entity_id
     *   The entity ID.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   A JSON response containing the entity data or an error response.
     */
    public function getEntityByTypeAndId($entity_type, $entity_id) {
        $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $force = $this->requestStack->getCurrentRequest()->query->get('force', false);

        if ($force) {
            $entity = $this->entityCache->getEntityFromDatabase($entity_type, $entity_id, $lang);
        } else {
            $entity = $this->entityCache->getEntityFromJSON($entity_type, $entity_id, $lang);
        }

        if (!$entity) {
            return new JsonResponse(["error" => "Entity not found"], 404);
        }

        return new JsonResponse(["data" => $entity], 200);
    }

    
}
