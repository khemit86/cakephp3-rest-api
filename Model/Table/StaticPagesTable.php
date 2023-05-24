<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\Behavior\TranslateBehavior;

class StaticPagesTable extends Table
{
    public function initialize(array $config)
    {
        $this->addBehavior('Timestamp');
        $this->addBehavior('Translate', [
            'fields' => ['title','description'],
            'translationTable' => 'I18n'
        ]);
    }
	
	public function validationHelp($validator)
    {
        $validator
			->requirePresence('first_name')
            ->notEmpty('first_name', __('firstNameRequired'))
			 ->requirePresence('last_name')
            ->notEmpty('last_name', __('lastNameRequired'))
            ->requirePresence('email')
            ->notEmpty('email', __('emailIsRequired'))
            ->add('email', 'validFormat', [
                'rule' => 'email',
                'message' => __('emailMustValid')
            ])
			->requirePresence('message')
            ->notEmpty('message', __('messageRequired'))
			->requirePresence('subject')
            ->notEmpty('subject', __('subjectRequired'))
			->requirePresence('question')
            ->notEmpty('question', __('questionRequired'))
           
            ;
        return $validator;
    }
}
