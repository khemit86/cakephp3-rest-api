<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

class BudgetsTable extends Table
{
    public function initialize(array $config)
    {
		$this->addBehavior('Timestamp');
		
    }
	
	
	public function validationDefault(Validator $validator)
    {
        $validator
            ->notEmpty('amount')
			->add('amount', 'required', [
				 'rule' => array('money', 'left'),
				'message' => 'Please supply a valid monetary amount.'
			])
			/*->add('amount', 'validFormat', [
				'rule' => ['custom','/^[1-9][0-9]*$/'],
				'required' => true,
                'message' => 'Positive numbers only.'
			])*/
			;

        return $validator;
    }
	
	/**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->isUnique(['amount'],__('Amount already exists.')));
        return $rules;
    }
}
?>