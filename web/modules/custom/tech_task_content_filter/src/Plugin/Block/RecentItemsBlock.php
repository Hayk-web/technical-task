<?php

namespace Drupal\tech_task_content_filter\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to display recent items.
 *
 * @Block(
 *   id = "recent_items_block",
 *   admin_label = @Translation("Recent Items Block")
 * )
 */
class RecentItemsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGenerator
   */
  protected $fileUrlGenerator;


  /**
   * Constructs a RecentItemsBlock object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, FileUrlGenerator $fileUrlGenerator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $fileUrlGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'custom_content')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, 3);
    $nids = $query->execute();
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    $items = [];
    foreach ($nodes as $node) {
      $items[] = [
        'title' => $node->getTitle(),
        'description' => strip_tags($node->get('field_description')->value),
        'image' => $node->get('field_image')->entity ? $this->fileUrlGenerator->generateAbsoluteString($node->get('field_image')->entity->uri->value) : '',
        'tags' => $this->tagNames($node->get('field_tags'))
      ];
    }


    // For caching may be used CachableMetadata.
    return [
      '#theme' => 'recent_items',
      '#items' => $items,
      '#attached' => [
        'library' => [
          'tech_task_content_filter/cards',
        ]
      ],
      '#cache' => [
        'tags' => ['node_list'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('file_url_generator')
    );
  }

  private function tagNames($tags) {
    $itemNames = [];
    foreach ($tags->referencedEntities() as $item) {
      $itemNames[] = $item->getName();
    }
    return $itemNames;
  }
}
