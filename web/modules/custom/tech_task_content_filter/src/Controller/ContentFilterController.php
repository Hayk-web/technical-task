<?php

namespace Drupal\tech_task_content_filter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Cache\CacheableJsonResponse;

/**
 * Provides JSON responses for filtering content.
 */
class ContentFilterController extends ControllerBase {

  /**
   * Returns filtered content as JSON.
   */
  public function filter(Request $request) {
    $title = $request->query->get('title');

    // Comma-separated tag names.
    $tag_names = $request->query->get('tag');

    // TODO: add check for proper query string(URL decoding).

    // Initialize response data.
    $results = [];
    $response_code = 200;

    // Cache metadata.
    // TODO: Instead of hardcoded cache metadata load from entity.
    $cache_contexts = ['url.query_args:title', 'url.query_args:tag'];
    $cache_tags = ['node_list', 'taxonomy_term_list'];

    try {
      // Build the node query.
      $query = $this->entityTypeManager()->getStorage('node')->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'custom_content')
        ->condition('status', 1);

      // Check if filters are applied.
      $hasFilters = FALSE;
      $or_group = $query->orConditionGroup();

      // Add title condition to the OR group if present.
      if ($title) {
        $or_group->condition('title', '%' . $title . '%', 'LIKE');
        $hasFilters = TRUE;
      }

      // Add tag conditions to the OR group if present.
      if ($tag_names) {
        $tag_names_array = array_map('trim', explode(',', $tag_names));
        $term_storage = $this->entityTypeManager()->getStorage('taxonomy_term');
        $terms = $term_storage->loadByProperties([
          'vid' => 'tags',
          'name' => $tag_names_array,
        ]);

        if (!empty($terms)) {
          $tag_ids = array_map(fn($term) => $term->id(), $terms);

          if (count($tag_ids) > 1) {
            $tags_group = $query->orConditionGroup();
            foreach ($tag_ids as $tag_id) {
              $tags_group->condition('field_tags.target_id', $tag_id);
            }
            $or_group->condition($tags_group);
          } else {
            $or_group->condition('field_tags.target_id', reset($tag_ids));
          }
          $hasFilters = TRUE;
        }
      }

      // Apply the OR condition group only if there are filters.
      if ($hasFilters) {
        $query->condition($or_group);
      }

      // Execute the query.
      $nids = $query->execute();

      // Load nodes if there are results.
      if (!empty($nids)) {
        $nodes = $this->entityTypeManager()->getStorage('node')->loadMultiple($nids);

        foreach ($nodes as $node) {
          $tags = [];
          if (!empty($node->get('field_tags')->getValue())) {
            $tags = $this->collectTargetIds($node->get('field_tags'));
            $tags = implode(', ', $tags);
          }

          $results[] = [
            'id' => $node->id(),
            'title' => $node->getTitle(),
            'description' => $node->get('field_description')->value,
            'image' => $node->get('field_image')->entity ? $node->get('field_image')->entity->uri : '',
            'tags' => $tags,
          ];
        }
      } else {
        // No matching nodes found.
        return $this->buildCacheableJsonResponse([], 404, 'No matching content found.', $cache_contexts, $cache_tags);
      }

    } catch (\Exception $e) {
      // Log the error and return a 500 response.
      \Drupal::logger('tech_task_content_filter')->error($e->getMessage());
      return $this->buildCacheableJsonResponse([], 500, 'An error occurred while processing the request.', $cache_contexts, $cache_tags);
    }

    // Return the response with cache metadata.
    return $this->buildCacheableJsonResponse($results, $response_code, '', $cache_contexts, $cache_tags);
  }

  /**
   * Collects target IDs and resolves names for tags.
   */
  private function collectTargetIds($array) {
    $itemNames = [];
    foreach ($array->referencedEntities() as $item) {
      $itemNames[] = $item->getName();
    }
    return $itemNames;
  }

  /**
   * Builds a CacheableJsonResponse with cache metadata.
   */
  private function buildCacheableJsonResponse(array $data, int $status_code, string $message, array $cache_contexts, array $cache_tags) {
    $response = new CacheableJsonResponse([
      'status' => $status_code === 200 ? 'success' : 'error',
      'message' => $message,
      'data' => $data,
    ], $status_code);

    // Set cacheable metadata.
    $response->addCacheableDependency([
      '#cache' => [
        'contexts' => $cache_contexts,
        'tags' => $cache_tags,
        'max-age' => 3600,
      ],
    ]);

    return $response;
  }

}
