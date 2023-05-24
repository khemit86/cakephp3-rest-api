<?php
namespace App\Model\Table;
use Cake\ORM\Query;
use Cake\ORM\Table;


class MessagesTable extends Table
{
    public function initialize(array $config)
    {
		$this->primaryKey('id');
		$this->addBehavior('Timestamp');
    }
	
	public function validationApi($validator){
		
		$validator
			->requirePresence('sinch_message_id')
			->notEmpty('sinch_message_id', 'Sinch Message id is required.')
			->add('sinch_message_id', 'required', array(
				'rule' => 'notBlank',
				'required' => true
			))
			->requirePresence('message')
			->notEmpty('message', 'Message is required.')
			->add('message', 'required', array(
				'rule' => 'notBlank',
				'required' => true
			))
			->requirePresence('receiver_id')
			->notEmpty('receiver_id', 'Receiver Id is required.')
			->add('receiver_id', 'required', array(
				'rule' => 'notBlank',
				'required' => true
			))
			;
		return $validator;
		
		
    }
	
	
	
	
}
?>