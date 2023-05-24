<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\RulesChecker;
use Cake\ORM\Rule\IsUnique;
use Cake\ORM\Behavior\TranslateBehavior;

class BlogsTable extends Table
{
    public function initialize(array $config)
    {
        $this->addBehavior('Timestamp');
        $this->addBehavior('Translate', [
            'fields' => ['title','description','short_description'],
            'translationTable' => 'I18n'
        ]);
    }
}
