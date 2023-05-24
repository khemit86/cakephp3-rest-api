<?php
namespace App\Model\Table;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
/**
 * Users Model
 */
class NewsLettersTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->table('newsletters');
        $this->displayField('id');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');
    }
	
	
	public function validationNewsletter($validator){
		
		$validator = new Validator();
        $validator
			
			->notEmpty('email', 'Email is required.')
			->add('email', 'validFormat', [
				'rule' => 'email',
				'message' => 'E-mail must be valid.'
			])
			;
			
        return $validator;
    }
	
	public function validationAdmin($validator){
		
		$validator
			->notEmpty('newsletter_id')
			->notEmpty('newsletter_id', 'Newsletter template is required.')
			
			 ;
		return $validator;
    }
}