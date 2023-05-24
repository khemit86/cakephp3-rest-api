<?php
namespace App\Model\Table;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
/**
 * Roles Model
 */
class WorkersTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->table('workers');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');
    }
	
	public function validationApi($validator)
    {
		
		$validator
			->requirePresence('first_name')
			->notEmpty('first_name', 'First name is required.')
			->add('first_name', 'required', array(
				'rule' => 'notBlank',
				'required' => true
			))
			->add('first_name', [
				'length' => [
					'rule' => ['lengthBetween', 4, 100],
					'message' => 'First name Between 4 to 100 characters.',
				]
			])
			->requirePresence('last_name')
			->notEmpty('last_name', 'Last name is required.')
			->add('last_name', 'required', array(
				'rule' => 'notBlank',
				'required' => true
			))
			->add('last_name', [
				'length' => [
					'rule' => ['lengthBetween', 4, 100],
					'message' => 'Last name Between 4 to 100 characters.',
				]
			])
			->requirePresence('email')
			->notEmpty('email', 'Email is required.')
			->add('email', 'required', array(
				'rule' => 'notBlank',
				'required' => true
			))
			->add('email', 'validFormat', [
				'rule' => 'email',
				'message' => 'E-mail must be valid.'
			])
			
           ;
		return $validator;
		
		
    }
    
	public function validationFront($validator)
    {
		
		$validator
			
			->notEmpty('first_name', __('firstNameRequired'))
			->add('first_name', 'required', array(
				'rule' => 'notBlank',
				'required' => true
			))
			->add('first_name', [
				'length' => [
					'rule' => ['lengthBetween', 4, 100],
					'message' => __('firstNameBetween4To100'),
				]
			])
			->requirePresence('last_name')
			->notEmpty('last_name', __('lastNameRequired'))
			->add('last_name', 'required', array(
				'rule' => 'notBlank',
				'required' => true
			))
			->add('last_name', [
				'length' => [
					'rule' => ['lengthBetween', 4, 100],
					'message' => __('lastNameBetween4To100'),
				]
			])
			->add('email', 'validFormat', [
				'rule' => 'email',
				'message' => __('emailMustValid'),
				'required' => false
			]);
		return $validator;
		
		
    }
	
	public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->isUnique(['email','user_id'],__('emailAlreadyExists')));
		return $rules;
    }
}