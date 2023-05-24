<?php
namespace App\Model\Table;
use App\Model\Entity\Role;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
/**
 * Events Model
 */
class EventsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->table('events');
        $this->displayField('id');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');
    }

	
   public function validationApi($validator)
    {
		
		$validator
			->requirePresence('worker_id')
			->notEmpty('worker_id', 'worker_id is required')
			->add('worker_id', 'required', array(
				'rule' => 'notBlank',
				'required' => true
			))
			->requirePresence('title')
			->notEmpty('title', 'title is required')
			->add('title', 'required', array(
				'rule' => 'notBlank',
				'required' => true
			))
			->requirePresence('start')
			->notEmpty('start', __('start date is required.'))
			->add('start', 'required', array(
				'rule' => 'notBlank',
				'required' => true
			))
			->requirePresence('end')
			->notEmpty('end', __('end date is required.'))
			->add('end', 'required', array(
				'rule' => 'notBlank',
				'required' => true
			))
			
			;	

		return $validator;			
	}
}