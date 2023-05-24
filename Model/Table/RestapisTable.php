<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Roles Model
 */
class RestapisTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->table('restapis');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');
    }
    
    public function validationDefault(Validator $validator)
    {
        $validator
            ->requirePresence('title')
            ->notEmpty('title', 'Title is required.')
            ->add('title', 'required', array(
                'rule' => 'notBlank',
                'required' => true
            ))
            ->add('title', [
                'length' => [
                    'rule' => ['lengthBetween', 4, 100],
                    'message' => 'Title Between 4 to 100 characters.',
                ]
            ]);
        return $validator;
    }
    
    public function validationFront($validator)
    {
        $validator
            ->requirePresence('title')
            ->notEmpty('title', __('titleRequired'))
            ->add('title', 'required', array(
                'rule' => 'notBlank',
                'required' => true
            ))
            ->add('title', [
                'length' => [
                    'rule' => ['lengthBetween', 4, 100],
                    'message' => __('titleBetween4To100'),
                ]
            ])
            ->requirePresence('description')
            ->notEmpty('description', __('DescriptionRequired'))
            ->add('description', 'required', array(
                'rule' => 'notBlank',
                'required' => true
            ))
            ->requirePresence('category_id')
            ->notEmpty('category_id', __('categoryRequired'))
            ->add('category_id', 'required', array(
                'rule' => 'notBlank',
                'required' => true
            ))
            ->requirePresence('end_date')
            ->notEmpty('end_date', __('EnddateRequired'))
            ->add('end_date', 'required', array(
                'rule' => 'notBlank',
                'required' => true
            ))
            ->requirePresence('zipcode')
            ->notEmpty('zipcode', __('postalcodeRequired'))
            ->add('zipcode', 'required', array(
                'rule' => 'notBlank',
                'required' => true
            ))
            ->requirePresence('location')
            ->notEmpty('location', __('locationRequired'))
            ->add('location', 'required', array(
                'rule' => 'notBlank',
                'required' => true
            ))
            ->requirePresence('area_range_id')
            ->notEmpty('area_range_id', __('areaRangeRequired'))
            ->add('area_range_id', 'required', array(
                'rule' => 'notBlank',
                'required' => true
            ));
        return $validator;
    }
}
