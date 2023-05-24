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
class WebServicesController extends AppController
{
		
	public function initialize()
    {
        parent::initialize();
		//$this->Auth->allow(['roles','test_request']);
    }
	
	/**
     * Get the list of categories
    */
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
	
	/**
     * Get the list of blogs
    */
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
	
	/**
     * Get the list of blogs of specific categories
    */
	public function getBlogListingByCategoryId(){
		if($this->request->is('post')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				if(!empty($this->request->data['categoryId'])){
					$categoryId = $this->request->data['categoryId'];
					$this->loadModel('Blogs');
					$this->paginate = [
						'fields' => [
							'id', 'title','description','short_description','category_id','created'
						],
						'conditions'=>['Blogs.status'=>1,'Blogs.category_id'=>$categoryId],
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
					$this->response->statusCode(422);
					$errors = ['categoryId'=>['_required'=>'Caregory id is required']];
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' =>'1 validation errors occurred',
							'error' => '',
							'errorCount' => 1,
							'errors' => $errors,
							],
						'_serialize' => ['success', 'data']]);
						 return ;
					
					
				}
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
	
	/**
     * Get the list of area ranges
    */
	public function getAreaRange(){
		if($this->request->is('get')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$this->loadModel('AreaRanges');	
				$this->paginate = [
					'fields' => [
						'id', 'a_range'
					]
				];
				$areaRange = $this->paginate($this->AreaRanges);
				$this->set([
					'success' => true,
					'data' => $areaRange,
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
	
	/**
     * Get the list of budgets
    */
	public function getBudget(){
		if($this->request->is('get')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$this->loadModel('Budgets');	
				$this->paginate = [
					'fields' => [
						'id', 'amount'
					]
				];
				$budget = $this->paginate($this->Budgets);
				$this->set([
					'success' => true,
					'data' => $budget,
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
	
	/**
     * Get the list of execution times
    */
	public function getExecutionTime(){
		if($this->request->is('get')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$this->loadModel('ExecutionTimes');	
				$this->paginate = [
					'fields' => [
						'id', 'type','type_value'
					]
				];
				$executionTime = $this->paginate($this->ExecutionTimes);
				$this->set([
					'success' => true,
					'data' => $executionTime,
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
	
	/**
     * Get the list of projects
    */
	public function projectList(){
		
		if($this->request->is('get')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth)
			{
				$projects = TableRegistry::get('Projects');
				$user = $this->Auth->identify();
				$userID = $user['id'];
				$query = $projects->find()
				->where(['user_id' => $userID, 'status'=>1]);
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
	
	/**
     * Create the project
    */
	public function createProject(){
		if($this->request->is('post')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$projects = TableRegistry::get('Projects');
				$projectTable = $projects->newEntity($this->request->data);
				
				
				/* if ($projectTable->errors()) {
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($projectTable->errors()).' validation errors occurred',
							'error' => '',
							'errorCount' => count($projectTable->errors()),
							'errors' => $projectTable->errors(),
							],
						'_serialize' => ['success', 'data']]);
						 return ;
					
				} */
				$user = $this->Auth->identify();
				
				$projectTable->user_id = $user['id'];
				$projectTable->created = date('Y-m-d H:i:s');
				$projectTable->modified = date('Y-m-d H:i:s');
				if($projects->save($projectTable))
				{
					$id = $projectTable->id;
					$this->set([
						'success' => true,
						'data' => [
							'message' =>__('projectUpdateSuccessfully'),
							'id'=>$id
						],
						'_serialize' => ['data','success']
					]);
				}
				else
				{
					if ($projectTable->errors()) {
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($projectTable->errors()).__('validationErrorsOccured'),
							'error' => '',
							'errorCount' => count($projectTable->errors()),
							'errors' => $projectTable->errors(),
							],
						'_serialize' => ['success', 'data']]);
						 return ;
					
					}
				}
				
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
	
	/**
     * Update the project
    */
	public function updateProject(){
		if ($this->request->is(['post', 'put'])) {
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				if (!empty($this->request->data['id'])){
					$id = $this->request->data['id'];
					$projectTable = TableRegistry::get('Projects');
					$user = $this->Auth->identify();
					$userID = $user['id'];
					$exists = $projectTable->exists(['id' => $id,'user_id' => $userID,'status' => 1]);
					if($exists){
						$projectsData = $projectTable->get($id);
						$projectRecord = $projectTable->patchEntity($projectsData, $this->request->data);
						if ($projectTable->save($projectRecord)) {
							$this->set([
								'success' => true,
								'data' => [
									'message' =>__('projectUpdateSuccessfully'),
									'id'=>$id
								],
								'_serialize' => ['data','success']
							]);
						} 
						else 
						{
							if ($projectRecord->errors()) {
								$this->response->statusCode(422);
								$this->set([
									'success' => false,
									'data' => [
										'code' => 422,
										'url' => h($this->request->here()),
										'message' => count($projectRecord->errors()).__('validationErrorsOccured'),
										'error' => '',
										'errorCount' => count($projectRecord->errors()),
										'errors' => $projectRecord->errors(),
										],
									'_serialize' => ['success', 'data']]);
									 return ;
							
							}
						}
					}
					else
					{
						$errors = ['error'=>['_required'=>'Invalid id']];
						$this->response->statusCode(422);
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
					
				}
				else
				{
					$errors = ['error'=>['_required'=>'id is required']];
					$this->response->statusCode(422);
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
	
	/**
     * Delete the project
    */
	public function deleteProject(){
		if ($this->request->is(['delete'])) {
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				if (!empty($this->request->data['id'])){
					$id = $this->request->data['id'];
					$projectTable = TableRegistry::get('Projects');
					$user = $this->Auth->identify();
					$userID = $user['id'];
					$exists = $projectTable->exists(['id' => $id,'user_id' => $userID,'status' => 1]);
					if($exists){
					
						$jobData = TableRegistry::get('Jobs');
						$queryJob = $jobData->find()->where(['project_id' => $id]);
						$jobRowCount = $queryJob->count();
						if($jobRowCount > 0)
						{
							$errors = ['error'=>'You cannot delete the project because its contain jobs. '];
							$this->response->statusCode(422);
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
					
						$projectsData = $projectTable->get($id);
						if ($projectTable->delete($projectsData)) {
							$this->set([
								'success' => true,
								'data' => [
									'message' =>__('projectDeletedSuccessfully')
								],
								'_serialize' => ['data','success']
							]);
						}
					}
					else
					{
						$errors = ['error'=>['_required'=>'Invalid id']];
						$this->response->statusCode(422);
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
					
				}
				else
				{
					$errors = ['error'=>['_required'=>'id is required']];
					$this->response->statusCode(422);
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
	
	/**
     * Upload the CurruculumVitae
    */
	public function uploadCurruculumVitae(){
		if($this->request->is('post')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$user = $this->Auth->identify();
				$userID = $user['id'];
				
				if($user['role_id'] == USER_CLIENT_ROLE)
				{
					$errors = ['error'=>'You can not perform this because your account type is client.'];
						$this->response->statusCode(422);
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
				
				if (!empty($this->request->data['uploadFile']['tmp_name'])) {
					$attach_curruculum_vitae = $this->request->data['uploadFile'];
					$allowed	=	array('application/msword','application/pdf');// extensions are allowe
					if(!in_array($attach_curruculum_vitae["type"],$allowed)){ // check the extension of document
						$errors[] = ['extension'=>['_required'=>'Only pdf, word files allowed']];
					}
					if($attach_curruculum_vitae['size'] > 10485760){ // check the size of Curruculum Vitae
						$errors[] = ['size'=>['_required'=>'Size must be less than 10 MB']];
					}
					if(!empty($errors)){
						$this->response->statusCode(422);
								$this->set([
									'success' => false,
									'data' => [
										'code' => 422,
										'url' => h($this->request->here()),
										'message' => count($errors).__('validationErrorsOccured'),
										'error' => '',
										'errorCount' => count($errors),
										'errors' => $errors,
										],
									'_serialize' => ['success', 'data']]);
									 return ;
					}
					$temp 		= 	explode(".", $attach_curruculum_vitae["name"]);
					$extension 	= 	end($temp);
					$fileName 	= 	'curruculum_vitae_'.microtime(true).'.'.$extension;
					if (move_uploaded_file($attach_curruculum_vitae['tmp_name'], WWW_ROOT . USER_CURRUCULUM_VITAE_FULL_DIR . DS . $fileName)){
						
						$userDetailsTable = TableRegistry::get('UserCurruculumVitaes');
						// Start a new query.
						$query = $userDetailsTable->find()->where(['user_id' => $userID]);
						$row = $query->count();
						
						if($row == 0){
							$userDetail = $userDetailsTable->newEntity();
							$userDetail->attach_curruculum_vitae = $fileName;
							$userDetail->user_id = $userID;
							$userDetail->created = date('Y-m-d H:i:s');
							$userDetail->modified = date('Y-m-d H:i:s');
							$userDetailsTable->save($userDetail);
							$id = $userDetail->id;
						}
						else
						{
							$result = $query->toArray()[0];
							$id = $result->id;
							$query->update()
							->set(['attach_curruculum_vitae' => $fileName,'modified'=>date('Y-m-d H:i:s')])
							->where(['user_id' => $userID])
							->execute();
							if(file_exists(WWW_ROOT . USER_CURRUCULUM_VITAE_FULL_DIR . DS . $result->attach_curruculum_vitae))
							{
								unlink(WWW_ROOT . USER_CURRUCULUM_VITAE_FULL_DIR . DS . $result->attach_curruculum_vitae);
							}
						
						}
						$this->set([
							'success' => true,
							'data' => [
								'message' =>'Uploading Done.',
								'id'=>$id,
								'name'=>SITE_URL.USER_CURRUCULUM_VITAE_FULL_DIR.DS.$fileName
							],
							'_serialize' => ['data','success']
						]);
						
					}
					else
					{
						$errors = ['error'=>['_required'=>'Uploading error.Please try again later']];
						$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' =>.__('1validationErrorsOccured'),
									'error' => '',
									'errorCount' => 1,
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;
						
					}
				}
				else
				{
					$errors = ['error'=>['_required'=>'File is empty']];
					$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' =>.__('1validationErrorsOccured'),
									'error' => '',
									'errorCount' => 1,
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;
				}
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
	
	/**
     * Upload the Certificate
    */
	public function uploadCertificates(){
		if($this->request->is('post')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$user = $this->Auth->identify();
				$userID = $user['id'];
				
				if($user['role_id'] == USER_CLIENT_ROLE)
				{
					$errors = ['error'=>'You can not perform this because your account type is client.'];
						$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' =>.__('1validationErrorsOccured'),
									'error' => '',
									'errorCount' => 1,
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;
				}
				
				
				if (!empty($this->request->data['uploadFile']['tmp_name'])) {
					$attach_curruculum_vitae = $this->request->data['uploadFile'];
					$allowed	=	array('application/msword','application/pdf');// extensions are allowe
					if(!in_array($attach_curruculum_vitae["type"],$allowed)){ // check the extension of document
						$errors[] = ['extension'=>['_required'=>'Only pdf, word files allowed']];
					}
					if($attach_curruculum_vitae['size'] > 10485760){ // check the size of Curruculum Vitae
						$errors[] = ['size'=>['_required'=>'Size must be less than 10 MB']];
					}
					if(!empty($errors)){
						$this->response->statusCode(422);
								$this->set([
									'success' => false,
									'data' => [
										'code' => 422,
										'url' => h($this->request->here()),
										'message' => count($errors).__('validationErrorsOccured'),
										'error' => '',
										'errorCount' => count($errors),
										'errors' => $errors,
										],
									'_serialize' => ['success', 'data']]);
									 return ;
					}
					
					$temp 		= 	explode(".", $attach_curruculum_vitae["name"]);
					$extension 	= 	end($temp);
					$fileName 	= 	'certificates_'.microtime(true).'.'.$extension;
					if (move_uploaded_file($attach_curruculum_vitae['tmp_name'], WWW_ROOT . USER_CERTIFICATE_FULL_DIR . DS . $fileName)){
						
						$userDetailsTable = TableRegistry::get('UserCertificates');
						// Start a new query.
						$query = $userDetailsTable->find()->where(['user_id' => $userID]);
						$row = $query->count();
						
						if($row == 0){
							$userDetail = $userDetailsTable->newEntity();
							$userDetail->attach_certificates = $fileName;
							$userDetail->user_id = $userID;
							$userDetail->created = date('Y-m-d H:i:s');
							$userDetail->modified = date('Y-m-d H:i:s');
							$userDetailsTable->save($userDetail);
							$id = $userDetail->id;
						}
						else
						{
							$result = $query->toArray()[0];
							$id = $result->id;
							$query->update()
							->set(['attach_certificates' => $fileName,'modified'=>date('Y-m-d H:i:s')])
							->where(['user_id' => $userID])
							->execute();
							if(file_exists(WWW_ROOT . USER_CERTIFICATE_FULL_DIR . DS . $result->attach_certificates))
							{
								unlink(WWW_ROOT . USER_CERTIFICATE_FULL_DIR . DS . $result->attach_certificates);
							}
						
						}
						$this->set([
							'success' => true,
							'data' => [
								'message' =>'Uploading Done.',
								'id'=>$id,
								'name'=>SITE_URL.USER_CERTIFICATE_FULL_DIR.DS.$fileName
							],
							'_serialize' => ['data','success']
						]);
						
					}
					else
					{
						$errors = ['error'=>['_required'=>'Uploading error.Please try again later']];
						$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' =>.__('1validationErrorsOccured'),
									'error' => '',
									'errorCount' => 1,
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;
						
					}
				}
				else
				{
					$errors = ['error'=>['_required'=>'File is empty']];
					$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' =>'1 validation errors occurred',
									'error' => '',
									'errorCount' => 1,
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;
				}
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
	
	/**
     * Upload the Application
    */
	public function uploadApplication(){
		if($this->request->is('post')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$user = $this->Auth->identify();
				$userID = $user['id'];
				if($user['role_id'] == USER_CLIENT_ROLE)
				{
					$errors = ['error'=>'You can not perform this because your account type is client.'];
						$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' =>'1 validation errors occurred',
									'error' => '',
									'errorCount' => 1,
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;
				}
				if (!empty($this->request->data['uploadFile']['tmp_name'])) {
					$attach_curruculum_vitae = $this->request->data['uploadFile'];
					$allowed	=	array('application/msword','application/pdf');// extensions are allowe
					if(!in_array($attach_curruculum_vitae["type"],$allowed)){ // check the extension of document
						$errors[] = ['extension'=>['_required'=>'Only pdf, word files allowed']];
					}
					if($attach_curruculum_vitae['size'] > 10485760){ // check the size of Curruculum Vitae
						$errors[] = ['size'=>['_required'=>'Size must be less than 10 MB']];
					}
					if(!empty($errors)){
						$this->response->statusCode(422);
								$this->set([
									'success' => false,
									'data' => [
										'code' => 422,
										'url' => h($this->request->here()),
										'message' => count($errors).' validation errors occurred',
										'error' => '',
										'errorCount' => count($errors),
										'errors' => $errors,
										],
									'_serialize' => ['success', 'data']]);
									 return ;
					}
					
					$temp 		= 	explode(".", $attach_curruculum_vitae["name"]);
					$extension 	= 	end($temp);
					$fileName 	= 	'application_'.microtime(true).'.'.$extension;
					if (move_uploaded_file($attach_curruculum_vitae['tmp_name'], WWW_ROOT . USER_APPLICATION_FULL_DIR . DS . $fileName)){
						
						$userAppDetailsTable = TableRegistry::get('UserApplications');
						// Start a new query.
						$query = $userAppDetailsTable->find()->where(['user_id' => $userID]);
						$row = $query->count();
						
						if($row == 0){
							$userAppDetail = $userAppDetailsTable->newEntity();
							$userAppDetail->application = $fileName;
							$userAppDetail->user_id = $userID;
							$userAppDetail->created = date('Y-m-d H:i:s');
							$userAppDetail->modified = date('Y-m-d H:i:s');
							$userAppDetailsTable->save($userAppDetail);
							$id = $userAppDetail->id;
						}
						else
						{
							$result = $query->toArray()[0];
							$id = $result->id;
							$query->update()
							->set(['application' => $fileName,'modified'=>date('Y-m-d H:i:s')])
							->where(['user_id' => $userID])
							->execute();
							if(file_exists(WWW_ROOT . USER_APPLICATION_FULL_DIR . DS . $result->application))
							{
								unlink(WWW_ROOT . USER_APPLICATION_FULL_DIR . DS . $result->application);
							}
						
						}
						$this->set([
							'success' => true,
							'data' => [
								'message' =>'Uploading Done.',
								'id'=>$id,
								'name'=>SITE_URL.USER_APPLICATION_FULL_DIR.DS.$fileName
							],
							'_serialize' => ['data','success']
						]);
						
					}
					else
					{
						$errors = ['error'=>['_required'=>'Uploading error.Please try again later']];
						$this->response->statusCode(422);
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
				}
				else
				{
					$errors = ['error'=>['_required'=>'File is empty']];
					$this->response->statusCode(422);
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
	
	/**
     * Upload the Videos
    */
	public function uploadVideos(){
		if($this->request->is('post')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$user = $this->Auth->identify();
				$userID = $user['id'];
				if (!empty($this->request->data['uploadFile']['tmp_name'])) {
					$attach_curruculum_vitae = $this->request->data['uploadFile'];
					$allowed	=	array('video/mp4');// extensions are allowe
					if(!in_array($attach_curruculum_vitae["type"],$allowed)){ // check the extension of document
						$errors[] = ['extension'=>['_required'=>'Only mp4 files allowed']];
					}
					if($attach_curruculum_vitae['size'] > 10485760){ // check the size of Curruculum Vitae
						$errors[] = ['size'=>['_required'=>'Size must be less than 10 MB']];
					}
					if(!empty($errors)){
						$this->response->statusCode(422);
								$this->set([
									'success' => false,
									'data' => [
										'code' => 422,
										'url' => h($this->request->here()),
										'message' => count($errors).__('validationErrorsOccured'),
										'error' => '',
										'errorCount' => count($errors),
										'errors' => $errors,
										],
									'_serialize' => ['success', 'data']]);
									 return ;
					}
					
					$temp 		= 	explode(".", $attach_curruculum_vitae["name"]);
					$extension 	= 	end($temp);
					$fileName 	= 	'videos_'.microtime(true).'.'.$extension;
					if (move_uploaded_file($attach_curruculum_vitae['tmp_name'], WWW_ROOT . USER_VIDEOS_FULL_DIR . DS . $fileName)){
						
						$userVideoDetailsTable = TableRegistry::get('UserVideos');
						// Start a new query.
						$query = $userVideoDetailsTable->find()->where(['user_id' => $userID]);
						$row = $query->count();
						
						if($row == 0){
							$userVideoDetail = $userVideoDetailsTable->newEntity();
							$userVideoDetail->video = $fileName;
							$userVideoDetail->user_id = $userID;
							$userVideoDetail->created = date('Y-m-d H:i:s');
							$userVideoDetail->modified = date('Y-m-d H:i:s');
							$userVideoDetailsTable->save($userVideoDetail);
							$id = $userVideoDetail->id;
						}
						else
						{
							$result = $query->toArray()[0];
							$id = $result->id;
							$query->update()
							->set(['video' => $fileName,'modified'=>date('Y-m-d H:i:s')])
							->where(['user_id' => $userID])
							->execute();
							if(file_exists(WWW_ROOT . USER_VIDEOS_FULL_DIR . DS . $result->video))
							{
								unlink(WWW_ROOT . USER_VIDEOS_FULL_DIR . DS . $result->video);
							}
						
						}
						$this->set([
							'success' => true,
							'data' => [
								'message' =>'Uploading Done.',
								'id'=>$id,
								'name'=>SITE_URL.USER_VIDEOS_FULL_DIR.DS.$fileName
							],
							'_serialize' => ['data','success']
						]);
						
					}
					else
					{
						$errors = ['error'=>['_required'=>'Uploading error.Please try again later']];
						$this->response->statusCode(422);
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
				}
				else
				{
					$errors = ['error'=>['_required'=>'File is empty']];
					$this->response->statusCode(422);
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
	
	/**
     * Upload the BusinessSheet
    */
	public function uploadBusinessSheet(){
		if($this->request->is('post')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$user = $this->Auth->identify();
				$userID = $user['id'];
				if($user['role_id'] != USER_COMPANY_ROLE)
				{
					$errors = ['error'=>'Only company account type can perform this action'];
						$this->response->statusCode(422);
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
				if (!empty($this->request->data['uploadFile']['tmp_name'])) {
					$attach_business_sheet = $this->request->data['uploadFile'];
					$allowed	=	array('application/msword','application/pdf');// extensions are allowe
					if(!in_array($attach_business_sheet["type"],$allowed)){ // check the extension of document
						$errors[] = ['extension'=>['_required'=>'Only pdf, word files allowed']];
					}
					if($attach_business_sheet['size'] > 10485760){ // check the size of Curruculum Vitae
						$errors[] = ['size'=>['_required'=>'Size must be less than 10 MB']];
					}
					if(!empty($errors)){
						$this->response->statusCode(422);
								$this->set([
									'success' => false,
									'data' => [
										'code' => 422,
										'url' => h($this->request->here()),
										'message' => count($errors).' validation errors occurred',
										'error' => '',
										'errorCount' => count($errors),
										'errors' => $errors,
										],
									'_serialize' => ['success', 'data']]);
									 return ;
					}
					$temp 		= 	explode(".", $attach_business_sheet["name"]);
					$extension 	= 	end($temp);
					$fileName 	= 	'business_sheet_'.microtime(true).'.'.$extension;
					if (move_uploaded_file($attach_business_sheet['tmp_name'], WWW_ROOT . USER_BUSINESS_SHEET_FULL_DIR . DS .$fileName)){
						
						$userDetailsTable = TableRegistry::get('UserBusinessSheets');
						// Start a new query.
						$query = $userDetailsTable->find()->where(['user_id' => $userID]);
						$row = $query->count();
						
						if($row == 0){
							$userDetail = $userDetailsTable->newEntity();
							$userDetail->attach_business_sheet = $fileName;
							$userDetail->user_id = $userID;
							$userDetail->created = date('Y-m-d H:i:s');
							$userDetail->modified = date('Y-m-d H:i:s');
							$userDetailsTable->save($userDetail);
							$id = $userDetail->id;
						}
						else
						{
							$result = $query->toArray()[0];
							$id = $result->id;
							$query->update()
							->set(['attach_business_sheet' => $fileName,'modified'=>date('Y-m-d H:i:s')])
							->where(['user_id' => $userID])
							->execute();
							if(file_exists(WWW_ROOT . USER_BUSINESS_SHEET_FULL_DIR . DS .$result->attach_business_sheet))
							{
								unlink(WWW_ROOT . USER_BUSINESS_SHEET_FULL_DIR . DS .$result->attach_business_sheet);
							}
						
						}
						$this->set([
							'success' => true,
							'data' => [
								'message' =>'Uploading Done.',
								'id'=>$id,
								'name'=>SITE_URL.USER_BUSINESS_SHEET_FULL_DIR.DS.$fileName
							],
							'_serialize' => ['data','success']
						]);
						
					}
					else
					{
						$errors = ['error'=>['_required'=>'Uploading error.Please try again later']];
						$this->response->statusCode(422);
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
				}
				else
				{
					$errors = ['error'=>['_required'=>'File is empty']];
					$this->response->statusCode(422);
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
	
	/**
     * Get the list of workers
    */
	public function workersList(){
		
		if($this->request->is('get')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth)
			{
				$workers = TableRegistry::get('Workers');
				$user = $this->Auth->identify();
				if($user['role_id'] == USER_CLIENT_ROLE || $user['role_id'] == USER_WORKER_ROLE)
				{
					$errors = ['error'=>'You are not authorized for perform this action.For this your account type should be either company or proexpert'];
						$this->response->statusCode(422);
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
				$userID = $user['id'];
				$query = $workers->find()
				->where(['user_id' => $userID, 'status'=>1]);
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
	
	/**
     * Create the worker
    */
	public function createWorker(){
		if($this->request->is('post')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$workers = TableRegistry::get('Workers');
				$workersTable = $workers->newEntity($this->request->data,['validate' => 'api']);
				
				if ($workersTable->errors()) {
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($workersTable->errors()).' validation errors occurred',
							'error' => '',
							'errorCount' => count($workersTable->errors()),
							'errors' => $workersTable->errors(),
							],
						'_serialize' => ['success', 'data']]);
						 return ;
					
				}
				
				$user = $this->Auth->identify();
				if($user['role_id'] == USER_CLIENT_ROLE || $user['role_id'] == USER_WORKER_ROLE)
				{
					$errors = ['error'=>'You are not authorized for perform this action.For this your account type should be either company or proexpert'];
						$this->response->statusCode(422);
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
				
				$workersTable->user_id = $user['id'];
				if($workers->save($workersTable))
				{
					$id = $workersTable->id;
					$this->set([
						'success' => true,
						'data' => [
							'message' =>'Workers add successfully.',
							'id'=>$id
						],
						'_serialize' => ['data','success']
					]);
				}
				else
				{
					
					if ($workersTable->errors()) {
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($workersTable->errors()).' validation errors occurred',
							'error' => '',
							'errorCount' => count($workersTable->errors()),
							'errors' => $workersTable->errors(),
							],
						'_serialize' => ['success', 'data']]);
						 return ;
					
					}
					
					
					
				}
				
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
	
	/**
     * Update the worker
    */
	public function updateWokers(){
		if ($this->request->is(['post', 'put'])) {
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				if (!empty($this->request->data['id'])){
					$id = $this->request->data['id'];
					$workersTable = TableRegistry::get('Workers');
					$user = $this->Auth->identify();
					if($user['role_id'] == USER_CLIENT_ROLE || $user['role_id'] == USER_WORKER_ROLE)
					{
						$errors = ['error'=>'You are not authorized for perform this action.For this your account type should be either company or proexpert'];
							$this->response->statusCode(422);
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
					$userID = $user['id'];
					$exists = $workersTable->exists(['id' => $id,'user_id' => $userID,'status' => 1]);
					if($exists){
						$workersData = $workersTable->get($id);
						$workerRecord = $workersTable->patchEntity($workersData, $this->request->data,['validate' => 'api']);
						if ($workersTable->save($workerRecord)) {
							$this->set([
								'success' => true,
								'data' => [
									'message' =>'Workers updated successfully.',
									'id'=>$id
								],
								'_serialize' => ['data','success']
							]);
						} 
						else 
						{
							if ($workerRecord->errors()) {
								$this->response->statusCode(422);
								$this->set([
									'success' => false,
									'data' => [
										'code' => 422,
										'url' => h($this->request->here()),
										'message' => count($workerRecord->errors()).' validation errors occurred',
										'error' => '',
										'errorCount' => count($workerRecord->errors()),
										'errors' => $workerRecord->errors(),
										],
									'_serialize' => ['success', 'data']]);
									 return ;
							
							}
						}
					}
					else
					{
						$errors = ['error'=>['_required'=>'id not exists']];
						$this->response->statusCode(422);
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
					
				}
				else
				{
					$errors = ['error'=>['_required'=>'id is required']];
					$this->response->statusCode(422);
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
	
	/**
     * Delete the worker
    */
	public function deleteWorkers(){
		if ($this->request->is(['delete'])) {
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				if (!empty($this->request->data['id'])){
					$id = $this->request->data['id'];
					$workerTable = TableRegistry::get('Workers');
					$user = $this->Auth->identify();
					if($user['role_id'] == USER_CLIENT_ROLE || $user['role_id'] == USER_WORKER_ROLE)
					{
						$errors = ['error'=>'You are not authorized for perform this action.For this your account type should be either company or proexpert'];
							$this->response->statusCode(422);
								$this->set([
									'success' => false,
									'data' => [
										'code' => 422,
										'url' => h($this->request->here()),
										'message' =>'1 validation errors occurred',
										'error' => '',
										'errorCount' => 1,
										'errors' => $errors,
										],
									'_serialize' => ['success', 'data']]);
									 return ;
					}
					$userID = $user['id'];
					$exists = $workerTable->exists(['id' => $id,'user_id' => $userID,'status' => 1]);
					if($exists){
						$workersData = $workerTable->get($id);
						if ($workerTable->delete($workersData)) {
							$this->set([
								'success' => true,
								'data' => [
									'message' =>'Workers deleted successfully.'
								],
								'_serialize' => ['data','success']
							]);
						}
					}
					else
					{
						$errors = ['error'=>['_required'=>'id not exists']];
						$this->response->statusCode(422);
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
					
				}
				else
				{
					$errors = ['error'=>['_required'=>'id is required']];
					$this->response->statusCode(422);
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
	
	function filter_mydate($s) {
		if (preg_match('@^(\d\d\d\d)-(\d\d)-(\d\d) (\d\d):(\d\d):(\d\d)$@', $s, $m) == false) {
			return false;
		}
		if (checkdate($m[2], $m[3], $m[1]) == false || $m[4] >= 24 || $m[5] >= 60 || $m[6] >= 60) {
			return false;
		}
		return true;
	}
	
	
	/**
     * Create the job
    */
	public function createJob(){
		if($this->request->is('post')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$user = $this->Auth->identify();
				$userID = $user['id'];
				if(!empty($this->request->data)){
					foreach ($this->request->data as $key => $value) {
						$this->request->data[$key] = trim($value);
					}
				}
				if(isset($this->request->data['project_id']) && empty($this->request->data['project_id'])){
					//$errors[] = ['error'=>['_required'=>'Project is required.']];
					$this->request->data['project_id'] = 0;
				}
				if(isset($this->request->data['budget_id']) && empty($this->request->data['budget_id'])){
					//$errors[] = ['error'=>['_required'=>'Project is required.']];
					$this->request->data['budget_id'] = 0;
				}
				if(isset($this->request->data['job_type']) && empty($this->request->data['job_type'])){
					//$errors[] = ['error'=>['_required'=>'Project is required.']];
					$this->request->data['job_type'] = 0;
				}
				if(!empty($errors)){
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($errors).__('validationErrorsOccured'),
							'error' => '',
							'errorCount' => count($errors),
							'errors' => $errors,
							],
						'_serialize' => ['success', 'data']]);
						 return ;
				}
				$jobs = TableRegistry::get('Jobs');
				$jobsTable = $jobs->newEntity($this->request->data,['validate' => 'api']);
				
				if ($jobsTable->errors()) {
					
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($jobsTable->errors()).' validation errors occurred',
							'error' => '',
							'errorCount' => count($jobsTable->errors()),
							'errors' => $jobsTable->errors(),
							],
						'_serialize' => ['success', 'data']]);
						 return ;
					
				}
				else
				{
				
					if(!empty($this->request->data['end_date'])){
					
						if( $this->filter_mydate($this->request->data['end_date'])){
						   $current_date = time();
						   $end_date = strtotime($this->request->data['end_date']);
						   if($end_date < $current_date )
							{
								$errors[] = ['error'=>['_required'=>'end_date should be greater then current date.']];
							}
						  
						}else{
						  $errors[] = ['error'=>['_required'=>'end_date should be in Y-m-d H:i:s format.']];
						}
					}
					if(!empty($this->request->data['job_type']))
					{
						if(!in_array($this->request->data['job_type'], array('1','2','3'))){
							
							$errors[] = ['error'=>['_required'=>'Invalid job_type. It should be 1,2 or 3.']];
							
						}
					}
					if(!empty($this->request->data['execution_time_id'])){
						$executionTimeId = $this->request->data['execution_time_id'];
						$executionTimesTable = TableRegistry::get('ExecutionTimes');
						$executionTimesExists = $executionTimesTable->exists(['id' => $executionTimeId,'status' => 1]);
						if(!$executionTimesExists){
							$errors[] = ['error'=>['_required'=>'Execution Time id not exists.']];
						}
					}
					if(!empty($this->request->data['budget_id'])){
						$budgetId = $this->request->data['budget_id'];
						$budgetTable = TableRegistry::get('Budgets');
						$budgetExists = $budgetTable->exists(['id' => $budgetId,'status' => 1]);
						if(!$budgetExists){
							$errors[] = ['error'=>['_required'=>'Budget id not exists.']];
						}
					}
					if(!empty($this->request->data['area_range_id'])){
						$areaRangeId = $this->request->data['area_range_id'];
						$areaRangesTable = TableRegistry::get('AreaRanges');
						$areaRangeExists = $areaRangesTable->exists(['id' => $areaRangeId,'status' => 1]);
						if(!$areaRangeExists){
							$errors[] = ['error'=>['_required'=>'AreaRange id not exists.']];
						}
					} 
					if(!empty($this->request->data['category_id'])){
						$categoryId = $this->request->data['category_id'];
						$categoryTable = TableRegistry::get('Categories');
						$categoryExists = $categoryTable->exists(['id' => $categoryId,'status' => 1]);
						if(!$categoryExists){
							$errors[] = ['error'=>['_required'=>'Category id not exists.']];
						}
					}
					if(!empty($this->request->data['project_id'])){
					
						$projectId = $this->request->data['project_id'];
						$projectTable = TableRegistry::get('Projects');
						$proejctExists = $projectTable->exists(['id' => $projectId,'user_id'=>$userID,'status' => 1]);
						if(!$proejctExists){
							$errors[] = ['error'=>['_required'=>'Invalid project id.']];
						}
					}
					if(!empty($errors)){
						$this->response->statusCode(422);
						$this->set([
							'success' => false,
							'data' => [
								'code' => 422,
								'url' => h($this->request->here()),
								'message' => count($errors).' validation errors occurred',
								'error' => '',
								'errorCount' => count($errors),
								'errors' => $errors,
								],
							'_serialize' => ['success', 'data']]);
							 return ;
					}	 
				}
				$user = $this->Auth->identify();
				$jobsTable->user_id = $user['id'];
				if($jobs->save($jobsTable))
				{
					$id = $jobsTable->id;
					$this->set([
						'success' => true,
						'data' => [
							'message' =>'Job add successfully.',
							'id'=>$id
						],
						'_serialize' => ['data','success']
					]);
				}
				else
				{
					
					$this->response->statusCode(422);
					$this->set([
					'success' => false,
					'data' => [
						'code' => 422,
						'url' => h($this->request->here()),
						'message' => count($jobsTable->errors()).' validation errors occurred',
						'error' => '',
						'errorCount' => count($jobsTable->errors()),
						'errors' => $jobsTable->errors(),
						],
					'_serialize' => ['success', 'data']]);
					 return ;
				}
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
	
	/**
     * Update the job
    */
	public function updateJob(){
		if($this->request->is('post')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$user = $this->Auth->identify();
				$userID = $user['id'];
				if (!empty($this->request->data['id'])){
					$id = $this->request->data['id'];
					$jobsFind = TableRegistry::get('Jobs');
					$exists = $jobsFind->exists(['id' => $id,'user_id' => $userID,'status' => 1]);
					if($exists){
						/* if(!empty($this->request->data['category_id'])){
							$categoryId = $this->request->data['category_id'];
							$categoryTable = TableRegistry::get('Categories');
							$categoryExists = $categoryTable->exists(['id' => $categoryId,'status' => 1]);
							if(!$categoryExists){
								$errors[] = ['error'=>['_required'=>'Category id not exists']];
							}
						}
						if(!empty($this->request->data['project_id'])){
							$projectId = $this->request->data['project_id'];
							$projectTable = TableRegistry::get('Projects');
							$proejctExists = $projectTable->exists(['id' => $projectId,'user_id'=>$userID,'status' => 1]);
							if(!$proejctExists){
								$errors[] = ['error'=>['_required'=>'Project id not exists']];
							}
						}
						if(!empty($this->request->data['worker_id'])){
							$workerID = $this->request->data['worker_id'];
							$workerTable = TableRegistry::get('Workers');
							$workerExists = $workerTable->exists(['id' => $workerID,'user_id'=>$userID,'status' => 1]);
							if(!$workerExists){
								$errors[] = ['error'=>['_required'=>'Worker id not exists']];
							}
						}
						if(!empty($this->request->data['executiontime_id'])){
							$executionTimeId = $this->request->data['executiontime_id'];
							$executionTimesTable = TableRegistry::get('ExecutionTimes');
							$executionTimesExists = $executionTimesTable->exists(['id' => $executionTimeId,'status' => 1]);
							if(!$executionTimesExists){
								$errors[] = ['error'=>['_required'=>'Execution Time id not exists']];
							}
						}
						if(!empty($this->request->data['budget_id'])){
							$budgetId = $this->request->data['budget_id'];
							$budgetTable = TableRegistry::get('Budgets');
							$budgetExists = $budgetTable->exists(['id' => $budgetId,'status' => 1]);
							if(!$budgetExists){
								$errors[] = ['error'=>['_required'=>'Budget id not exists']];
							}
						}
						if(!empty($this->request->data['area_range_id'])){
							$areaRangeId = $this->request->data['area_range_id'];
							$areaRangesTable = TableRegistry::get('AreaRanges');
							$areaRangeExists = $areaRangesTable->exists(['id' => $areaRangeId,'status' => 1]);
							if(!$areaRangeExists){
								$errors[] = ['error'=>['_required'=>'AreaRange id not exists']];
							}
						} */
						if(isset($this->request->data['project_id']) && empty($this->request->data['project_id'])){
							//$errors[] = ['error'=>['_required'=>'Project is required.']];
							$this->request->data['project_id'] = 0;
						}
						if(isset($this->request->data['budget_id']) && empty($this->request->data['budget_id'])){
							//$errors[] = ['error'=>['_required'=>'Project is required.']];
							$this->request->data['budget_id'] = 0;
						}
						if(isset($this->request->data['job_type']) && empty($this->request->data['job_type'])){
							//$errors[] = ['error'=>['_required'=>'Project is required.']];
							$this->request->data['job_type'] = 0;
						}
						if(!empty($errors)){
							$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' => count($errors).' validation errors occurred',
									'error' => '',
									'errorCount' => count($errors),
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;
						}
						$jobs = TableRegistry::get('Jobs');
						$jobData = $jobs->get($id);
						$jobsTable = $jobs->patchEntity($jobData,$this->request->data,['validate' => 'api']);
						if ($jobsTable->errors()) {
							$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' => count($jobsTable->errors()).' validation errors occurred',
									'error' => '',
									'errorCount' => count($jobsTable->errors()),
									'errors' => $jobsTable->errors(),
									],
								'_serialize' => ['success', 'data']]);
								 return ;
							
						}
						else
						{	
							
							if(!empty($this->request->data['job_type']))
							{
								if(!in_array($this->request->data['job_type'], array('1','2','3'))){
									
									$errors[] = ['error'=>['_required'=>'Invalid job_type. It should be 1,2 or 3.']];
									
								}
							}
							if(!empty($this->request->data['end_date'])){
								if( $this->filter_mydate($this->request->data['end_date'])){
								   $current_date = time();
								   $end_date = strtotime($this->request->data['end_date']);
								   if($end_date < $current_date )
									{
										$errors[] = ['error'=>['_required'=>'end_date should be greater then current date.']];
									}
								  
								}else{
								  $errors[] = ['error'=>['_required'=>'end_date should be in Y-m-d H:i:s format.']];
								}
							}
							if(!empty($this->request->data['execution_time_id'])){
								$executionTimeId = $this->request->data['execution_time_id'];
								$executionTimesTable = TableRegistry::get('ExecutionTimes');
								$executionTimesExists = $executionTimesTable->exists(['id' => $executionTimeId,'status' => 1]);
								if(!$executionTimesExists){
									$errors[] = ['error'=>['_required'=>'Execution Time id not exists.']];
								}
							}
							if(!empty($this->request->data['budget_id'])){
								$budgetId = $this->request->data['budget_id'];
								$budgetTable = TableRegistry::get('Budgets');
								$budgetExists = $budgetTable->exists(['id' => $budgetId,'status' => 1]);
								if(!$budgetExists){
									$errors[] = ['error'=>['_required'=>'Budget id not exists.']];
								}
							}
							if(!empty($this->request->data['area_range_id'])){
								$areaRangeId = $this->request->data['area_range_id'];
								$areaRangesTable = TableRegistry::get('AreaRanges');
								$areaRangeExists = $areaRangesTable->exists(['id' => $areaRangeId,'status' => 1]);
								if(!$areaRangeExists){
									$errors[] = ['error'=>['_required'=>'AreaRange id not exists.']];
								}
							} 
							if(!empty($this->request->data['category_id'])){
								$categoryId = $this->request->data['category_id'];
								$categoryTable = TableRegistry::get('Categories');
								$categoryExists = $categoryTable->exists(['id' => $categoryId,'status' => 1]);
								if(!$categoryExists){
									$errors[] = ['error'=>['_required'=>'Category id not exists.']];
								}
							}
							if(!empty($this->request->data['project_id'])){
							
								$projectId = $this->request->data['project_id'];
								$projectTable = TableRegistry::get('Projects');
								$proejctExists = $projectTable->exists(['id' => $projectId,'user_id'=>$userID,'status' => 1]);
								if(!$proejctExists){
									$errors[] = ['error'=>['_required'=>'Invalid project id.']];
								}
							}
							if(!empty($errors)){
								$this->response->statusCode(422);
								$this->set([
									'success' => false,
									'data' => [
										'code' => 422,
										'url' => h($this->request->here()),
										'message' => count($errors).' validation errors occurred',
										'error' => '',
										'errorCount' => count($errors),
										'errors' => $errors,
										],
									'_serialize' => ['success', 'data']]);
									 return ;
							}	 
							
						}
						$user = $this->Auth->identify();
						$jobsTable->user_id = $user['id'];
						$jobs->save($jobsTable);
						$id = $jobsTable->id;
						$this->set([
							'success' => true,
							'data' => [
								'message' =>'Job updated successfully.',
								'id'=>$id
							],
							'_serialize' => ['data','success']
						]);
					}
					else
					{
						$errors = ['error'=>['_required'=>'id not exists']];
						$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' =>'1 validation errors occurred',
									'error' => '',
									'errorCount' => 1,
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;
						
					}
				}
				else
				{
					$errors = ['error'=>['_required'=>'id is required']];
					$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' =>'1 validation errors occurred',
									'error' => '',
									'errorCount' => 1,
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;
					
				}
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
	
	/**
     * Delete the job
    */
	public function deleteJob(){
		if ($this->request->is(['delete'])) {
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				if (!empty($this->request->data['id'])){
					$id = $this->request->data['id'];
					$jobTable = TableRegistry::get('Jobs');
					$user = $this->Auth->identify();
					$userID = $user['id'];
					$exists = $jobTable->exists(['id' => $id,'user_id' => $userID,'status' => 1]);
					if($exists){
					
						$offerData = TableRegistry::get('Offers');
						$queryOffer = $offerData->find()->where(['job_id' => $id]);
						$offerRowCount = $queryOffer->count();
						if($offerRowCount > 0)
						{
							$errors = ['error'=>'You cannot delete the job because its contain offers. '];
							$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' =>'1 validation errors occurred',
									'error' => '',
									'errorCount' => 1,
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;	
						}
						$jobsData = $jobTable->get($id);
						if ($jobTable->delete($jobsData)) {
							$this->set([
								'success' => true,
								'data' => [
									'message' =>'Job deleted successfully.'
								],
								'_serialize' => ['data','success']
							]);
						}
					}
					else
					{
						$errors = ['error'=>['_required'=>'id not exists']];
						$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' =>'1 validation errors occurred',
									'error' => '',
									'errorCount' => 1,
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;
					}
					
				}
				else
				{
					$errors = ['error'=>['_required'=>'id is required']];
					$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' =>'1 validation errors occurred',
									'error' => '',
									'errorCount' => 1,
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;
				}
				
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
	
	/**
     *Get the list of jobs
    */
	public function jobsList(){
		
		if($this->request->is('get')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth)
			{
				$jobs = TableRegistry::get('Jobs');
				$user = $this->Auth->identify();
				$userID = $user['id'];
				$query = $jobs->find()
				->where(['user_id' => $userID]);
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
	
	/**
     *Send Messages for chat
    */
	public function sendMessage(){
		if($this->request->is('post')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$user = $this->Auth->identify();
				$userID = $user['id'];
				if(!empty($this->request->data)){
					foreach ($this->request->data as $key => $value) {
						$this->request->data[$key] = trim($value);
					}
				}
				if(!empty($this->request->data['receiver_id'])){
					$receiverId = $this->request->data['receiver_id'];
					$userTable = TableRegistry::get('Users');
					$userExists = $userTable->exists(['id' => $receiverId,'status' => 1]);
					if(!$userExists){
						$errors[] = ['error'=>['_required'=>'Receiver id not exists']];
					}
				}
				if(!empty($errors)){
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($errors).' validation errors occurred',
							'error' => '',
							'errorCount' => count($errors),
							'errors' => $errors,
							],
						'_serialize' => ['success', 'data']]);
						 return ;
				}
				$messages = TableRegistry::get('Messages');
				$messagesTable = $messages->newEntity($this->request->data,['validate' => 'api']);
				
				if ($messagesTable->errors()) {
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($messagesTable->errors()).' validation errors occurred',
							'error' => '',
							'errorCount' => count($messagesTable->errors()),
							'errors' => $messagesTable->errors(),
							],
						'_serialize' => ['success', 'data']]);
						 return ;
					
				}
				$messagesTable->sender_id = $user['id'];
				$messages->save($messagesTable);
				$id = $messagesTable->id;
				$this->set([
					'success' => true,
					'data' => [
						'message' =>'Message add successfully.',
						'id'=>$id
					],
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

	/**
     *Get the message list
    */
	public function messageList(){
		if($this->request->is('get')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$user = $this->Auth->identify();
				$userID = $user['id'];
				$conn = ConnectionManager::get('default');
				/* $stmt = $conn->execute('SELECT * FROM messages m WHERE not exists( SELECT 1 FROM messages m2 WHERE m2.created > m.created AND 
		( (m.sender_id = m2.sender_id AND m.receiver_id = m2.receiver_id ) OR (m.sender_id = m2.receiver_id AND m.receiver_id = m2.sender_id ))) and((m.receiver_id = '.$userID.') or m.sender_id = '.$userID.')  order by m.created desc'); */
		
		$stmt = $conn->execute('SELECT messages.* FROM (SELECT MAX(id) AS ID FROM messages WHERE '.$userID.' IN (sender_id,receiver_id) GROUP BY IF ('.$userID.' = sender_id,receiver_id,sender_id)) AS latest LEFT JOIN messages USING(ID)');
		
				$results = $stmt->fetchAll('assoc');
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
	
	/**
     *Get the message detail
    */
	public function messageDetail(){
		if($this->request->is('post')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$user = $this->Auth->identify();
				$senderId = $user['id'];
				if(!empty($this->request->data)){
					foreach ($this->request->data as $key => $value) {
						$this->request->data[$key] = trim($value);
					}
				}
				
				if(empty($this->request->data['receiver_id'])){
					$errors[] = ['error'=>['_required'=>'Receiver id required']];
				}
				if(!empty($this->request->data['receiver_id'])){
					$receiverId = $this->request->data['receiver_id'];
					$userTable = TableRegistry::get('Users');
					$userExists = $userTable->exists(['id' => $receiverId,'status' => 1]);
					if(!$userExists){
						$errors[] = ['error'=>['_required'=>'Receiver id not exists']];
					}
				}
				if(!empty($errors)){
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($errors).' validation errors occurred',
							'error' => '',
							'errorCount' => count($errors),
							'errors' => $errors,
							],
						'_serialize' => ['success', 'data']]);
						 return ;
				}
				$messages = TableRegistry::get('Messages');
				$query = $messages->find('all', [
				'conditions' => ['Messages.sender_id IN' =>[$senderId,$receiverId],'Messages.receiver_id IN' =>[$senderId,$receiverId]],
				'order' => ['Messages.created' => 'DESC']]);
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
	
	/**
     *Delete the message
    */
	public function deleteMessage(){
		if ($this->request->is(['delete'])) {
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				if(!empty($this->request->data)){
					foreach ($this->request->data as $key => $value) {
						$this->request->data[$key] = trim($value);
					}
				}
				if(empty($this->request->data['receiver_id'])){
					$errors[] = ['error'=>['_required'=>'Receiver id required']];
				}
				if(!empty($this->request->data['receiver_id'])){
					$receiverId = $this->request->data['receiver_id'];
					$userTable = TableRegistry::get('Users');
					$userExists = $userTable->exists(['id' => $receiverId,'status' => 1]);
					if(!$userExists){
						$errors[] = ['error'=>['_required'=>'Receiver id not exists']];
					}
				}
				$user = $this->Auth->identify();
				$senderId = $user['id'];
				if(!empty($senderId) && !empty($receiverId)){
					if($senderId==$receiverId){
						$errors[] = ['error'=>['_required'=>'Sender and Receiver Id Both are different']];
					}
				}
				if(!empty($errors)){
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($errors).' validation errors occurred',
							'error' => '',
							'errorCount' => count($errors),
							'errors' => $errors,
							],
						'_serialize' => ['success', 'data']]);
						 return ;
				}
				
				$messages = TableRegistry::get('Messages');		
				$queryMessasgeDetail = $messages->find()->where(['Messages.sender_id IN' =>[$senderId,$receiverId],'Messages.receiver_id IN' =>[$senderId,$receiverId]]);
				$messagedetail = $queryMessasgeDetail->toArray();
				if(!empty($messagedetail))
				{
					foreach($messagedetail as $key=>$value)
					{
						if($value->type == 2 &&  !empty($value->url))
						{
							$imageNameArray = explode(SITE_URL."uploads/chats/images/",$value->url);
							if(!empty($imageNameArray[1]) &&  file_exists(WWW_ROOT . CHAT_IMAGE_FULL_DIR . DS . $imageNameArray[1]))
							{
								unlink(WWW_ROOT . CHAT_IMAGE_FULL_DIR . DS . $imageNameArray[1]);
							}
						}
						if($value->type == 3 &&  !empty($value->url))
						{
							$documentNameArray = explode(SITE_URL."uploads/chats/documents/",$value->url);
							if(!empty($documentNameArray[1]) &&  file_exists(WWW_ROOT . CHAT_DOCUMENTS_FULL_DIR . DS . $documentNameArray[1]))
							{
								unlink(WWW_ROOT . CHAT_DOCUMENTS_FULL_DIR . DS . $documentNameArray[1]);
							}
						}
					}
					
				}
				
				if($messages->deleteAll(['Messages.sender_id IN' =>[$senderId,$receiverId],'Messages.receiver_id IN' =>[$senderId,$receiverId]])){
					$this->set([
						'success' => true,
						'data' => [
							'message' =>'Message deleted successfully.'
						],
						'_serialize' => ['data','success']
					]);
					
				}
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
	
	/**
     *Create the offer
    */
	public function createOffer(){
		if($this->request->is('post')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$user = $this->Auth->identify();
				if($user['role_id'] == USER_CLIENT_ROLE && empty($user['want_became_worker']))
				{
					$errors = ['error'=>'You can not perform this because your account type is client.'];
						$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' =>'1 validation errors occurred',
									'error' => '',
									'errorCount' => 1,
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;
				}
				if(!empty($this->request->data)){
					foreach ($this->request->data as $key => $value) {
						$this->request->data[$key] = trim($value);
					}
				}
				if(isset($this->request->data['id']))
				{
					$errors = ['error'=>'Invalid request'];
					$this->response->statusCode(422);
						$this->set([
							'success' => false,
							'data' => [
								'code' => 422,
								'url' => h($this->request->here()),
								'message' =>'1 validation errors occurred',
								'error' => '',
								'errorCount' => 1,
								'errors' => $errors,
								],
							'_serialize' => ['success', 'data']]);
							 return ;
				}
				if(!empty($this->request->data['job_id'])){
					$jobId = $this->request->data['job_id'];
					$jobTable = TableRegistry::get('Jobs');
					$jobExists = $jobTable->exists(['Jobs.id' => $jobId,'status' => 1]);
					if(!$jobExists){
						$errors[] = ['error'=>['_required'=>'You cannot create the offer for this job.']];
					}
					
					$offers = TableRegistry::get('Offers');
					$offerExists = $offers->exists(['job_id' => $jobId,'user_id' => $user['id']]);
					if($offerExists)
					{
						$errors[] = ['error'=>['_required'=>'You have already created the offer for this job.']];
					}
					$jobDetail = $jobTable->get($jobId)->toArray();
					
					if(!empty($jobDetail))
					{
						if($jobDetail['user_id'] == $user['id'])
						{
							$errors[] = ['error'=>['_required'=>'You cannot create the offer for this job because job is posted by you.']];
						}
					}
					
				}
				if(!empty($errors)){
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($errors).' validation errors occurred',
							'error' => '',
							'errorCount' => count($errors),
							'errors' => $errors,
							],
						'_serialize' => ['success', 'data']]);
						 return ;
				}
				$offers = TableRegistry::get('Offers');
				$offerTable = $offers->newEntity($this->request->data,['validate' => 'api']);
				
				if ($offerTable->errors()) {
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($offerTable->errors()).' validation errors occurred',
							'error' => '',
							'errorCount' => count($offerTable->errors()),
							'errors' => $offerTable->errors(),
							],
						'_serialize' => ['success', 'data']]);
						 return ;
					
				}
				
				$offerTable->user_id = $user['id'];
				$offerTable->price = $this->request->data['amount'];
				$offers->save($offerTable);
				$id = $offerTable->id;
				$this->set([
					'success' => true,
					'data' => [
						'message' =>'Offer added successfully.',
						'id'=>$id
					],
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
	
	/**
     *Update the offer
    */
	public function updateOffer(){
		if($this->request->is('post')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
			
				$user = $this->Auth->identify();
				if($user['role_id'] == USER_CLIENT_ROLE && empty($user['want_became_worker']))
				{
					$errors = ['error'=>'You can not perform this because your account type is client.'];
						$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' =>'1 validation errors occurred',
									'error' => '',
									'errorCount' => 1,
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;
				}
			
			
			
			
				if(!empty($this->request->data)){
					foreach ($this->request->data as $key => $value) {
						$this->request->data[$key] = trim($value);
					}
				}
				if (!empty($this->request->data['id'])){
					if(!empty($this->request->data['job_id'])){
						$jobId = $this->request->data['job_id'];
						$jobTable = TableRegistry::get('Jobs');
						$jobExists = $jobTable->exists(['Jobs.id' => $jobId,'status' => 1]);
						if(!$jobExists){
							$errors[] = ['error'=>['_required'=>'Job is not open']];
						}
					}
					$offers = TableRegistry::get('Offers');
					$id = $this->request->data['id'];
					$offerExists = $offers->exists(['id' => $id]);
					if(!$offerExists){
						$errors[] = ['error'=>['_required'=>'Offer id is invalid']];
					}
					$offerDetail = $offers->get($id)->toArray();
					
					if(!empty($offerDetail))
					{
						if($offerDetail['status'] != 1)
						{
							$errors[] = ['error'=>['_required'=>'You cannot update the offer.']];
						}
						if($offerDetail['user_id'] != $user['id'])
						{
							$errors[] = ['error'=>['_required'=>'You are invalid user for this offer.']];
						}
					}
					
					
					if(!empty($errors)){
						$this->response->statusCode(422);
						$this->set([
							'success' => false,
							'data' => [
								'code' => 422,
								'url' => h($this->request->here()),
								'message' => count($errors).' validation errors occurred',
								'error' => '',
								'errorCount' => count($errors),
								'errors' => $errors,
								],
							'_serialize' => ['success', 'data']]);
							 return ;
					}
					
					$offerTable = $offers->newEntity($this->request->data,['validate' => 'api']);
					
					if ($offerTable->errors()) {
						$this->response->statusCode(422);
						$this->set([
							'success' => false,
							'data' => [
								'code' => 422,
								'url' => h($this->request->here()),
								'message' => count($offerTable->errors()).' validation errors occurred',
								'error' => '',
								'errorCount' => count($offerTable->errors()),
								'errors' => $offerTable->errors(),
								],
							'_serialize' => ['success', 'data']]);
							 return ;
						
					}
					
					$offerTable->user_id = $user['id'];
					$offerTable->price = $this->request->data['amount'];
					$offers->save($offerTable);
					$id = $offerTable->id;
					$this->set([
						'success' => true,
						'data' => [
							'message' =>'Offer updated successfully.',
							'id'=>$id
						],
						'_serialize' => ['data','success']
					]);
				}
				else
				{
					$errors = ['error'=>['_required'=>'id is required']];
					$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' =>'1 validation errors occurred',
									'error' => '',
									'errorCount' => 1,
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;
					
				}
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
	
	/**
     *Get the offer list
    */
	public function offersList(){
		if($this->request->is('post')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				if(!empty($this->request->data)){
					foreach ($this->request->data as $key => $value) {
						$this->request->data[$key] = trim($value);
					}
				}
				if(empty($this->request->data['job_id'])){
					$errors[] = ['error'=>['_required'=>'Job id required']];
				}
				if(!empty($this->request->data['job_id'])){
					$jobId = $this->request->data['job_id'];
					$jobTable = TableRegistry::get('Jobs');
					$jobExists = $jobTable->exists(['Jobs.id' => $jobId]);
					if(!$jobExists){
						$errors[] = ['error'=>['_required'=>'Job is not exist']];
					}
				}
				if(!empty($errors)){
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($errors).' validation errors occurred',
							'error' => '',
							'errorCount' => count($errors),
							'errors' => $errors,
							],
						'_serialize' => ['success', 'data']]);
						 return ;
				}
				$offers = TableRegistry::get('Offers');
				$user = $this->Auth->identify();
				$userID = $user['id'];
				$query = $offers->find()
				->where(['job_id' => $jobId]);
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
	
	
	
	/**
     *For change the password
    */
	public function changePassword(){
		if ($this->request->is(['post', 'put'])) {
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$user = $this->Auth->identify();
				$userID = $user['id'];
				$this->loadModel('Users');
				$user = $this->Users->get($userID);
				$user = $this->Users->patchEntity($user, [
                    'old_password'  => $this->request->data['old_password'],
                    'password'      => $this->request->data['password1'],
                    'password1'     => $this->request->data['password1'],
                    'password2'     => $this->request->data['password2']
                ],
                ['validate' => 'changePassword']
				);
				
				if(empty($user['password']))
				{
					$errors = ['error'=>'You have registered your account through social media.So you cannot change your password.'];
					$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' =>'1 validation errors occurred',
									'error' => '',
									'errorCount' => 1,
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;
				}				 
				
				
				
				if ($this->Users->save($user)) {
					$this->set([
								'success' => true,
								'data' => [
									'message' =>'Password updated successfully.'
								],
								'_serialize' => ['data','success']
							]);
				} else {
				   
				   if ($user->errors()) {
						$this->response->statusCode(422);
						$this->set([
							'success' => false,
							'data' => [
								'code' => 422,
								'url' => h($this->request->here()),
								'message' => count($user->errors()).' validation errors occurred',
								'error' => '',
								'errorCount' => count($user->errors()),
								'errors' => $user->errors(),
								],
							'_serialize' => ['success', 'data']]);
							 return ;
					
					}
				   
				}
				
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
	
	/**
     *Get the review list of user
    */
	public function getReviewList(){
		
		if($this->request->is('get')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$user = $this->Auth->identify();
				$userID = $user['id'];
				$this->loadModel('JobFeedbacks');	
				$this->paginate = [
					'conditions'=>['JobFeedbacks.member_id'=>$userID],
					'limit'=>APIPageLimit,
					'order'=>['JobFeedbacks.rate'=>'desc']
				];
				$this->set([
					'success' => true,
					'data' => $this->paginate('JobFeedbacks'),
					'pagination'=>['page_count'=>$this->request->params['paging']['JobFeedbacks']['pageCount'],
									'current_page'=>$this->request->params['paging']['JobFeedbacks']['page'],
									'has_next_page'=>$this->request->params['paging']['JobFeedbacks']['nextPage'],
									'has_prev_page'=>$this->request->params['paging']['JobFeedbacks']['prevPage'],
									'count'=>$this->request->params['paging']['JobFeedbacks']['count'],
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
	
	/**
     *Give the feedback
    */
	public function feedback(){
		if ($this->request->is(['post', 'put'])) {
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$this->loadModel('Users');
				$this->loadModel('Jobs');
				$user = $this->Auth->identify();
				$userID = $user['id'];
				$jobId = $this->request->data['job_id'];
				$offerId = $this->request->data['offer_id'];
				$memberId = $this->request->data['member_id'];
				
				if(!empty($this->request->data['job_id'])){
					$jobId = $this->request->data['job_id'];
					$jobTable = TableRegistry::get('Jobs');
					$jobExists = $jobTable->exists(['Jobs.id' => $jobId]);
					if(!$jobExists){
						$errors[] = ['error'=>['_required'=>'job_id is not exist']];
					}
				}
				if(!empty($errors)){
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($errors).' validation errors occurred',
							'error' => '',
							'errorCount' => count($errors),
							'errors' => $errors,
							],
						'_serialize' => ['success', 'data']]);
						 return ;
				}
				if(!empty($this->request->data['offer_id'])){
					$offerId = $this->request->data['offer_id'];
					$offerTable = TableRegistry::get('Offers');
					$offerExists = $offerTable->exists(['Offers.id' => $jobId]);
					if(!$offerExists){
						$errors[] = ['error'=>['_required'=>'offer_id is not exist']];
					}
				}
				if(!empty($errors)){
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($errors).' validation errors occurred',
							'error' => '',
							'errorCount' => count($errors),
							'errors' => $errors,
							],
						'_serialize' => ['success', 'data']]);
						 return ;
				}
				
				$this->Jobs->hasOne('Offers', [
					'className' => 'Offers',
					'foreignKey' => 'job_id'
				]);
				$jobFeedbackTable = TableRegistry::get('JobFeedbacks');
				$jobFeedbackCheck = $jobFeedbackTable->find()
						->where(['member_id'=>$memberId,'job_id'=>$jobId,'offer_id'=>$offerId])
						->first();	
				
				
				$jobFeedback = $jobFeedbackTable->newEntity();
				$jobFeedback = $jobFeedbackTable->patchEntity($jobFeedback, $this->request->data,['validate' => 'api']);
				if(!empty($jobFeedback->errors())){
						$this->response->statusCode(422);
						$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($jobFeedback->errors()).' validation errors occurred',
							'error' => '',
							'errorCount' => count($jobFeedback->errors()),
							'errors' => $jobFeedback->errors(),
							],
						'_serialize' => ['success', 'data']]);
						 return ;
				}
				
				$jobFeedback['user_id'] = $userID;
				$jobFeedback['member_id'] = $memberId;
				$jobFeedback['job_id'] = $jobId;
				$jobFeedback['offer_id'] = $offerId;
				
				if(!empty($jobFeedbackCheck))
				{
					$errors[] = ['error'=>['_required'=>'You have already given the feedback.']];
					if(!empty($errors)){
						$this->response->statusCode(422);
						$this->set([
							'success' => false,
							'data' => [
								'code' => 422,
								'url' => h($this->request->here()),
								'message' => count($errors).' validation errors occurred',
								'error' => '',
								'errorCount' => count($errors),
								'errors' => $errors,
								],
							'_serialize' => ['success', 'data']]);
							 return ;
					}
				}
				
				
				$validationCheck = true;
				$job = $this->Jobs->get($jobId,['contain'=>['Offers'=>['conditions'=>['Offers.status'=> ACCEPTED_OFFER_STATUS]]]]);
				
				if($job->user_id == $userID &&  (isset($job->offer->user_id) && $job->offer->user_id == $memberId))
				{
					$validationCheck = false;
				}
				if($job->user_id == $memberId &&  (isset($job->offer->user_id) && $job->offer->user_id == $userID))
				{
					$validationCheck = false;
				}
				if($validationCheck)
				{
					$errors[] = ['error'=>['_required'=>'You cannnot give the feedback.Because data is not match']];
					if(!empty($errors)){
						$this->response->statusCode(422);
						$this->set([
							'success' => false,
							'data' => [
								'code' => 422,
								'url' => h($this->request->here()),
								'message' => count($errors).' validation errors occurred',
								'error' => '',
								'errorCount' => count($errors),
								'errors' => $errors,
								],
							'_serialize' => ['success', 'data']]);
							 return ;
					}
				} 
				
				if ($jobFeedbackTable->save($jobFeedback)) {
				
					//Update User rating
					//$ratingData = TableRegistry::get('JobFeedbacks');
					$ratingQuery = $jobFeedbackTable->find();
					$result = $ratingQuery->select([
						'total' => $ratingQuery->func()->avg('rating')
					])
					->where(['JobFeedbacks.member_id' => $memberId])
					->group('member_id');
					$totalPrice = $result->first();
					if(!empty($totalPrice->total)){
						$this->loadModel('Users');
						$user_data = $this->Users->get($memberId);
						
						$user_data->rating = $totalPrice->total;
						$this->Users->save($user_data);
						$memberDetail = parent::getUserDetail($memberId);
						if(!empty($memberDetail['email'])){
							
							$name = '';
							if(!empty($memberDetail['username']))
							{
								$name = $memberDetail['username'];
							}
							elseif(!empty($memberDetail['first_name']))
							{
								$name = $memberDetail['first_name'];
							}
							
							$job = $this->Jobs->get($jobId);			
							$emailData = TableRegistry::get('EmailTemplates');
							$emailDataResult = $emailData->find()->where(['slug' => 'job_rating']);
							$emailContent = $emailDataResult->first();	
							$to = $memberDetail['email'];
							$subject = $emailContent->subject;
							$mail_message_data = $emailContent->description;
							$mail_message = str_replace(array('{NAME}','{RATING}','{FEEDBACK}','{JOB_TITLE}'), array($name,$totalPrice->total,$this->request->data['message'],$job->title), $mail_message_data);				
							$from = SITE_EMAIL;					
							parent::sendEmail($from, $to, $subject, $mail_message);	
						}
					}
					
					$id = $jobFeedback->id;
					$this->set([
					'success' => true,
					'data' => [
						'message' =>'Feedback given successfully.',
						'id'=>$id
					],
					'_serialize' => ['data','success']
					]);
					
				}
				
				if(!empty($jobFeedback->errors())){
					
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($jobFeedback->errors()).' validation errors occurred',
							'error' => '',
							'errorCount' => count($jobFeedback->errors()),
							'errors' => $jobFeedback->errors(),
							],
						'_serialize' => ['success', 'data']]);
						 return ;
					
				}
				
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
	
	/**
     *Post the comment of blog
    */
	public function postComment(){
		if ($this->request->is(['post', 'put'])) {
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$this->loadModel('BlogComments');
				$user = $this->Auth->identify();
				$userID = $user['id'];
				$blogcomment = $this->BlogComments->newEntity();
				$this->request->data['user_id'] = $userID;
				$blogcomment = $this->BlogComments->patchEntity($blogcomment,$this->request->data,['validate' => 'api']);
				
				if(!empty($this->request->data['blog_id'])){
					$blogId = $this->request->data['blog_id'];
					$blogTable = TableRegistry::get('Blogs');
					$blogExists = $blogTable->exists(['Blogs.id' => $blogId]);
					if(!$blogExists){
						$errors[] = ['error'=>['_required'=>'Blog is not exist']];
					}
				}
				if(!empty($errors)){
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($errors).' validation errors occurred',
							'error' => '',
							'errorCount' => count($errors),
							'errors' => $errors,
							],
						'_serialize' => ['success', 'data']]);
						 return ;
				}
				
				
				
				if ($this->BlogComments->save($blogcomment)) {
				
					$this->set([
					'success' => true,
					'data' => [
						'message' =>'Comment given successfully.',
						'id'=>$blogcomment->id
					],
					'_serialize' => ['data','success']
					]);
				
				
				}else{
				
					if ($blogcomment->errors()) {
						$this->response->statusCode(422);
						$this->set([
							'success' => false,
							'data' => [
								'code' => 422,
								'url' => h($this->request->here()),
								'message' => count($blogcomment->errors()).' validation errors occurred',
								'error' => '',
								'errorCount' => count($blogcomment->errors()),
								'errors' => $blogcomment->errors(),
								],
							'_serialize' => ['success', 'data']]);
							 return ;
						
					}
				
				}
				
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
	
	/**
     *Get the calendar of workers
    */
	public function getCalendar(){
		if($this->request->is('post')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
			
				$user = $this->Auth->identify();
				if($user['role_id'] == USER_CLIENT_ROLE || $user['role_id'] == USER_WORKER_ROLE)
				{
					$errors = ['error'=>'You are not authorized for perform this action.For this your account type should be either company or proexpert'];
						$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' =>'1 validation errors occurred',
									'error' => '',
									'errorCount' => 1,
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;
				}
				$workerId = $this->request->data['worker_id'];
				if(!empty($workerId)){
					
					$workerTable = TableRegistry::get('Workers');
					$workerExists = $workerTable->exists(['Workers.id' => $workerId]);
					if(!$workerExists){
						$errors[] = ['error'=>['_required'=>'worker_id is not exist']];
					}
				}
				else
				{
					$errors[] = ['error'=>['_required'=>'worker_id is required']];
				}
				if(!empty($errors)){
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($errors).' validation errors occurred',
							'error' => '',
							'errorCount' => count($errors),
							'errors' => $errors,
							],
						'_serialize' => ['success', 'data']]);
						 return ;
				}
				
				$this->loadModel('Events');	
				$this->paginate = [
					'fields' => [
						'id', 'title','start','end','color'
					],
					'conditions'=>['Events.worker_id'=>$workerId],
					'limit'=>APIPageLimit,
					'order'=>['Events.id'=>'desc']
				];
				$this->set([
					'success' => true,
					'data' => $this->paginate('Events'),
					'pagination'=>['page_count'=>$this->request->params['paging']['Events']['pageCount'],
									'current_page'=>$this->request->params['paging']['Events']['page'],
									'has_next_page'=>$this->request->params['paging']['Events']['nextPage'],
									'has_prev_page'=>$this->request->params['paging']['Events']['prevPage'],
									'count'=>$this->request->params['paging']['Events']['count'],
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
	
	/**
     *Create calendar
    */
	public function addCalendar(){
		if($this->request->is('post')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
			
				$user = $this->Auth->identify();
				if($user['role_id'] == USER_CLIENT_ROLE || $user['role_id'] == USER_WORKER_ROLE)
				{
					$errors = ['error'=>'You are not authorized for perform this action.For this your account type should be either company or proexpert'];
						$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' =>'1 validation errors occurred',
									'error' => '',
									'errorCount' => 1,
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;
				}
				$events = TableRegistry::get('Events');
				$eventTable = $events->newEntity($this->request->data,['validate' => 'api']);
				
				if ($eventTable->errors()) {
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($eventTable->errors()).' validation errors occurred',
							'error' => '',
							'errorCount' => count($eventTable->errors()),
							'errors' => $eventTable->errors(),
							],
						'_serialize' => ['success', 'data']]);
						 return ;
					
				}
				
				$start = strtotime($this->request->data['start']);
				$end = strtotime($this->request->data['end']);
				if($start == $end )
				{
					$errors = ['Start date and End date should not be equal.'];
					if(!empty($errors)){
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($errors).' validation errors occurred',
							'error' => '',
							'errorCount' => count($errors),
							'errors' => $errors,
							],
						'_serialize' => ['success', 'data']]);
						 return ;
					}
				}
				elseif($end < $start )
				{
					$errors = ['Start date should not be less then End date.'];
					if(!empty($errors)){
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($errors).' validation errors occurred',
							'error' => '',
							'errorCount' => count($errors),
							'errors' => $errors,
							],
						'_serialize' => ['success', 'data']]);
						 return ;
					}
				}
				
				$user = $this->Auth->identify();
				$userID = $user['id'];
				$workerId = $this->request->data['worker_id'];
				if(!empty($workerId)){
				
				
					$workerTable = TableRegistry::get('Workers');
					$workerExists = $workerTable->exists(['Workers.user_id'=>$userID,'Workers.id' => $workerId]);
					if(!$workerExists){
						$errors[] = ['error'=>['_required'=>'Invalid Worker Id.']];
					}
					$eventNewTable = TableRegistry::get('Events');
					$eventExists = $eventNewTable->exists(['Events.worker_id'=>$workerId,'Events.start'=>$this->request->data['start'],'Events.end'=>$this->request->data['end']]);
					if($eventExists){
						$errors[] = ['error'=>'Already exists this record.'];
					}
				}
				
				if(!empty($errors)){
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($errors).' validation errors occurred',
							'error' => '',
							'errorCount' => count($errors),
							'errors' => $errors,
							],
						'_serialize' => ['success', 'data']]);
						 return ;
				}
				if($events->save($eventTable))
				{
				
					$id = $eventTable->id;
					$this->set([
						'success' => true,
						'data' => [
							'message' =>'Event add successfully.',
							'id'=>$id
						],
						'_serialize' => ['data','success']
					]);
				}
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
	
	/**
     *Update the offer
    */
	public function updateOfferStatus(){
		if($this->request->is('post','put')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				/* if(!empty($this->request->data)){
					foreach ($this->request->data as $key => $value) {
						$this->request->data[$key] = trim($value);
					}
				} */
			
				if(!isset($this->request->data['id']) && empty($this->request->data['id'])){
					$errors[] = ['error'=>['_required'=>'id is required']];
				}
				if(!isset($this->request->data['type']) && empty($this->request->data['type'])){
					$errors[] = ['error'=>['_required'=>'type is required']];
				}
				
				if(!empty($errors)){
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($errors).' validation errors occurred',
							'error' => '',
							'errorCount' => count($errors),
							'errors' => $errors,
							],
						'_serialize' => ['success', 'data']]);
						 return ;
				}
				
				$id = $this->request->data['id'];
				$type = $this->request->data['type'];
				
				$user = $this->Auth->identify();
				$offersTable = TableRegistry::get('Offers');
				$jobsTable = TableRegistry::get('Jobs');
				//$this->loadModel("Jobs");
				
				
				
				$offersTable->belongsTo('Users');	
				$offer = $offersTable->get($id,['contain'=>['Users'=>['fields'=>['Users.first_name','Users.last_name','Users.username','Users.email']]]]);
				$jobId = $offer->job_id;
				$checkJobUser = $jobsTable->exists(['user_id' => $user['id'],'id'=>$jobId]);
				if(!$checkJobUser){
					$errors[] = ['error'=>['_required'=>'your are not authorized to accept the offer']];
				}
				$offerExists = $offersTable->exists(['id' => $id]);
				if(!$offerExists){
					$errors[] = ['error'=>['_required'=>'Offer id not Open']];
				}
				if($offerExists)
				{
					if($offer->status == ACCEPTED_OFFER_STATUS && $type == "accept")
					{
						$errors[] = ['error'=>['_required'=>'offer already accepted']];
					}
					if($offer->status == DECLINED_OFFER_STATUS  &&  $type == "decline" )
					{
						$errors[] = ['error'=>['_required'=>'offer already declined']];
					}
					if($offer->status == COMPLETED_OFFER_STATUS && $type == "complete" )
					{
						$errors[] = ['error'=>['_required'=>'offer already completed']];
					
					}
				}
				if(!empty($errors)){
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($errors).' validation errors occurred',
							'error' => '',
							'errorCount' => count($errors),
							'errors' => $errors,
							],
						'_serialize' => ['success', 'data']]);
						 return ;
				}
				
				if($type == "accept")
				{
					$offer->status = ACCEPTED_OFFER_STATUS;
					$offerMessage = "Offer accepted successfully";
				}
				else if($type == "decline")
				{
					$offer->status = DECLINED_OFFER_STATUS;
					$offerMessage = "Offer declined successfully";
				}
				else if($type == "complete")
				{
					$offer->status = COMPLETED_OFFER_STATUS;
					$offerMessage = "Offer completed successfully.";
				}
				if($offersTable->save($offer))
				{
					$job = $jobsTable->get($jobId); 
					//if($type == "accept" || $type == "acceptSeries")
					if($type == "accept")
					{
						$job->status = ACCEPTED_JOB_STATUS;
						$jobsTable->save($job);
						/***** mail code for accept the offer start  *****/
						
							if(!empty($offer->user) && !empty($offer->user->email) )
							{
							
								$acceptOfferMailTo = $offer->user->email;
								$acceptOfferNameTo = "";
								if($offer->user->username)
								{
									$acceptOfferNameTo = $offer->user->username;
								}
								else if($offer->user->first_name)
								{
									$acceptOfferNameTo = $offer->user->first_name;
								}
								//echo $acceptOfferMailTo;
								/*Email accept offer start*/
									$emailData = TableRegistry::get('EmailTemplates');				
									$emailDataResult = $emailData->find()->where(['slug' => 'offer_accept']);
									$emailContent = $emailDataResult->first();

									
									$job_title = $job->title;
									$to = $acceptOfferMailTo;
									$subject = $emailContent->subject;
									$mail_message_data = $emailContent->description;
									$from = SITE_EMAIL;	

									$mail_message = str_replace(array('{NAME}','{JOB_TITLE}'), array($acceptOfferNameTo,$job_title), $mail_message_data);
									parent::sendEmail($from, $to, $subject, $mail_message);
									
								/*Email accept offer close*/
							}
						/***** mail code for accept the offer end  *****/
						
						
						
						
						/***** mail code for decline the offer start  *****/
						//pr($offer);
						$declineOfferQuery = $offersTable->find('all',['conditions'=>['Offers.id != '=>$id,'Offers.job_id'=>$jobId,'Offers.status'=>OPEN_OFFER_STATUS],'contain'=>['Users'=>['fields'=>['Users.first_name','Users.last_name','Users.username','Users.email']]]]);
						$declineOfferList = $declineOfferQuery->toArray();
						if(!empty($declineOfferList))
						{
							foreach($declineOfferList as $key=>$value)
							{
								if(!empty($value->user) && !empty($value->user->email) )
								{
									$declineOfferMailTo = $value->user->email;
									$declineOfferNameTo = "";
									if($value->user->username)
									{
										$declineOfferNameTo = $value->user->username;
									}
									else if($value->user->first_name)
									{
										$declineOfferNameTo = $value->user->first_name;
									}
									$offersTable->updateAll(['status' => DECLINED_OFFER_STATUS], ['id' => $value->id]); 
									//echo $declineOfferMailTo;
									
									/*Email decline offer start*/
										//I18n::locale($this->request->session()->read('Config.language'));		
										$emailData = TableRegistry::get('EmailTemplates');				
										$emailDataResult = $emailData->find()->where(['slug' => 'offer_decline']);
										$emailContent = $emailDataResult->first();

										
										$job_title = $job->title;
										$to = $declineOfferMailTo;
										$subject = $emailContent->subject;
										$mail_message_data = $emailContent->description;
										$from = SITE_EMAIL;	

										$mail_message = str_replace(array('{NAME}','{JOB_TITLE}'), array($declineOfferNameTo,$job_title), $mail_message_data);
										parent::sendEmail($from, $to, $subject, $mail_message);	
										//$this->_init_language();
									/*Email decline offer close*/
									
								}
							}
						}
						
						/***** mail code for decline the offer end  *****/
						
						
					}
					if($type == "decline")
					{
						
						/***** mail code for decline the offer start  *****/
						if(!empty($offer->user) && !empty($offer->user->email) )
						{
						
							$declineOfferMailTo = $offer->user->email;
							$declineOfferNameTo = "";
							if($offer->user->username)
							{
								$declineOfferNameTo = $offer->user->username;
							}
							else if($offer->user->first_name)
							{
								$declineOfferNameTo = $offer->user->first_name;
							}
							
							/*Email decline offer start*/
							//I18n::locale($this->request->session()->read('Config.language'));		
							$emailData = TableRegistry::get('EmailTemplates');				
							$emailDataResult = $emailData->find()->where(['slug' => 'offer_decline']);
							$emailContent = $emailDataResult->first();

							
							$job_title = $job->title;
							$to = $declineOfferMailTo;
							$subject = $emailContent->subject;
							$mail_message_data = $emailContent->description;
							$from = SITE_EMAIL;	

							$mail_message = str_replace(array('{NAME}','{JOB_TITLE}'), array($declineOfferNameTo,$job_title), $mail_message_data);
							parent::sendEmail($from, $to, $subject, $mail_message);
							//$this->_init_language();
							/*Email decline offer close*/
							
						}
						/***** mail code for decline the offer end  *****/
						
									
					}
					if($type == "complete")
					{
					
						$job->status = COMPLETED_JOB_STATUS;
						$jobsTable->save($job);
						/***** mail code for complete  the offer  and job start  *****/
						if(!empty($offer->user) && !empty($offer->user->email) )
						{
						
							$completeOfferMailTo = $offer->user->email;
							$completeOfferNameTo = "";
							if($offer->user->username)
							{
								$completeOfferNameTo = $offer->user->username;
							}
							else if($offer->user->first_name)
							{
								$completeOfferNameTo = $offer->user->first_name;
							}
							/*Email completed offer start*/
							//I18n::locale($this->request->session()->read('Config.language'));		
							$emailData = TableRegistry::get('EmailTemplates');				
							$emailDataResult = $emailData->find()->where(['slug' => 'job_complete']);
							$emailContent = $emailDataResult->first();

							
							$job_title = $job->title;
							$end_date = $job->end_date;
							$to = $completeOfferMailTo;
							$subject = $emailContent->subject;
							$mail_message_data = $emailContent->description;
							$from = SITE_EMAIL;	

							$mail_message = str_replace(array('{NAME}','{JOB_TITLE}','{END_DATE}'), array($completeOfferNameTo,$job_title,$end_date), $mail_message_data);
							parent::sendEmail($from, $to, $subject, $mail_message);
							//$this->_init_language();
							/*Email completed offer close*/
						}
						/***** mail code for complete  the offer  and job end  *****/
					}
					
						$this->set([
						'success' => true,
						'data' => [
							'message' =>$offerMessage
						],
						'_serialize' => ['data','success']
					]);
					
				}
			
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
	
	/**
     *Delete the calendar
    */
	public function deleteCalendar(){
		if ($this->request->is(['delete'])) {
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$user = $this->Auth->identify();
				if($user['role_id'] == USER_CLIENT_ROLE || $user['role_id'] == USER_WORKER_ROLE)
				{
					$errors = ['error'=>'You are not authorized for perform this action.For this your account type should be either company or proexpert'];
						$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' =>'1 validation errors occurred',
									'error' => '',
									'errorCount' => 1,
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;
				}
				if(!isset($this->request->data['id'])){
					$errors[] = ['error'=>['_required'=>'id is required']];
				}
				if(empty(trim($this->request->data['id']))){
					$errors[] = ['error'=>['_required'=>'id is required']];
				}
				$this->loadModel("Events");
				if(isset($this->request->data['id']) && !empty(trim($this->request->data['id'])))
				{
					$checkEvents = $this->Events->exists(['id' => $this->request->data['id']]);
					if(!$checkEvents){
						$errors[] = ['error'=>['_required'=>'record not found']];
					}
				}
				if(!empty($errors)){
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($errors).' validation errors occurred',
							'error' => '',
							'errorCount' => count($errors),
							'errors' => $errors,
							],
						'_serialize' => ['success', 'data']]);
						 return ;
				}
				
				$id = $this->request->data['id'];
				$event = $this->Events->get($id);
				
				$workerId = $event->worker_id;
				if(!empty($workerId)){
				
				
					$workerTable = TableRegistry::get('Workers');
					$user = $this->Auth->identify();
					$userID = $user['id'];
					$workerExists = $workerTable->exists(['Workers.user_id'=>$userID,'Workers.id' => $workerId]);
					if(!$workerExists){
						$errors[] = ['error'=>['_required'=>'you cannot delete scheduling for this worker']];
					}
				}
				
				if(!empty($errors)){
					$this->response->statusCode(422);
					$this->set([
						'success' => false,
						'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' => count($errors).' validation errors occurred',
							'error' => '',
							'errorCount' => count($errors),
							'errors' => $errors,
							],
						'_serialize' => ['success', 'data']]);
						 return ;
				}
				
				
				
				if ($this->Events->delete($event)){	
					
					$this->set([
						'success' => true,
						'data' => [
							'message' =>'Scheduling deleted successfully.'
						],
						'_serialize' => ['data','success']
					]);
					
				}
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
	
	/**
     *Get the payment list
    */
	public function getUserRecievedPayments(){
		if($this->request->is('get')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$user = $this->Auth->identify();
				$userId = $user['id'];
				$this->loadModel('Transactions');	
				$this->paginate = [
					'conditions'=>['Transactions.member_id'=>$userId],
					'limit'=>APIPageLimit,
					'order'=>['Transactions.created'=>'desc']
				];
				$this->set([
					'success' => true,
					'data' => $this->paginate('Transactions'),
					'pagination'=>['page_count'=>$this->request->params['paging']['Transactions']['pageCount'],
									'current_page'=>$this->request->params['paging']['Transactions']['page'],
									'has_next_page'=>$this->request->params['paging']['Transactions']['nextPage'],
									'has_prev_page'=>$this->request->params['paging']['Transactions']['prevPage'],
									'count'=>$this->request->params['paging']['Transactions']['count'],
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
	
	/**
     *Get the list of open jobs
    */
	public function openJobs(){
		if($this->request->is('get')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$conditions = array();
				$conditions[] = ['Jobs.status'=>OPEN_JOB_STATUS];
				$this->loadModel('Jobs');
				$this->Jobs->belongsTo('Categories', [
					'className' => 'Categories',
					'foreignKey'=>'category_id'
				]);
				$this->Jobs->belongsTo('Budgets', [
					'className' => 'Budgets',
					'foreignKey' => 'budget_id'
				]);
				$this->Jobs->hasOne('Offers');	
				$this->paginate = [
					'limit'=>APIPageLimit,
					'contain'=>[
						'Categories',
						'Budgets',
						'Offers'=> function ($q) {
							return $q->select(
								[
									'id',
									'total_offers' => $q->func()->count('Offers.id')
							   ])
							   ->group(['job_id']);
						}
					],
					'conditions' => $conditions,
					'order' => [
						'Jobs.title' => 'ASC'
					],
					'group'=>'Jobs.id'
				];
				
				$this->set([
					'success' => true,
					'data' => $this->paginate('Jobs'),
					'pagination'=>['page_count'=>$this->request->params['paging']['Jobs']['pageCount'],
									'current_page'=>$this->request->params['paging']['Jobs']['page'],
									'has_next_page'=>$this->request->params['paging']['Jobs']['nextPage'],
									'has_prev_page'=>$this->request->params['paging']['Jobs']['prevPage'],
									'count'=>$this->request->params['paging']['Jobs']['count'],
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
     *Get the list of awarded jobs
    */
	public function awardedJobs()
    {	
		if($this->request->is('get')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$user = $this->Auth->identify();
				$userId = $user['id'];
				$this->loadModel('Jobs');
				$conditions = array();
				$conditions[] = ['Jobs.user_id'=>$userId];
				$conditions[] = ['Jobs.status != '=>OPEN_JOB_STATUS];
				$this->Jobs->belongsTo('Categories', [
					'className' => 'Categories',
					'foreignKey'=>'category_id'
				]);
				$this->Jobs->belongsTo('Budgets', [
					'className' => 'Budgets',
					'foreignKey' => 'budget_id'
				]);
				$this->Jobs->hasOne('Offers');	
				$this->Jobs->hasOne('AwardedOffers', [
					'className' => 'Offers',
					'foreignKey'=>'job_id'
				]);
				$this->paginate = [
				   'limit' => FRONT_PAGE_LIMIT,
				   // 'limit' => 1,
					'contain'=>[
						'Categories',
						'Budgets',
						'Offers'=> function ($q) {
							return $q->select(
								[
									'id',
									'total_offers' => $q->func()->count('Offers.id')
							   ])
							   ->group(['job_id']);
						},
						'AwardedOffers'=>['conditions'=>['AwardedOffers.status != '=>OPEN_OFFER_STATUS]]
					],
					'conditions' => $conditions,
					'order' => [
						'Jobs.title' => 'ASC'
					],
					'group'=>'Jobs.id'
				];
				$this->set([
					'success' => true,
					'data' => $this->paginate('Jobs'),
					'pagination'=>['page_count'=>$this->request->params['paging']['Jobs']['pageCount'],
									'current_page'=>$this->request->params['paging']['Jobs']['page'],
									'has_next_page'=>$this->request->params['paging']['Jobs']['nextPage'],
									'has_prev_page'=>$this->request->params['paging']['Jobs']['prevPage'],
									'count'=>$this->request->params['paging']['Jobs']['count'],
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
	
	
	/**
     * awardedOffers method
     *
     * @return \Cake\Network\Response|null
     */
	public function awardedOffers()
    {	
		if($this->request->is('get')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
				$user = $this->Auth->identify();
				if($user['role_id'] == USER_CLIENT_ROLE && empty($user['want_became_worker']))
				{
					$errors = ['error'=>'You can not perform this because your account type is client.'];
						$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' =>'1 validation errors occurred',
									'error' => '',
									'errorCount' => 1,
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;
				}
				$userId = $user['id'];
				$conditions = array();
				$this->loadModel("Offers");
				$this->loadModel("Jobs");
				$conditions[] = ['Offers.user_id'=>$userId];
				$conditions[] = ['Offers.status != '=>OPEN_OFFER_STATUS];
				
				$this->Offers->belongsTo('Jobs');
				$this->Jobs->belongsTo('Users');
				$this->Jobs->belongsTo('Categories', [
					'className' => 'Categories',
					'foreignKey'=>'category_id'
				]);
				$this->Jobs->belongsTo('Budgets', [
					'className' => 'Budgets',
					'foreignKey' => 'budget_id'
				]);
				$this->Jobs->hasOne('TotalOffers', [
					'className' => 'Offers',
					'foreignKey'=>'job_id'
				]);
				
				$this->paginate = [
					'limit' => FRONT_PAGE_LIMIT,
					'conditions' => $conditions,
					'contain'=>[
						'Jobs'=>[
						'Categories',
						'Budgets',
						'Users'=>['fields'=>['Users.first_name','Users.last_name','Users.username']],
						'TotalOffers'=> function ($q) {
							return $q->select(
								[
									'id',
									'total_offers' => $q->func()->count('Offers.id')
							   ])
							   ->group(['job_id']);
							}
						]
					],
					'order' => [
						'Offers.created' => 'ASC'
					],
					'group'=>'Offers.id'
				];
				
				$this->set([
					'success' => true,
					'data' => $this->paginate('Offers'),
					'pagination'=>['page_count'=>$this->request->params['paging']['Offers']['pageCount'],
									'current_page'=>$this->request->params['paging']['Offers']['page'],
									'has_next_page'=>$this->request->params['paging']['Offers']['nextPage'],
									'has_prev_page'=>$this->request->params['paging']['Offers']['prevPage'],
									'count'=>$this->request->params['paging']['Offers']['count'],
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
	
	/**
     *Get the profile detail of user
    */
	public function viewProfileSetting(){
		
		if($this->request->is('get')){
		
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
			
				$user = $this->Auth->identify();
				$userId = $user['id'];
				$this->loadModel('Users');	
				$this->Users->hasOne('UserDetails', [
					'className' => 'UserDetails',
					'foreignKey' => 'user_id'
				]);
				$this->Users->hasOne('UserBankDetails', [
					'className' => 'UserBankDetails',
					'foreignKey' => 'user_id'
				]);
				$this->Users->belongsToMany('Categories');
				$user = $this->Users->get($userId,['contain'=>['UserDetails','UserBankDetails','Categories']]);
				$userDetail = array();
				if(!empty($user))
				{
					$userDetail['first_name'] = $user->first_name;
					$userDetail['last_name'] = $user->last_name;
					$userDetail['email'] = $user->email;
					$userDetail['username'] = $user->username;
					
					$userDetail['rating'] = $user->rating;
					$userDetail['zipcode'] = $user->zipcode;
					$userDetail['street_address'] = $user->street_address;
					
					if(!empty($user->profile_image))
					{
						$userDetail['profile_image'] = SITE_URL .USERS_FULL_DIR.'/'.$user->profile_image;
					}
					else
					{
						$userDetail['profile_image'] = "";
					}
					if(isset($user->categories) && !empty($user->categories))
					{
						$categories = array();
						foreach($user->categories as $key=>$value)
						{
							$categories[] = $value->name;
						}
						if(!empty($categories))
						{
							$userDetail['categories'] = implode(",",$categories);
						}
					}
					$userDetail['bank_name'] = "";
					if(isset($user->user_bank_detail->name) && !empty($user->user_bank_detail->name))
					{
						$userDetail['bank_name'] = $user->user_bank_detail->name;
					}
					$userDetail['bank_description'] = "";
					if(isset($user->user_bank_detail->description) && !empty($user->user_bank_detail->description))
					{
						$userDetail['bank_description'] = $user->user_bank_detail->description;
					}
					$userDetail['paypal_email'] = "";
					if(isset($user->user_bank_detail->paypal_email) && !empty($user->user_bank_detail->paypal_email))
					{
						$userDetail['paypal_email'] = $user->user_bank_detail->paypal_email;
					}
					$userDetail['iban'] = "";
					if(isset($user->user_bank_detail->iban) && !empty($user->user_bank_detail->iban))
					{
						$userDetail['iban'] = $user->user_bank_detail->iban;
					}
					$userDetail['bic'] = "";
					if(isset($user->user_bank_detail->bic) && !empty($user->user_bank_detail->bic))
					{
						$userDetail['bic'] = $user->user_bank_detail->bic;
					}
					$userDetail['hourly_rate'] = "";
					if(isset($user->user_detail->hourly_rate) && !empty($user->user_detail->hourly_rate))
					{
						$userDetail['hourly_rate'] = $user->user_detail->hourly_rate;
					}
					
					
				}
				//pr($user);
				/* pr($userDetail);
				die; */
				
				$this->set([
					'success' => true,
					'data' => $userDetail,
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
	
	/**
     *Update the profile detail of user
    */
	public function profileSettingEdit(){
		
		$this->loadModel('Users');
		if(!$this->request->is('put')){
			$checkAuth = $this->check_user_authrization();
			if($checkAuth){
			$user = $this->Auth->identify();
			$id = $user['id'];
			$this->Users->hasOne('UserDetails', [
			'className' => 'UserDetails',
			'foreignKey' => 'user_id'
			]);
			$this->Users->hasOne('UserBankDetails', [
			'className' => 'UserBankDetails',
			'foreignKey' => 'user_id'
			]);
			$this->Users->belongsToMany('Categories');
			$user = $this->Users->get($id,['contain'=>['UserDetails','UserBankDetails','Categories']]);
			
			$user_data['first_name'] = $user->first_name;
			$user_data['last_name'] = $user->last_name;
			$user_data['zipcode'] = $user->zipcode;
			$user_data['street_address'] = $user->street_address;
			$user_data['user_detail']['trade_license_type'] = $user->user_detail['trade_license_type'];
			$user_data['user_detail']['insurance_type'] = $user->user_detail['insurance_type'];
			$user_data['is_adult'] = $user->is_adult;
			$user_data['user_detail']['is_residence_germany'] = $user->user_detail['is_residence_germany'];
			$user_data['user_detail']['country'] = $user->user_detail['country'];
			$user_data['user_detail']['hourly_rate'] = $user->user_detail['hourly_rate'];
			$user_data['user_bank_detail']['cash_payment_status'] = $user->user_bank_detail['cash_payment_status'];
			$user_data['user_bank_detail']['paypal_payment_status'] = $user->user_bank_detail['paypal_payment_status'];
			$user_data['user_bank_detail']['paypal_email'] = $user->user_bank_detail['paypal_email'];
			$user_data['user_bank_detail']['iban'] = $user->user_bank_detail['iban'];
			$user_data['user_bank_detail']['bic'] = $user->user_bank_detail['bic'];
			$user_data['user_bank_detail']['name'] = $user->user_bank_detail['name'];
			$user_data['user_bank_detail']['description'] = $user->user_bank_detail['description'];
			$user_data['job_type'] = $user->job_type;
			$user_data['user_detail']['facebook_link'] = $user->user_detail['facebook_link'];
			$user_data['user_detail']['google_plus_link'] = $user->user_detail['google_plus_link'];
			$user_data['user_detail']['twitter_link'] = $user->user_detail['twitter_link'];
			$user_data['user_detail']['xing_link'] = $user->user_detail['xing_link'];
			$user_data['user_detail']['linkedin_link'] = $user->user_detail['linkedin_link'];
			$user_data['user_detail']['skype_id'] = $user->user_detail['skype_id'];
			$user_data['id'] = $user->id;
			
				if(!empty($user_data)){
					foreach ($user_data as $key => $value) {
						$this->request->data[$key] = $value;
					}	
				}
				if(!empty($this->request->data['zipcode'])){
					$zipcode = $this->request->data['zipcode'];
					if(!empty($this->request->data['street_address'])){
						$streetAddress = $this->request->data['street_address'];
						$this->getzipcode($streetAddress,$zipcode);
					}
					else
					{
						$streetAddress = "";
						$this->getzipcode($streetAddress,$zipcode);
						
					}
				}							
				if (!empty($this->request->data['user_id'])){					
					$id = $this->request->data['user_id'];
					$users = TableRegistry::get('Users');
					if(!$user){
						$errors[] = ['error'=>['_required'=>'Offer id not Open']];
					}
					if(!empty($errors)){
						$this->response->statusCode(422);
						$this->set([
							'success' => false,
							'data' => [
								'code' => 422,
								'url' => h($this->request->here()),
								'message' => count($errors).' validation errors occurred',
								'error' => '',
								'errorCount' => count($errors),
								'errors' => $errors,
								],
							'_serialize' => ['success', 'data']]);
							 return ;
					}					
					// $userTable = $users->newEntity($this->request->data,['validate' => 'api']);
					$userTable = $users->newEntity($this->request->data);
				
						if ($userTable->errors()) {
						$this->response->statusCode(422);
						$this->set([
							'success' => false,
							'data' => [
								'code' => 422,
								'url' => h($this->request->here()),
								'message' => count($offerTable->errors()).' validation errors occurred',
								'error' => '',
								'errorCount' => count($userTable->errors()),
								'errors' => $userTable->errors(),
								],
							'_serialize' => ['success', 'data']]);
							 return ;
						
					}
			
					$user = $this->Auth->identify();
					$userTable->user_id = $user['id'];
					$users->save($userTable);
					$id = $userTable->id;
					$this->set([
						'success' => true,
						'data' => [
							'message' =>'Offer updated successfully.',
							'id'=>$id
						],
						'_serialize' => ['data','success']
					]);
				}
				else
				{
					$errors = ['error'=>['_required'=>'id is required']];
					$this->response->statusCode(422);
							$this->set([
								'success' => false,
								'data' => [
									'code' => 422,
									'url' => h($this->request->here()),
									'message' =>'1 validation errors occurred',
									'error' => '',
									'errorCount' => 1,
									'errors' => $errors,
									],
								'_serialize' => ['success', 'data']]);
								 return ;
					
				}
			
			
			
			}
			else
			{
				throw new MethodNotAllowedException();
			}	
		}else
		{
			throw new MethodNotAllowedException();
		}
	}
}
