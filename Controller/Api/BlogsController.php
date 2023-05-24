<?php
namespace App\Controller\Api;

use Cake\Event\Event;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Utility\Security;
use Firebase\JWT\JWT;
use Cake\ORM\TableRegistry;
use Cake\I18n\I18n;

class BlogsController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->Auth->allow(['index','view','comments','blogList','postBlogComment','commentDelete','getBlogCommentCount']);
    }
      
    public function index()
    {
        if ($this->request->is('post')) {
            I18n::locale($this->request->session()->read('Config.language'));
            $this->loadModel('Categories');
            
            if (!isset($this->request->data['categorySlug'])) {
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
            $categorySlug = $this->request->data['categorySlug'];
            if (!empty($categorySlug)) {
                $exists = $this->Categories->exists(['Categories.slug' => $categorySlug]);
                if ($exists) {
                    $categoryDetail = $this->Categories->find()
                            ->where(['slug' => $categorySlug])
                            ->first();
                    $categoryId = $categoryDetail->id;
                    $conditions[] = ['Blogs.category_id'=>$categoryId];
                }
            }
            $this->Blogs->belongsTo('Categories', [
                'className' => 'Categories',
                'foreignKey' => 'category_id'
            ]);
           /*  $this->Blogs->hasOne('BlogComments'); */
            $conditions[] = ['Blogs.status'=>STATUS_ACTIVE,'Categories.status'=>STATUS_ACTIVE];
        
        
            $this->paginate = [
            
            'limit' => FRONT_PAGE_LIMIT,
            'conditions' =>$conditions,
            'order' => [
                'Blogs.id' => 'DESC'
            ],
            'contain'=>[
                'Categories'=>['fields'=>['Categories.id']]/* ,
                'BlogComments'=> function ($q) {
                    return $q->select(
                        [
                            'id',
                            'total_comments' => $q->func()->count('BlogComments.id')
                       ])
                       ->group(['blog_id']);
                } */
            ],
            'group'=>'Blogs.id'
        ];
            $blogs = $this->paginate($this->Blogs);
        
            $this->set([
                'success' => true,
                'blogs' => $blogs,
                'paging'=>['page'=>$this->request->params['paging']['Blogs']['page'],
                                'current'=>$this->request->params['paging']['Blogs']['current'],
                                'pageCount'=>$this->request->params['paging']['Blogs']['pageCount'],
                                'current'=>$this->request->params['paging']['Blogs']['page'],
                                'nextPage'=>$this->request->params['paging']['Blogs']['nextPage'],
                                'prevPage'=>$this->request->params['paging']['Blogs']['prevPage'],
                                'count'=>$this->request->params['paging']['Blogs']['count'],
                                'perPage'=>$this->request->params['paging']['Blogs']['perPage'],
                            ],
                '_serialize' => ['blogs','success','paging']
            ]);
            $this->_init_language();
            return ;
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
    
    public function blogList()
    {
        if ($this->request->is('get')) {
            I18n::locale($this->request->session()->read('Config.language'));
            $this->loadModel('Categories');
        
            
            $this->Blogs->hasOne('BlogComments');
            $conditions[] = ['Blogs.status'=>STATUS_ACTIVE];
        
        
            $this->paginate = [
            
            'limit' => FRONT_PAGE_LIMIT,
            'conditions' =>$conditions,
            'order' => [
                'Blogs.id' => 'DESC'
            ],
            'contain'=>[
                'BlogComments'=> function ($q) {
                    return $q->select(
                        [
                            'id',
                            'total_comments' => $q->func()->count('BlogComments.id')
                       ])
                       ->group(['blog_id']);
                }
            ],
            'group'=>'Blogs.id'
        ];
            $blogs = $this->paginate($this->Blogs);
        
            $this->set([
                'success' => true,
                'blogs' => $blogs,
                'paging'=>['page'=>$this->request->params['paging']['Blogs']['page'],
                                'current'=>$this->request->params['paging']['Blogs']['current'],
                                'pageCount'=>$this->request->params['paging']['Blogs']['pageCount'],
                                'current'=>$this->request->params['paging']['Blogs']['page'],
                                'nextPage'=>$this->request->params['paging']['Blogs']['nextPage'],
                                'prevPage'=>$this->request->params['paging']['Blogs']['prevPage'],
                                'count'=>$this->request->params['paging']['Blogs']['count'],
                                'perPage'=>$this->request->params['paging']['Blogs']['perPage'],
                            ],
                '_serialize' => ['blogs','success','paging']
            ]);
            $this->_init_language();
            return ;
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
    
    public function view()
    {
        if ($this->request->is('post')) {
            if (!empty($this->request->data['blogSlug'])) {
                I18n::locale($this->request->session()->read('Config.language'));
                $blogSlug = $this->request->data['blogSlug'];
                $exists = $this->Blogs->exists(['Blogs.slug' => $blogSlug]);
                if ($exists) {
                    $blogDetail = $this->Blogs->find()
                                ->where(['slug' => $blogSlug,'status'=>STATUS_ACTIVE])
                                ->first()->toArray();
                    
                    $this->set([
                        'success' => true,
                        'blogDetail' => $blogDetail,
                        '_serialize' => ['blogDetail','success']
                    ]);
                    $this->_init_language();
                    return ;
                } else {
                    $errors = ['blogSlug'=>['_required'=>__('blogSlugInvalid')]];
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
                $errors = ['blogSlug'=>['_required'=>__('blogSlugIsRequired')]];
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
    
    public function comments()
    {
        if ($this->request->is('post')) {
            if (!empty($this->request->data['blogSlug'])) {
                $this->loadModel('BlogComments');
                $blogSlug = $this->request->data['blogSlug'];
                $conditions = array();
                if (!empty($blogSlug)) {
                    $exists = $this->Blogs->exists(['Blogs.slug' => $blogSlug,'Blogs.status'=>STATUS_ACTIVE]);
                    if ($exists) {
                        $BlogDetail = $this->Blogs->find()
                                ->where(['Blogs.slug' => $blogSlug,'Blogs.status'=>STATUS_ACTIVE])
                                ->first();
                        $blogId = $BlogDetail->id;
                        $conditions[] = ['BlogComments.blog_id'=>$blogId];
                    }
                }
            
                
                $this->BlogComments->belongsTo('Users', [
                    'className' => 'Users',
                    'foreignKey'=>'user_id'
                ]);
                
                $this->paginate = [
                    'limit' => FRONT_PAGE_LIMIT,
                    'conditions' => $conditions,
                    'order' => [
                        'BlogComments.created' => 'DESC'
                    ],
                    'contain'=>[
                        'Users'
                    ]
                ];

                $comments = $this->paginate($this->BlogComments);
                $this->set([
                    'success' => true,
                    'comments' => $comments,
                    'paging'=>['page'=>$this->request->params['paging']['BlogComments']['page'],
                                    'current'=>$this->request->params['paging']['BlogComments']['current'],
                                    'pageCount'=>$this->request->params['paging']['BlogComments']['pageCount'],
                                    'current'=>$this->request->params['paging']['BlogComments']['page'],
                                    'nextPage'=>$this->request->params['paging']['BlogComments']['nextPage'],
                                    'prevPage'=>$this->request->params['paging']['BlogComments']['prevPage'],
                                    'count'=>$this->request->params['paging']['BlogComments']['count'],
                                    'perPage'=>$this->request->params['paging']['BlogComments']['perPage']
                                ],
                    '_serialize' => ['comments','success','paging']
                ]);
                
                return ;
            } else {
                $errors = ['blogSlug'=>['_required'=>__('blogSlugIsRequired')]];
                $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' =>__('1validationErrorsOccured'),
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
	
    public function postBlogComment()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $getUser = $this->Auth->identify();
                $this->loadModel('BlogComments');
                $userId = $getUser['id'];
                $this->request->data['user_id'] = $userId;
                $blogId = $this->request->data['blog_id'];
                $blogDetail = $this->Blogs->find()
                            ->where(['id' => $blogId])
                            ->first();
                
                
                
                $BlogComments = $this->BlogComments->newEntity();
                $comment = $this->BlogComments->patchEntity($BlogComments, $this->request->data, ['validate' => 'front']);
            
                if ($this->BlogComments->save($comment)) {
                    $this->set([
                                    'success' => true,
                                    'data' => [
                                        'blogSlug' =>$blogDetail['slug'],
                                        'message' =>__('blogCommentPostSuccessfuly'),
                                    ],
                                    '_serialize' => ['data','success']
                                ]);
                    return ;
                } else {
                    if ($comment->errors()) {
                        $this->set([
                        'success' => false,
                        'data' => [
                        'blogSlug' =>$blogDetail['slug'],
                        'code' => 422,
                        'url' => h($this->request->here()),
                        'message' => count($comment->errors()).__('validationErrorsOccured'),
                        'error' => '',
                        'errorCount' => count($comment->errors()),
                        'errors' => $comment->errors(),
                        ],
                    '_serialize' => ['success', 'data']]);
                        return ;
                    }
                }
            } else {
                throw new UnauthorizedException();
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

    /**
     * Comment Delete the job
    */
    public function commentDelete()
    {
        if (isset($this->request->data['blogCommentId'])) {
            $checkAuth = $this->check_user_authrization();
            
            if ($checkAuth) {
                $getUser = $this->Auth->identify();
               
                $blogCommentTable = TableRegistry::get('BlogComments');
                $blogTable = TableRegistry::get('Blogs');
                if (!empty($this->request->data['blogCommentId'])) {
                    $id = $this->request->data['blogCommentId'];
                    $blogId = $this->request->data['blogId'];
                    
                    $blogDetail = $blogTable->find()
                            ->where(['id' => $blogId])
                            ->first();
                    
                    $user = $this->Auth->identify();
                    $userID = $user['id'];
                    $exists = $blogCommentTable->exists(['id' => $id,'user_id' => $userID]);
                    if ($exists) {
                        $blogCommentsData = $blogCommentTable->get($id);
                        if ($blogCommentTable->delete($blogCommentsData)) {
                            $this->set([
                                'success' => true,
                                'data' => [
                                    'message' =>__("commentDelete"),
                                    'blogSlug' =>$blogDetail['slug']
                                ],
                                '_serialize' => ['data','success']
                            ]);
                        }
                    } else {
                        $this->set([
                            'success' => false,
                            'data' => [
                                'code' => 422,
                                'url' => h($this->request->here()),
                                'message' =>__('invalidAccess'),
                                'blogSlug' =>$blogDetail['slug'],
                                'error' => '',
                                'errorCount' => 1,
                                'errors' => __('invalidAccess'),
                                ],
                            '_serialize' => ['success', 'data']]);
                        return ;
                    }
                } else {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' =>__('1validationErrorsOccured'),
                            'blogSlug' =>$blogDetail['slug'],
                            'error' => '',
                            'errorCount' => 1,
                            'errors' => __('jobIdRequired'),
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
            } else {
                throw new UnauthorizedException();
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
	
	
	public function getBlogCommentCount(){
	
		if ($this->request->is('post')) {
		
			$blog_id = $this->request->data['blogId'];
			$blogCommentsData = TableRegistry::get('BlogComments');
			$queryblogComment = $blogCommentsData->find()->where(['blog_id'=>$blog_id]);
			$blogCommentRowCount = $queryblogComment->count();			
			
			$this->set([				
				'blogCommentCount' => $blogCommentRowCount,
				'_serialize' => ['blogCommentCount','success']
			]);
			return ;		

		}		
	}
}
