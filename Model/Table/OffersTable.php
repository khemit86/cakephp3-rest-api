<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class OffersTable extends Table
{
    public function initialize(array $config)
    {
        $this->table('offers');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');
    }
    
    
    public function validationDefault(Validator $validator)
    {
        $validator
            ->notEmpty('amount')
            ->add('amount', 'validFormat', [
                'rule' => ['custom','/^[1-9][0-9]*$/'],
                'required' => true,
                'message' => __('positiveNumbersOnly')
            ]);

        return $validator;
    }

    
    public function validationFront($validator)
    {
        $validator
            ->requirePresence('slug')
            ->notEmpty('slug', __('SlugRequired'))
            ->requirePresence('description')
            ->notEmpty('description', __('DescriptionRequired'))
            ->add('description', 'required', array(
                'rule' => 'notBlank',
                'required' => true
            ))
            ->requirePresence('price')
            ->notEmpty('price', __('priceRequired'))
            ->add('price', 'required', array(
                'rule' => 'notBlank',
                'required' => true
            ))
            ->add('price', 'required', [
                 'rule' => array('money', 'left'),
                'message' => __('supplyMonetaryPrice')
            ])
             ;
        return $validator;
    }
}
