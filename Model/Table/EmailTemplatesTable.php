<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\Behavior\TranslateBehavior;

class EmailTemplatesTable extends Table
{
    public function initialize(array $config)
    {
		
		$this->addBehavior('Translate', [
            'fields' => ['title','subject','description'],
			'translationTable' => 'I18n'
            //'validator' => 'translated'
        ]);
       $this->addBehavior('Timestamp');
		
    }
	
	public function validationDefault(Validator $validator)
    {
        $validator
            ->notEmpty('title')
            ->notEmpty('subject')
            ->notEmpty('description');
			
			/* $translationValidator = new Validator();
			$translationValidator
			->requirePresence('title') 
			->notEmpty('title')
			->requirePresence('subject') 
			->notEmpty('subject')
			->requirePresence('description') 
			->notEmpty('description')
			; */

		/*  $validator
        ->addNestedMany('_translations', $translationValidator)
        // To prevent "field is required" for the "_translations" data
        ->requirePresence('_translations')
        ->notEmpty('_translations')
        ;
      */
        
			
        return $validator;
    }
	
}



?>