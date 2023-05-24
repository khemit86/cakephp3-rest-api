<?php
namespace App\Controller;

use Cake\ORM\TableRegistry;
use Cake\View\Helper\TextHelper;
use Cake\I18n\I18n;

class RestapisController extends AppController
{
    public $helpers = ['MetaTag'];
    public function initialize()
    {
        parent::initialize();
        $this->Auth->allow(['index']);
        $this->loadComponent('Flash'); // Include the FlashComponent
    }

    /**
     * Index method
     *
     * @return \Cake\Network\Response|null
     */
    public function index($id = null)
    {
        $this->viewBuilder()->layout('lay_restapi');
        $apiQuery = $this->Restapis->find('all', ['conditions'=>['status'=>STATUS_ACTIVE],'order'=>['title'=>'ASC']]);
        $this->set('left_nav', $apiQuery);
        
        if (isset($id) && !empty($id)) {
            $details = $this->Restapis->get($id);
            $this->set('details', $details);
        }
    }
    
    /**
     * View method
     *
     * @param string|null $id Category id.
     * @return \Cake\Network\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
    */
    public function view($id = null)
    {
        if (isset($this->request->params['pass'][0]) && !empty($this->request->params['pass'][0])) {
            $blogIndex = $this->hashids()->decode($this->request->params['pass'][0]);
            if (!empty($blogIndex)) {
                $id = $blogIndex[0];
            } else {
                throw new NotFoundException();
            }
        }
        if (!$id) {
            throw new \Cake\Network\Exception\NotFoundException(__('Id is not valid!'));
        }
    
        if (!empty($id)) {
            $exists = $this->Blogs->exists(['Blogs.id' => $id]);
            if (!$exists) {
                throw new \Cake\Network\Exception\NotFoundException(__('Id is not exists!'));
            }
        }
        
        $this->viewBuilder()->layout('lay_home');
        $this->set('title_for_layout', __('blog'));
        $this->loadModel('Categories');
        
        $conditions[] = ['Blogs.status'=>STATUS_ACTIVE,'Blogs.id' => $id];
        I18n::locale($this->request->session()->read('Config.language'));
        $blog = $this->Blogs->find('all', [
                'conditions' => $conditions,
                'limit' => 1
        ])->first();
        $categoryDetail = array();
        
        if (!empty($blog->category_id)) {
            I18n::locale($this->request->session()->read('Config.language'));
            $categoryDetail = $this->Categories->get($blog->category_id);
        }
        if ($this->request->session()->read('Config.language')=='en') {
            $this->set('title_for_layout', ucwords($blog->title));
        } else {
            $this->set('title_for_layout', ucwords($blog->translation($this->request->session()->read('Config.language'))->title));
        }
        
      /** get the parent categories data start **/
        $this->loadModel('BlogComments');
        $this->BlogComments->belongsTo('Users', [
            'className' => 'Users',
            'foreignKey'=>'user_id'
        ]);
        
        $conditionss[] = ['BlogComments.blog_id'=>$id];
        $comments = $this->BlogComments
        ->find('all', ['conditions' => $conditionss])
        ->contain(['Users'=>['fields'=>['Users.id','Users.profile_image','Users.username']]]);
        
        $categoryQuery = $this->Categories->find('translations', ['conditions'=>['parent_id'=>0,'status'=>STATUS_ACTIVE],'order'=>['name'=>'ASC']]);
        $categories = $categoryQuery->toArray();
        /** get the parent categories data end **/
        $this->set('hashids', $this->hashids());
        $user = $this->request->session()->read('Auth.User');
        $this->set('blogcomment', "");
        $this->set(compact('user', 'comments', 'blog', 'categories', 'categoryDetail'));
        $this->_init_language();
    }
    
