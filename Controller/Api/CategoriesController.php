<?php
namespace App\Controller\Api;

use Cake\Event\Event;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Utility\Security;
use Firebase\JWT\JWT;
use Cake\ORM\TableRegistry;
use Cake\I18n\I18n;

class CategoriesController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->Auth->allow(['getParentCategories','getChildCategories','view','index','getDetail','listForJob','getCategoryCostomField','getEditCategoryCostomField']);
    }
      
    public function getParentCategories()
    {
        if ($this->request->is('get')) {
            I18n::locale($this->request->session()->read('Config.language'));
            $this->paginate = [
                    'conditions'=>['parent_id'=>0,'status'=>STATUS_ACTIVE],
                    //'limit'=>APIPageLimit,
                    'order'=>['name'=>'ASC']
                ];
            $categories = $this->paginate($this->Categories);
            
            $this->set([
                'success' => true,
                'categories' => $categories,
                'paging'=>['page'=>$this->request->params['paging']['Categories']['page'],
                                'current'=>$this->request->params['paging']['Categories']['current'],
                                'pageCount'=>$this->request->params['paging']['Categories']['pageCount'],
                                'current'=>$this->request->params['paging']['Categories']['page'],
                                'nextPage'=>$this->request->params['paging']['Categories']['nextPage'],
                                'prevPage'=>$this->request->params['paging']['Categories']['prevPage'],
                                'count'=>$this->request->params['paging']['Categories']['count'],
                                'perPage'=>$this->request->params['paging']['Categories']['perPage'],
                            ],
                '_serialize' => ['categories','success','paging']
            ]);
            $this->_init_language();
            return ;
        } else {
            //throw new MethodNotAllowedException();
            $this->set([
                'success' => false,
                'data' => [
                    'code' =>422,
                    'message' =>__('methodNotAllowed')
                ],
                '_serialize' => ['success', 'data']
            ]);
            return ;
        }
    }
        
    public function getChildCategories()
    {
        if ($this->request->is('get')) {
            I18n::locale($this->request->session()->read('Config.language'));
            $this->paginate = [
                    'conditions'=>['parent_id != 0 ','status'=>STATUS_ACTIVE],
                    'order'=>['name'=>'ASC']
                ];
            $categories = $this->paginate($this->Categories);
            $this->set([
                'success' => true,
                'categories' => $categories,
                'paging'=>['page'=>$this->request->params['paging']['Categories']['page'],
                                'current'=>$this->request->params['paging']['Categories']['current'],
                                'pageCount'=>$this->request->params['paging']['Categories']['pageCount'],
                                'current'=>$this->request->params['paging']['Categories']['page'],
                                'nextPage'=>$this->request->params['paging']['Categories']['nextPage'],
                                'prevPage'=>$this->request->params['paging']['Categories']['prevPage'],
                                'count'=>$this->request->params['paging']['Categories']['count'],
                                'perPage'=>$this->request->params['paging']['Categories']['perPage'],
                            ],
                '_serialize' => ['categories','success','paging']
            ]);
            $this->_init_language();
            return ;
        } else {
            //throw new MethodNotAllowedException();
            $this->set([
                'success' => false,
                'data' => [
                    'code' =>422,
                    'message' =>__('methodNotAllowed')
                ],
                '_serialize' => ['success', 'data']
            ]);
            return ;
        }
    }
        
    public function view()
    {
        if ($this->request->is('post')) {
            if (!empty($this->request->data['categorySlug'])) {
                I18n::locale($this->request->session()->read('Config.language'));
                $categorySlug = $this->request->data['categorySlug'];
                $exists = $this->Categories->exists(['Categories.slug' => $categorySlug,'Categories.status'=>STATUS_ACTIVE]);
                if ($exists) {
                    $categoryDetail = $this->Categories->find()
                                ->where(['slug' => $categorySlug])
                                ->first();
                    $categoryId = $categoryDetail->id;
                    $categoryName = ucwords($categoryDetail->name);
                    $conditions[] = ['Categories.status'=>STATUS_ACTIVE,'Categories.parent_id'=>$categoryId];
        
                    $this->paginate = [
                        'conditions' =>$conditions,
                        'order' => [
                            'Categories.name' => 'ASC']
                    ];
                    $categories = $this->paginate($this->Categories);
                    /*********** other categories start ***********/
                    $categoryQuery = $this->Categories->find('all', ['conditions'=>['parent_id'=>0,'Categories.id != '=>$categoryId,'status'=>STATUS_ACTIVE],'order'=>['name'=>'ASC']]);
                    $otherCategories = $categoryQuery->toArray();
                    /*********** other categories end ***********/
                    $this->set([
                        'success' => true,
                        'categories' => $categories,
                        'otherCategory'=>$otherCategories,
                        'categoryName'=>$categoryName,
                        'categoryDetail'=>$categoryDetail,
                        'paging'=>['page'=>$this->request->params['paging']['Categories']['page'],
                                        'current'=>$this->request->params['paging']['Categories']['current'],
                                        'pageCount'=>$this->request->params['paging']['Categories']['pageCount'],
                                        'current'=>$this->request->params['paging']['Categories']['page'],
                                        'nextPage'=>$this->request->params['paging']['Categories']['nextPage'],
                                        'prevPage'=>$this->request->params['paging']['Categories']['prevPage'],
                                        'count'=>$this->request->params['paging']['Categories']['count'],
                                        'perPage'=>$this->request->params['paging']['Categories']['perPage'],
                                    ],
                        '_serialize' => ['categories','success','paging','otherCategory','categoryName','categoryDetail']
                    ]);
                    $this->_init_language();
                } else {
                    $this->set([
                    'success' => false,
                    'data' => [
                        'code' =>422,
                        'message' =>__('categorySlugInvalid')
                    ],
                    '_serialize' => ['success', 'data']
                    ]);
                    return ;
                }
            } else {
                $this->set([
                    'success' => false,
                    'data' => [
                        'code' =>422,
                        'message' =>__('categorySlugRequired')
                    ],
                    '_serialize' => ['success', 'data']
                    ]);
                return ;
            }
        } else {
            //throw new MethodNotAllowedException();
            $this->set([
                'success' => false,
                'data' => [
                    'code' =>405,
                    'message' =>__('methodNotAllowed')
                ],
                '_serialize' => ['success', 'data']
            ]);
            return ;
        }
    }
    
    public function index()
    {
        if ($this->request->is('get')) {
            I18n::locale($this->request->session()->read('Config.language'));
            
            $query = $this->Categories->find('threaded', ['fields'=>['id','name','parent_id']]);
            $results = $query->toArray();
            $this->set([
                'success' => true,
                'data' => $results,
                '_serialize' => ['data','success']
            ]);
            $this->_init_language();
            return ;
        } else {
            //throw new MethodNotAllowedException();
            $this->set([
                'success' => false,
                'data' => [
                    'code' =>422,
                    'message' =>__('methodNotAllowed')
                ],
                '_serialize' => ['success', 'data']
            ]);
            return ;
        }
    }
        
    public function getDetail()
    {
        if ($this->request->is('post')) {
            if (!empty($this->request->data['categorySlug'])) {
                I18n::locale($this->request->session()->read('Config.language'));
                $categorySlug = $this->request->data['categorySlug'];
                $exists = $this->Categories->exists(['Categories.slug' => $categorySlug]);
                if ($exists) {
                    $categoryDetail = $this->Categories->find()
                                ->where(['slug' => $categorySlug,'status'=>STATUS_ACTIVE])
                                ->first()->toArray();
                    
                    $this->set([
                        'success' => true,
                        'categoryDetail' => $categoryDetail,
                        '_serialize' => ['categoryDetail','success']
                    ]);
                    $this->_init_language();
                    return ;
                } else {
                    $errors = ['categorySlug'=>['_invalid'=>__('categorySlugInvalid')]];
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' =>__('validationErrorsOccured'),
                            'error' => '',
                            'errorCount' => 1,
                            'errors' => $errors,
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
            } else {
                $errors = ['categorySlug'=>['_empty'=>__('categorySlugRequired')]];
                $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' =>__('validationErrorsOccured'),
                            'error' => '',
                            'errorCount' => 1,
                            'errors' => $errors,
                            ],
                        '_serialize' => ['success', 'data']]);
                return ;
            }
        } else {
            //throw new MethodNotAllowedException();
            $this->set([
                'success' => false,
                'data' => [
                    'code' =>405,
                    'message' =>__('methodNotAllowed')
                ],
                '_serialize' => ['success', 'data']
            ]);
            return ;
        }
    }
        
    public function listForJob()
    {
        if ($this->request->is('post')) {
            if (!empty($this->request->data['categoryId'])) {
                I18n::locale($this->request->session()->read('Config.language'));
                $exists = $this->Categories->exists(['Categories.id' => $this->request->data['categoryId'],'Categories.status'=>STATUS_ACTIVE]);
                
                I18n::locale($this->request->session()->read('Config.language'));
                $CategoriesList = $this->Categories->find('path', ['for' => $this->request->data['categoryId']]);
                $this->set([
                        'success' => true,
                        'categories' => $CategoriesList,
                        '_serialize' => ['categories','success']
                    ]);
                $this->_init_language();
            } else {
                $this->set([
                    'success' => false,
                    'data' => [
                        'code' =>422,
                        'message' =>__('categoryIdRequired')
                    ],
                    '_serialize' => ['success', 'data']
                    ]);
                return ;
            }
        } else {
            //throw new MethodNotAllowedException();
            $this->set([
                'success' => false,
                'data' => [
                    'code' =>405,
                    'message' =>__('methodNotAllowed')
                ],
                '_serialize' => ['success', 'data']
            ]);
            return ;
        }
    }
	
	public function getEditCategoryCostomField(){
	
		if ($this->request->is('post')) {
			if (!empty($this->request->data['categoryId'])) {
				I18n::locale($this->request->session()->read('Config.language'));
				$categoryId = $this->request->data['categoryId'];
				$this->loadModel('CategoriesCustomFields');	
				$exists = $this->CategoriesCustomFields->exists(['CategoriesCustomFields.category_id' => $categoryId]);
				if ($exists) {
					$this->CategoriesCustomFields->belongsTo('Categories', [
						'className' => 'Categories',
						'foreignKey'=>'category_id'
					]);
					$this->CategoriesCustomFields->belongsTo('CustomFields', [
						'className' => 'CustomFields',
						'foreignKey'=>'custom_field_id'
					]);
					$categoriesCustomFields = $this->CategoriesCustomFields->find()
						->contain(['Categories'=>['fields'=>['Categories.slug','Categories.name']],'CustomFields'=>['fields'=>['CustomFields.label','CustomFields.name','CustomFields.type','CustomFields.options']]])
						->where(['CategoriesCustomFields.category_id' => $categoryId])
						->toArray();
					// pr($categoriesCustomFields);die;
					$this->set([
						'success' => true,
						'categoriesCustomFields' => $categoriesCustomFields,
						'_serialize' => ['categoriesCustomFields','success']
					]);
					$this->_init_language();
					return ;
				} else {
					$this->set([
						'success' => false,
							'data' => [
							'code' =>422,
								'message' =>__('categoryIdRequired')
							],
						'_serialize' => ['success', 'data']
					]);
					return ;
				}
			} else {
			$this->set([
			'success' => false,
				'data' => [
					'code' =>405,
					'message' =>__('methodNotAllowed')
				],
				'_serialize' => ['success', 'data']
			]);
			return ;
			}
		} else {
			$this->set([
				'success' => false,
				'data' => [
					'code' =>405,
					'message' =>__('methodNotAllowed')
				],
				'_serialize' => ['success', 'data']
			]);
			return ;
		}
	}
	
	public function getCategoryCostomField(){
	
		if ($this->request->is('post')) {
			if (!empty($this->request->data['categoryId'])) {
				I18n::locale($this->request->session()->read('Config.language'));
				$categoryId = $this->request->data['categoryId'];
				$this->loadModel('CategoriesCustomFields');	
				$exists = $this->CategoriesCustomFields->exists(['CategoriesCustomFields.category_id' => $categoryId]);
				if ($exists) {
					$this->CategoriesCustomFields->belongsTo('Categories', [
						'className' => 'Categories',
						'foreignKey'=>'category_id'
					]);
					$this->CategoriesCustomFields->belongsTo('CustomFields', [
						'className' => 'CustomFields',
						'foreignKey'=>'custom_field_id'
					]);
					$categoriesCustomFields = $this->CategoriesCustomFields->find()
						->contain(['Categories'=>['fields'=>['Categories.slug','Categories.name']],'CustomFields'=>['fields'=>['CustomFields.label','CustomFields.name','CustomFields.type','CustomFields.options']]])
						->where(['CategoriesCustomFields.category_id' => $categoryId])
						->toArray();
					
					$this->set([
						'success' => true,
						'categoriesCustomFields' => $categoriesCustomFields,
						'_serialize' => ['categoriesCustomFields','success']
					]);
					$this->_init_language();
					return ;
				} else {
					$this->set([
						'success' => false,
							'data' => [
							'code' =>422,
								// 'message' =>__('categoryIdRequired')
							],
						'_serialize' => ['success', 'data']
					]);
					return ;
				}
			} else {
			$this->set([
			'success' => false,
				'data' => [
					'code' =>405,
					'message' =>__('methodNotAllowed')
				],
				'_serialize' => ['success', 'data']
			]);
			return ;
			}
		} else {
			$this->set([
				'success' => false,
				'data' => [
					'code' =>405,
					'message' =>__('methodNotAllowed')
				],
				'_serialize' => ['success', 'data']
			]);
			return ;
		}
	}
	
	
}
