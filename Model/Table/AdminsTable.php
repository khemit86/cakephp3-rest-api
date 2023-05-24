<?php
// src/Model/Table/AdminsTable.php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Validation\Validator;

class AdminsTable extends Table
{

    public function initialize(array $config)
    {
		$this->primaryKey('id');
		$this->addBehavior('Timestamp');
    }
	
	public function validationDefault(Validator $validator)
    {
        return $validator
            ->notEmpty('username', 'Username is required')
			->add('username', [
				'length' => [
					'rule' => ['minLength', 5],
					'message' => 'Username need to be at least 5 characters long.',
				]
			])
			->notEmpty('first_name', 'First name is required')
			->notEmpty('last_name', 'Last name is required')
			->notEmpty('email', 'Email is required.')
			->add('email', 'validFormat', [
				'rule' => 'email',
				'message' => 'E-mail must be valid.'
			])
            ->notEmpty('password', 'Password is required');
    }
		
	public function validationPassword(Validator $validator )
    {
		
        $validator
            ->add('old_password','custom',[
                'rule'=>  function($value, $context){
					
                    $user = $this->get($context['data']['id']);
                    if ($user) {
                        if ((new DefaultPasswordHasher)->check($value, $user->password)) {
							
                            return true;
                        }
                    }
                    return false;
                },
                'message'=>'The old password does not match the current password!',
            ])
            ->notEmpty('old_password');

        $validator
            ->add('password1', [
                'length' => [
                    'rule' => ['minLength', 6],
                    'message' => 'The password have to be at least 6 characters!',
                ]
            ])
            ->add('password1',[
                'match'=>[
                    'rule'=> ['compareWith','password2'],
                    'message'=>'The passwords does not match!',
                ]
            ])
            ->notEmpty('password1');
			
			
        $validator
            ->add('password2', [
                'length' => [
                    'rule' => ['minLength', 6],
                    'message' => 'The password have to be at least 6 characters!',
                ]
            ])
            ->add('password2',[
                'match'=>[
                    'rule'=> ['compareWith','password1'],
                    'message'=>'The passwords does not match!',
                ]
            ])
            ->notEmpty('password2');

        return $validator;
    }
	
}



?>