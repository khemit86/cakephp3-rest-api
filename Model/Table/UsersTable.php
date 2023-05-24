<?php
namespace App\Model\Table;

use App\Model\Entity\User;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Auth\DefaultPasswordHasher;

/**
 * Users Model
 */

class UsersTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->table('users');
        $this->displayField('id');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');
    }
    public function validationDefault(Validator $validator)
    {
        $validator = new Validator();
        $validator
            ->notEmpty('username', __('usernameRequired'))
            ->add('username', [
                'length' => [
                    'rule' => ['lengthBetween', 6, 25],
                    'message' =>  __('usernameBetween6To25Character'),
                ]
            ])
            ->add('username', 'validFormat', [
              'rule' => 'alphanumeric',
              'message' => __('onlyAlphabetsNumbersAllowed'),
            ])
            ->notEmpty('password', __('passwordRequired'))
            ->add('password', 'required', [
                'rule' => 'notBlank',
                'required' => true
            ])
            ->add('password', 'size', [
                'rule' => ['lengthBetween', 4, 20],
                'message' => __('passwordShouldBeBetween4To20')
            ])
            ->notEmpty('email', __('emailIsRequired'))
            ->add('email', 'validFormat', [
                'rule' => 'email',
                'message' => __('emailMustValid')
            ])
            
            ->notEmpty('first_name', __('firstNameRequired'))
          /*   ->add('first_name', 'validFormat', [
              'rule' => 'alphanumeric',
              'message' => __('onlyAlphabetsNumbersAllowed'),
            ]) */
            ->add('first_name', [
                'length' => [
                    'rule' => ['lengthBetween', 4, 100],
                    'message' =>  __('firstNameBetween4To100'),
                ]
            ])
            
            ->notEmpty('last_name', __('lastNameRequired'))
           /*  ->add('last_name', 'validFormat', [
              'rule' => 'alphanumeric',
              'message' => __('onlyAlphabetsNumbersAllowed'),
            ]) */
            ->add('last_name', [
                'length' => [
                    'rule' => ['lengthBetween', 4, 100],
                    'message' => __('lastNameBetween4To100'),
                ]
            ])
        
            ->add('image', [
                'fileSize' => [
                        'rule' => ['fileSize', '<=', '1MB'],
                        'message' => __('profileImageMustBeLessThen1mb'),
                        'allowEmpty' => true,
                ]

            ])
            ->notEmpty('zipcode', __('postalcodeRequired'))
            ->add('zipcode', 'validFormat', [
              'rule' => 'numeric',
              'message' => __('positiveNumbersOnly'),
            ])
            ->add('zipcode', [
                'length' => [
                    'rule' => ['maxLength', 5],
                    'message' => __('postalCodelength5'),
                ],
            ])
            
            
            ->notEmpty('street_address', __('streetAddressRequired'))
            ->add('street_address', [
                'length' => [
                    'rule' => ['lengthBetween', 4, 100],
                    'message' =>  __('streetAddressBetween4To100'),
                ]
            ])
            ;
        return $validator;
    }
    
    
    
    /**
     * Custom validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationEditprofile($validator)
    {
        $validator
            ->requirePresence('first_name')
            ->notEmpty('first_name', __('firstNameRequired'))
            ->add('first_name', [
                'length' => [
                    'rule' => ['lengthBetween', 4, 100],
                    'message' =>  __('firstNameBetween4To100'),
                ]
            ])
            ->requirePresence('last_name')
            ->notEmpty('last_name', __('lastNameRequired'))
            ->add('last_name', [
                'length' => [
                    'rule' => ['lengthBetween', 4, 100],
                    'message' => __('lastNameBetween4To100'),
                ]
            ])
            ->requirePresence('location')
            ->notEmpty('location', __('locationRequired'));
            
        return $validator;
    }
    public function validationForgotpassword($validator)
    {
        $validator
            ->requirePresence('email')
            ->notEmpty('email', __('emailIsRequired'))
            ->add('email', 'validFormat', [
                'rule' => 'email',
                'message' => __('emailMustValid')
            ])
            ;
        return $validator;
    }
    
    public function validationEditbackgroundinfo($validator)
    {
        $validator
            ->requirePresence('first_name')
            ->notEmpty('first_name', __('firstNameRequired'))
            ->requirePresence('last_name')
            ->notEmpty('last_name', __('lastNameRequired'))
            ;
        return $validator;
    }
    
    
    /**
     * Custom validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationGooglpluslogin($validator)
    {
        $validator
            ->requirePresence('email')
            ->notEmpty('email', __('emailIsRequired'))
            ->add('email', 'validFormat', [
                'rule' => 'email',
                'message' => __('emailMustValid')
            ])
            ->requirePresence('first_name')
            ->notEmpty('first_name', __('firstNameRequired'))
            ->requirePresence('googleplus_id')
            ->notEmpty('googleplus_id', __('googlePlusIdRequired'))
            ;
        return $validator;
    }
    
    
    public function validationTwitterlogin($validator)
    {
        $validator
            ->requirePresence('twitter_id')
            ->notEmpty('twitter_id', __('twitterIdRequired'))
            ->requirePresence('name')
            ->notEmpty('name', __('nameRequired'))
            ;
        return $validator;
    }
    
    public function validationFacebooklogin($validator)
    {
        $validator
            ->requirePresence('facebook_id')
            ->notEmpty('facebook_id', __('facebookIdRequired'))
            ->requirePresence('email')
            ->notEmpty('email', __('emailIsRequired'))
            ->add('email', 'validFormat', [
                'rule' => 'email',
                'message' => __('emailMustValid')
            ])
            ->requirePresence('first_name')
            ->notEmpty('first_name', __('firstNameRequired'))
            ->requirePresence('last_name')
            ->notEmpty('first_name', __('lastNameRequired'))
            ;
        return $validator;
    }
    
    
    public function validationPassword(Validator $validator)
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
                    'message' => __('passwordAtLeast6Character'),
                ]
            ])
            ->notEmpty('password1');
        $validator
            ->add('password2', [
                'length' => [
                    'rule' => ['minLength', 6],
                    'message' => __('passwordAtLeast6Character'),
                ]
            ])
            ->add('password2', [
                'match'=>[
                    'rule'=> ['compareWith','password1'],
                    'message'=>__('passwordDoesNotMatch'),
                ]
            ])
            ->notEmpty('password2');

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
        $rules->add($rules->isUnique(['username'], __('userNameAlreadyExists')));
        $rules->add($rules->isUnique(['email'], __('emailAlreadyExists')));
        return $rules;
    }
    
    public function validationContact($validator)
    {
        $validator
            ->requirePresence('salution')
            ->notEmpty('salution', __('salutionRequired'))
            ->add('salution', 'required', [
                'rule' => 'notBlank',
                'required' => true
            ])
            ->requirePresence('first_name')
            ->notEmpty('first_name', __('firstNameRequired'))
            ->add('first_name', 'required', [
                'rule' => 'notBlank',
                'required' => true
            ])
            ->requirePresence('last_name')
            ->notEmpty('last_name', __('lastNameRequired'))
            ->add('last_name', 'required', [
                'rule' => 'notBlank',
                'required' => true
            ])
            
            ->requirePresence('email')
            ->notEmpty('email', 'Email is required.')
            ->add('email', 'required', [
                'rule' => 'notBlank',
                'required' => true
            ])
            ->requirePresence('message')
            ->notEmpty('message', __('messageRequired'))
            ->add('message', 'required', [
                'rule' => 'notBlank',
                'required' => true
            ])
            ;
        return $validator;
    }
    
    
    public function validationChangePassword(Validator $validator)
    {
        $validator
            ->add('old_password', 'custom', [
                'rule'=>  function ($value, $context) {
                    $user = $this->get($context['data']['id']);
                    if ($user) {
                        if ((new DefaultPasswordHasher)->check($value, $user->password)) {
                            return true;
                        }
                    }
                    return false;
                },
                'message'=>__('oldPasswordNotMatch'),
            ])
            ->notEmpty('old_password');

        $validator
            ->add('password1', [
                'length' => [
                    'rule' => ['minLength', 6],
                    'message' => __('passwordAtLeast6Character'),
                ]
            ])
            ->add('password1', [
                'match'=>[
                    'rule'=> ['compareWith','password2'],
                    'message'=>__('passwordsDoesNotMatch'),
                ]
            ])
            ->notEmpty('password1');
            
            
        $validator
            ->add('password2', [
                'length' => [
                    'rule' => ['minLength', 6],
                    'message' => __('passwordAtLeast6Character'),
                ]
            ])
            ->add('password2', [
                'match'=>[
                    'rule'=> ['compareWith','password1'],
                    'message'=>__('passwordsDoesNotMatch'),
                ]
            ])
            ->notEmpty('password2');

        return $validator;
    }
	
	
}
