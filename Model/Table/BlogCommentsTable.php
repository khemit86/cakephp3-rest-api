<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Roles Model
 */
class BlogCommentsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->addBehavior('Timestamp');
        $this->addBehavior('Translate', [
            'fields' => ['title','description'],
            'translationTable' => 'I18n'
        ]);
    }
    
    public function validationFront($validator)
    {
        $validator
            ->requirePresence('comment')
            ->notEmpty('comment', __('commentRequired'))
            ->add('comment', 'required', array(
                'rule' => 'notBlank',
                'required' => true
            ));

        return $validator;
    }
    
    
    
    public function validationApi($validator)
    {
        $validator
            ->requirePresence('blog_id')
            ->notEmpty('comment', 'Blog id is required')
            ->add('comment', 'required', array(
                'rule' => 'notBlank',
                'required' => true
            ))
            ->requirePresence('comment')
            ->notEmpty('comment', __('Comment is required.'))
            ->add('comment', 'required', array(
                'rule' => 'notBlank',
                'required' => true
            ));

        return $validator;
    }
}
