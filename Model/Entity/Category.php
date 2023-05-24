<?php
namespace App\Model\Entity;
use Cake\ORM\Behavior\Translate\TranslateTrait;
use Cake\ORM\Entity;

/**
 * User Entity.
 */
class Category extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array
     */
	  protected $_accessible = [
    '*' => true,
    'id' => false,
  ];
	use TranslateTrait;
    
}