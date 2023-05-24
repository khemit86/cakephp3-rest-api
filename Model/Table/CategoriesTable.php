<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\Rule\IsUnique;
use Cake\ORM\Behavior\TranslateBehavior;

class CategoriesTable extends Table
{
    public function initialize(array $config)
    {
        $this->addBehavior('Timestamp');
        $this->addBehavior('Translate', [
            'fields' => ['name','description'],
            'translationTable' => 'I18n'
        ]);
        $this->addBehavior('Tree');
    }
}
