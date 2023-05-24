<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class NewsletterEmailTemplatesTable extends Table
{
    public function initialize(array $config)
    {
       $this->addBehavior('Timestamp');
		
    }
	
	public function validationDefault(Validator $validator)
    {
        $validator
            ->notEmpty('title')
            ->notEmpty('subject')
            ->notEmpty('description');

        return $validator;
    }
	
}

?>