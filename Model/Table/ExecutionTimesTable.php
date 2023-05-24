<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ExecutionTimesTable extends Table
{
    public function initialize(array $config)
    {
       $this->addBehavior('Timestamp');
		
    }
	
	
	public function validationDefault(Validator $validator)
    {
        $validator
            ->notEmpty('type_value');

        return $validator;
    }
	
}
?>