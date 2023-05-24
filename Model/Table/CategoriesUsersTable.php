<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Rule\IsUnique;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class CategoriesUsersTable extends Table
{
    public function initialize(array $config)
    {
        $this->table('categories_users');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');
    }

}
