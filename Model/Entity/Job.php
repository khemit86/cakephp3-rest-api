<?php
namespace App\Model\Entity;
use Cake\ORM\Entity;
/**
 * Role Entity.
 */
class Job extends Entity
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
	  
	 public function setErrors($errors)
	{
		$this->_errors = $errors;
	} 
}