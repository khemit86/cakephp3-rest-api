<?php
namespace App\Model\Table;
use App\Model\Entity\Project;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
/**
 * Roles Model
 */
class ProjectsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->table('projects');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');
    }
	
	public function validationDefault(Validator $validator)
    {
		 $validator = new Validator();
        $validator
           ->requirePresence('title')
			->notEmpty('title', 'Title is required.')
			->add('title', 'required', array(
				'rule' => 'notBlank',
				'required' => true
			))
			->add('title', [
				'length' => [
					'rule' => ['lengthBetween', 4, 100],
					'message' => 'title Between 4 to 100 characters.',
				]
			])
			->requirePresence('description')
			->notEmpty('description', 'Description is required.')
			->add('description', 'required', array(
				'rule' => 'notBlank',
				'required' => true
			))
			;
			
        return $validator;
    }
    
	
	/**
     * Custom validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationFront($validator)
    { 
        $validator
           ->requirePresence('title')
			->notEmpty('title', __('titleRequired'))
			->add('title', 'required', array(
				'rule' => 'notBlank',
				'required' => true
			))
			->add('title', [
				'length' => [
					'rule' => ['lengthBetween', 4, 100],
					'message' => __('titleBetween4To100'),
				]
			])
			->requirePresence('description')
			->notEmpty('description', __('DescriptionRequired'))
			->add('description', 'required', array(
				'rule' => 'notBlank',
				'required' => true
			));
			
        return $validator;
    }
	
	public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->isUnique(['title','user_id'],__('titleAlreadyExists')));
		return $rules;
    }
}