    public function comment($id = null)
    {
        $this->viewBuilder()->layout('lay_home');
        $this->loadModel('BlogComments');
        if (isset($this->request->params['pass'][0]) && !empty($this->request->params['pass'][0])) {
            $blogIndex = $this->hashids()->decode($this->request->params['pass'][0]);
            if (!empty($blogIndex)) {
                $id = $blogIndex[0];
            } else {
                throw new NotFoundException();
            }
        }
        
        $blogcomment = $this->BlogComments->newEntity();
        $userId = $this->request->session()->read('Auth.User.id');
        
        if (isset($this->request->data['comment_id']) && !empty($this->request->data['comment_id'])) {
            if (isset($this->request->params['pass'][0]) && !empty($this->request->params['pass'][0])) {
                $blogIndex = $this->hashids()->decode($this->request->params['pass'][0]);
                if (!empty($blogIndex)) {
                    $ids = $blogIndex[0];
                } else {
                    throw new NotFoundException();
                }
            }
        
            if (isset($this->request->data['comment_id']) && !empty($this->request->data['comment_id'])) {
                $blogIndex = $this->hashids()->decode($this->request->data['comment_id']);
                if (!empty($blogIndex)) {
                    $comment_id = $blogIndex[0];
                } else {
                    throw new NotFoundException();
                }
            }
            $userId = $this->Auth->user("id");
            
        
            $blogComm = $this->BlogComments->get($comment_id);
            
            if ($this->BlogComments->delete($blogComm)) {
                $this->BlogComments->belongsTo('Users', [
                'className' => 'Users',
                'foreignKey'=>'user_id'
                ]);
                
                $conditionss[] = ['BlogComments.blog_id'=>$ids];
                $comments = $this->BlogComments
                ->find('all', ['conditions' => $conditionss])
                ->contain(['Users'=>['fields'=>['Users.id','Users.profile_image','Users.username']]]);
                $this->set('hashids', $this->hashids());
                $this->set('comments', $comments);
                if ($this->request->is('ajax')) {
                    $this->viewBuilder()->layout('ajax');
                    $this->render('/Element/Front/Blog/ele_blog_comment');
                }
            }
        }
        if ($this->request->is('post')) {
            $this->request->data['user_id'] = $userId;
            $this->request->data['blog_id'] = $id;
            $blogcomment = $this->BlogComments->patchEntity($blogcomment, $this->request->data, ['validate' => 'front']);
            if ($this->BlogComments->save($blogcomment)) {
                $this->loadModel('BlogComments');
                $this->BlogComments->belongsTo('Users', [
                'className' => 'Users',
                'foreignKey'=>'user_id'
                ]);

                $conditionss[] = ['BlogComments.blog_id'=>$id];
                $comments = $this->BlogComments
                ->find('all', ['conditions' => $conditionss])
                ->contain(['Users'=>['fields'=>['Users.id','Users.profile_image','Users.username']]]);
                $this->set('hashids', $this->hashids());
                $this->set('comments', $comments);
                if ($this->request->is('ajax')) {
                    $this->viewBuilder()->layout('ajax');
                    $this->render('/Element/Front/Blog/ele_blog_comment');
                }
            }
            $this->set('blogcomment', $blogcomment);
        }
    }
    
    public function commentDelete($id = null)
    {
        if (isset($this->request->params['pass'][0]) && !empty($this->request->params['pass'][0])) {
            $blogIndex = $this->hashids()->decode($this->request->params['pass'][0]);
            if (!empty($blogIndex)) {
                $id = $blogIndex[0];
            }
        }
        
        
        if (!$id) {
            throw new \Cake\Network\Exception\NotFoundException(__('idNotValid'));
        }
        $this->loadModel('BlogComments');
        $userId = $this->Auth->user("id");
        if (!$userId) {
            throw new \Cake\Network\Exception\NotFoundException(__('Id is not valid!'));
        }
        $blogComm = $this->BlogComments->get($id);
        if ($this->BlogComments->delete($blogComm)) {
            $this->Flash->front_flash_success(__('commentDelete'));
            return $this->redirect(['action' => 'view',$this->request->params['pass'][2],'language'=>$this->request->session()->read('Config.language')]);
        }
    }
}
