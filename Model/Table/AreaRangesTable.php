<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class AreaRangesTable extends Table
{
    public function initialize(array $config)
    {
       $this->addBehavior('Timestamp');
		
    }
	
	
	public function validationDefault(Validator $validator)
    {
        $validator
            ->notEmpty('a_range');

        return $validator;
    }
	
}
?>