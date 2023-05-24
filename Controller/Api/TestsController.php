<?php
namespace App\Controller\Api;

use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Network\Exception\MethodNotAllowedException;
use Cake\Utility\Security;
use Cake\Datasource\ConnectionManager;
use Cake\Auth\DefaultPasswordHasher;
/**
 * WebServices Controller
 * 
 */
class TestsController extends AppController
{
		
	public function initialize()
    {
        parent::initialize();
		//$this->Auth->allow(['roles','test_request']);
    }
	
	public function categories(){
		
		if($this->request->is('get')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth)
			{
				$categories = TableRegistry::get('Categories');
				$query = $categories->find('threaded',['fields'=>['id','name','parent_id']]);
				$results = $query->toArray();
				$this->set([
					'success' => true,
					'data' => $results,
					'_serialize' => ['data','success']
				]);
			}
			else
			{
				
				throw new UnauthorizedException();
			}
		}
		else
		{
			throw new MethodNotAllowedException();
			
		}
		
	}
	
	//http://35.156.9.103/api/web_services/blogs?page=1
	public function blogs(){
		
		if($this->request->is('get')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$this->loadModel('Blogs');	
				$this->paginate = [
					'fields' => [
						'id', 'title','description','short_description','category_id','created'
					],
					'conditions'=>['Blogs.status'=>1],
					'limit'=>APIPageLimit,
					'order'=>['Blogs.created'=>'desc']
				];
				$this->set([
					'success' => true,
					'data' => $this->paginate('Blogs'),
					'pagination'=>['page_count'=>$this->request->params['paging']['Blogs']['pageCount'],
									'current_page'=>$this->request->params['paging']['Blogs']['page'],
									'has_next_page'=>$this->request->params['paging']['Blogs']['nextPage'],
									'has_prev_page'=>$this->request->params['paging']['Blogs']['prevPage'],
									'count'=>$this->request->params['paging']['Blogs']['count'],
									'limit'=>APIPageLimit,
								],
					'_serialize' => ['data','success','pagination']
				]);
			}
			else
			{
				
				throw new UnauthorizedException();
			}
			
		}
		else
		{
			throw new MethodNotAllowedException();
			
		}
	}
	
	private function check_user_authrization()
	{
		
		if(!empty($this->request->header('Authorization')) && !empty($this->request->header('userId'))){
		
			$token =  str_replace("Bearer ","",$this->request->header('Authorization'));
			$userID = base64_decode($this->request->header('userId'));
			
			$articles = TableRegistry::get('Users');

			// Start a new query.
			$query = $articles->find()
			->where(['id' => $userID, 'token'=>$token]);
			
			$row = $query->count();
			return $row;
		}
		else
		{
			return 0;
		}
		
	}
	
	public function test(){
	
	echo 'kamaljadoun';die;
	}
	
	
	
	
	
}
