<?php
namespace App\Model\Table;

use App\Model\Entity\UserDetail;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Users Model
 */
class UserDetailsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->table('user_details');
        $this->displayField('id');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');
    }
    
    public function validationUpdate($validator)
    {
        $validator
            ->requirePresence('interested_become_worker')
            ->notEmpty('interested_become_worker', __('Interested in becoming a Worker'));
        return $validator;
    }
}
