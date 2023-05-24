<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Rule\IsUnique;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class JobFeedbacksTable extends Table
{
    public function initialize(array $config)
    {
        $this->table('job_feedbacks');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');
    }
    public function validationApi($validator)
    {
        $validator
            ->requirePresence('offer_id')
            ->notEmpty('offer_id', __('offer_id is required.'))
            ->add('offer_id', 'required', array(
                'rule' => 'notBlank',
                'required' => true
            ))
            ->requirePresence('job_id')
            ->notEmpty('job_id', __('job_id is required.'))
            ->add('job_id', 'required', array(
                'rule' => 'notBlank',
                'required' => true
            ))
            ->requirePresence('member_id')
            ->notEmpty('member_id', __('member_id is required.'))
            ->add('member_id', 'required', array(
                'rule' => 'notBlank',
                'required' => true
            ))
            ->requirePresence('message')
            ->notEmpty('message', __('message is required.'))
            ->add('message', 'required', array(
                'rule' => 'notBlank',
                'required' => true
            ))
            ->requirePresence('rating')
            ->notEmpty('rating', __('rating is required.'))
            ->add('rating', 'required', array(
                'rule' => 'notBlank',
                'required' => true
            ))
            
            ;

        return $validator;
    }
}
