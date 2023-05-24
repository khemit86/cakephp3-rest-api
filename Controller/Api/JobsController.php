<?php
namespace App\Controller\Api;

use Cake\Event\Event;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Utility\Security;
use Firebase\JWT\JWT;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\I18n\I18n;
use Intervention\Image\ImageManager;
use Cake\Utility\Inflector;

class JobsController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->Auth->allow(['addJobAttachmentFileRemove','view', 'openJobs','add','myJobs','delete','getVehicleType','getTaskType','edit','makeOffer','paypalIpn','getOffersCount','getJobReviewList','postedJobs','addJobAttachmentFile','editJobAttachmentFile']);
        $this->loadComponent('Flash'); // Include the FlashComponent
    }
    
    /**
     *Get the list of open jobs
    */
    public function openJobs()
    {
        if ($this->request->is('get')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $getUser = $this->Auth->identify();
                if ($getUser['role_id'] == USER_CLIENT_ROLE) {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' =>__('1validationErrorsOccured'),
                            'error' => '',
                            'errorCount' => 1,
                            'errors' => __('youAreNotAuthrizedForThis'),
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
            
                $conditions = array();
                $conditions[] = ['Jobs.status'=>OPEN_JOB_STATUS];
                $this->loadModel('Jobs');
                $this->loadModel('CustomFieldValues');
                $this->Jobs->belongsTo('Categories', [
                    'className' => 'Categories',
                    'foreignKey'=>'category_id'
                ]);
				
				$this->CustomFieldValues->belongsTo('CustomFields', [
						'className' => 'CustomFields',
						'foreignKey' => 'custom_field_id'
						]);

						$this->Jobs->hasMany('CustomFieldValues', [
						'className' => 'CustomFieldValues',
						'foreignKey' => 'job_id'
						]);
        
                $this->paginate = [
                    'limit'=>APIPageLimit,
                    'contain'=>[
                        'CustomFieldValues'=>['CustomFields'],'Categories'
                    ],
                    'conditions' => $conditions,
                    'order' => [
                        'Jobs.created' => 'DESC'
                    ],
                    'group'=>'Jobs.id'
                ];
                $openJobs = $this->paginate($this->Jobs);
                $this->set([
                    'success' => true,
                    'data' => $openJobs,
                    'paging'=>['page'=>$this->request->params['paging']['Jobs']['page'],
                                'current'=>$this->request->params['paging']['Jobs']['current'],
                                'pageCount'=>$this->request->params['paging']['Jobs']['pageCount'],
                                'current'=>$this->request->params['paging']['Jobs']['page'],
                                'nextPage'=>$this->request->params['paging']['Jobs']['nextPage'],
                                'prevPage'=>$this->request->params['paging']['Jobs']['prevPage'],
                                'count'=>$this->request->params['paging']['Jobs']['count'],
                                'perPage'=>$this->request->params['paging']['Jobs']['perPage'],
                            ],
                    '_serialize' => ['data','success','paging']
                ]);
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
     *Get the job detail of user
    */
    public function viewDetail()
    {	
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            $user = $this->Auth->identify();
            
            if ($checkAuth) {
                $user = $this->Auth->identify();
                $userId = $user['id'];
                if (!empty($this->request->data['jobSlug'])) {
                    $jobSlug = $this->request->data['jobSlug'];
                    $exists = $this->Jobs->exists(['Jobs.slug' => $jobSlug]);
                
                    if ($exists) {
                        $jobDetail = $this->Jobs->find()
                            ->where(['slug' => $jobSlug])
                            ->first();
                        $id = $jobDetail->id;
						$this->loadModel('CustomFieldValues');
						
						$this->CustomFieldValues->belongsTo('CustomFields', [
							'className' => 'CustomFields',
							'foreignKey' => 'custom_field_id'
						]);			
						$this->Jobs->hasMany('JobAttachments', [
							'className' => 'JobAttachments',
							'foreignKey' => 'job_id'
						]);
						$this->Jobs->hasMany('CustomFieldValues', [
							'className' => 'CustomFieldValues',
							'foreignKey' => 'job_id'
						]);
						$this->Jobs->belongsTo('Categories', [
							'className' => 'Categories',
							'foreignKey' => 'category_id'
						]);

						$this->Jobs->hasOne('Offers', [
							'className' => 'Offers',
							'foreignKey' => 'job_id'
						]);
						$this->Jobs->hasOne('Transactions', [
							'className' => 'Transactions',
							'foreignKey' => 'job_id'
						]);
						
						$job = $this->Jobs->get($id, ['contain'=>['CustomFieldValues'=>['CustomFields'],'JobAttachments','Transactions','Offers' => function (\Cake\ORM\Query $q) {
						return $q->where(['Offers.status IN'=>[ACCEPTED_OFFER_STATUS,COMPLETED_OFFER_STATUS]]);
						},'Categories'=>['fields'=>['name']]]]);
						
						foreach($job['job_attachments'] as $value){
							$filename =  WWW_ROOT . JOB_ATTACHMENT_FILE . DS.$value['name'];
							if (file_exists($filename)) {
							$job_document_exist[] = $value['name'];
							}
						}
						unset($job['job_attachments']);
						if (isset($job_document_exist) && !empty($job_document_exist)){
						$job['job_attachments'] = $job_document_exist;						
						
						} else {
							$job['job_attachments'] = "";
						}
                        $this->set([
                            'success' => true,
                            'jobDetail' => $job,
                            '_serialize' => ['jobDetailxxx','jobDetail','success']
                        ]);
                        
                        return ;
                    } else {
                        $errors = ['jobSlug'=>['_required'=>__('jobSlugIsInvalid')]];
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
                    $errors = ['jobSlug'=>['_required'=>__('jobSlugIsInvalid')]];
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

    public function view()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            $user = $this->Auth->identify();
            if ($checkAuth) {
                $user = $this->Auth->identify();
                $userId = $user['id'];
                if (!empty($this->request->data['jobSlug'])) {
                    $jobSlug = $this->request->data['jobSlug'];
                    $exists = $this->Jobs->exists(['Jobs.slug' => $jobSlug]);
                    if ($exists) {
                        $this->loadModel('Categories');
						$this->loadModel('CustomFieldValues');
						$this->loadModel('JobAttachments');
						
						$this->Jobs->hasMany('JobAttachments', [
							'className' => 'JobAttachments',
							'foreignKey' => 'job_id'
						]);
						$this->Jobs->hasMany('CustomFieldValues', [
							'className' => 'CustomFieldValues',
							'foreignKey' => 'job_id'
						]);
                        $jobDetail = $this->Jobs->find()
                                    ->where(['slug' => $jobSlug])
                                    ->contain(['CustomFieldValues','JobAttachments'])
                                    ->first()->toArray();
									
                        $jobDetailArray = array();
                        $jobDetailArray['task_type'] = $jobDetail['task_type'];
                        $jobDetailArray['basic_supplies'] = $jobDetail['basic_supplies'];
                        $jobDetailArray['mop'] = $jobDetail['mop'];
                        $jobDetailArray['vaccum'] = $jobDetail['vaccum'];
                        $jobDetailArray['vehicle_type'] = $jobDetail['vehicle_type'];
                        $jobDetailArray['title'] = $jobDetail['title'];
                        $jobDetailArray['description'] = $jobDetail['description'];
                        $jobDetailArray['location'] = $jobDetail['location'];
                        $jobDetailArray['user_id'] = $jobDetail['user_id'];
                        $jobDetailArray['id'] = $jobDetail['id'];
                        $jobDetailArray['slug'] = $jobDetail['slug'];
                        $jobDetailArray['category_id'] = $jobDetail['category_id'];
                        $jobDetailArray['status'] = $jobDetail['status'];
                        $jobDetailArray['statusssss'] = $jobDetail['status'];
                        $jobDetailArray['end_date'] = date_format($jobDetail['end_date'], "Y-m-d");
                        $jobDetailArray['end_time'] = $jobDetail['end_time'];
                        $jobDetailArray['custom_field_values'] = $jobDetail['custom_field_values'];
                        $jobDetailArray['job_attachments'] = $jobDetail['job_attachments'];

                        
                        
                        if (!empty($jobDetail['category_id'])) {
                            $categoryDetail = $this->Categories->find()
                                    ->where(['id' => $jobDetail['category_id']])
                                    ->first()->toArray();
                                
                            $jobDetailArray['category'] = $categoryDetail['name'];
                        }
                        
                        $this->set([
                            'success' => true,
                            'jobDetail' => $jobDetailArray,
                            '_serialize' => ['jobDetail','success']
                        ]);
                        
                        return ;
                    } else {
                        $errors = ['jobSlug'=>['_required'=>__('jobSlugIsInvalid')]];
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
                    $errors = ['jobSlug'=>['_required'=>__('jobSlugIsInvalid')]];
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
    
    public function add()
    { 	
	
        if ($this->request->is('post')) {
	
                $this->loadModel('JobAttachments');
                $this->loadModel('CustomFieldValues');
                $job = $this->Jobs->newEntity();
				if(empty($this->request->data['custome_fields'])){
					$job->errors(['custom_data' => ['_empty'=>__('youAreNotAuthrizedForThis')]]);
				}
                if (empty($this->request->data['title'])) {
                    $job->errors(['title' => ['_empty'=>__('titleRequired')]]);
                } 
				if (strlen($this->request->data['title'])  < 5) {
                    $job->errors(['title' => ['_empty'=>__('titleLength')]]);
                }
                if (empty($this->request->data['description'])) {
                    $job->errors(['description' => ['_empty'=>__('DescriptionRequired')]]);
                }
                if (empty($this->request->data['category_id'])) {
                    $job->errors(['category_id' => ['_empty'=>__('categoryRequired')]]);
                } else {
                    $this->loadModel('Categories');
                    $categoryExists = $this->Categories->exists(['Categories.id' => $this->request->data['category_id']]);
                    if ($categoryExists) {
                        $categoryDetail = $this->Categories->find()
                            ->where(['id' => $this->request->data['category_id']])
                            ->first()->toArray();
                        if ($categoryDetail['parent_id'] == 0) {
                            $this->set([
                            'success' => false,
                            'data' => [
                                'code' => 422,
                                'url' => h($this->request->here()),
                                'message' =>__('1validationErrorsOccured'),
                                'error' => '',
                                'errorCount' => 1,
                                'errors' => __('youCanUseOnlyChildCategoriesForTheJob'),
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
                            'error' => '',
                            'errorCount' => 1,
                            'errors' => __('categoryNotExists'),
                            ],
                        '_serialize' => ['success', 'data']]);
                        return ;
                    }
                }
                if (empty($this->request->data['end_date']) || empty($this->request->data['end_time'])) {
                    $job->errors(['end_date' => ['_empty'=>__('EnddateRequired')]]);
                }
                if (empty($this->request->data['location'])) {
                    $job->errors(['location' => ['_empty'=>__('locationRequired')]]);
                }
                    if ($job->errors()) {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' => count($job->errors()).__('validationErrorsOccured'),
                            'error' => '',
                            'errorCount' => count($job->errors()),
                            'errors' => $job->errors(),
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
                
                ############### Image uploading code end here ###################
               
                if (!empty($this->request->data['title'])) {
                    $jobData = TableRegistry::get('Jobs');
                    $queryJobs = $jobData->find()->where(['title' => $this->request->data['title']]);
                    $jobsRowCount = $queryJobs->count();
                    
                    $slug = $this->generateSlug($this->request->data['title']);
                    $this->request->data['slug'] = strtolower($slug);
                }
		
			
			if(isset($this->request->data['selected_type']) && !empty($this->request->data['selected_type'])){
				$getUser = $this->Auth->identify();
                $userId = $getUser['id'];
					if(isset($this->request->data['selected_type']) && $this->request->data['selected_type'] == "direct-order"){
					$this->loadModel('UserDetails');
					$userExists = $this->UserDetails->exists(['UserDetails.user_id' => $this->request->data['select_userId']]);
					if ($userExists) {
						$userDetail = $this->UserDetails->find()
										->where(['user_id' => $this->request->data['select_userId']])
										->first()->toArray();
					} else {
						$this->set([
							'success' => false,
							'data' => [
							'code' => 422,
							'url' => h($this->request->here()),
							'message' =>__('1validationErrorsOccured'),
							'error' => '',
							'errorCount' => 1,
							'errors' => __('userNotExists'),
						],
							'_serialize' => ['success', 'data']]);
						return ;
					}
					$this->request->data['user_id'] = $userId;
					
					$job = $this->Jobs->patchEntity($job, $this->request->data);
						if ($this->Jobs->save($job)) {
							$job_id = $job->id;
							if(isset($job_id) && !empty($job_id)){
								$customTaskFielsData = "";
								$custom_data = json_decode($this->request->data['custome_fields']);
								if(isset($custom_data->task_type) && !empty($custom_data->task_type)){
									$task_type['value'] =  $custom_data->task_type;
									$task_type['custom_field_id'] =  $custom_data->radio_custom_field_id;
									$customTaskFielsData[] = $task_type;					
								}if(isset($custom_data->task_checkbox) && !empty($custom_data->task_checkbox)){
									$task_checkbox['value'] = $custom_data->task_checkbox;
									$task_checkbox['custom_field_id'] =  $custom_data->checkbox_custom_field_id;
									$customTaskFielsData[] = $task_checkbox;
								}if(isset($custom_data->task_textarea) && !empty($custom_data->task_textarea)){
									$task_textarea['value'] = $custom_data->task_textarea;
									$task_textarea['custom_field_id'] =  $custom_data->textarea_custom_field_id;
									$customTaskFielsData[] = $task_textarea;
								}if(isset($custom_data->task_text) && !empty($custom_data->task_text)){
									$task_text['value'] = $custom_data->task_text;
									$task_text['custom_field_id'] =  $custom_data->textbox_custom_field_id;
									$customTaskFielsData[] = $task_text;
								}
								
								
							if(isset($custom_data->task_select) && !empty($custom_data->task_select)){
								$task_selected['value'] = $custom_data->task_select;
								$task_selected['custom_field_id'] =  $custom_data->select_custom_field_id;
								$task_selected['job_id'] =  $job->id;
								$customTaskSelectFielsData = $task_selected;


								foreach($customTaskSelectFielsData['value'] as $key=>$val){
							
								$selectvaluedata['custom_field_id'] = $key;
								$selectvaluedata['value'] = $val;
								$selectvaluedata['job_id'] =  $job->id;
								$customFieldValueData = $this->CustomFieldValues->newEntity();
								$customFieldPatchData = $this->CustomFieldValues->patchEntity($customFieldValueData,$selectvaluedata);	
								$this->CustomFieldValues->save($customFieldPatchData);


								}

							}
								
								
								
								
								
								
								
								
								
								if(isset($customTaskFielsData) && !empty($customTaskFielsData)){
									foreach($customTaskFielsData as $data){					
										$data['job_id'] = $job_id;
										$customFieldValue = $this->CustomFieldValues->newEntity();	
										$customFieldData = $this->CustomFieldValues->patchEntity($customFieldValue,$data);
										$this->CustomFieldValues->save($customFieldData);
									}
								}
								
								
								
								if(isset($this->request->data['job_attachment']) && !empty($this->request->data['job_attachment'])){
									foreach($this->request->data['job_attachment'] as $data){					
										$data['job_id'] = $job_id;
										$data['name'] = $data['name'];
										$jobAttachmentValue = $this->JobAttachments->newEntity();	
										$jobAttachmentData = $this->JobAttachments->patchEntity($jobAttachmentValue,$data);
										$this->JobAttachments->save($jobAttachmentData);
										@copy (WWW_ROOT . SIN_FILE . DS . $data['name'],WWW_ROOT . JOB_ATTACHMENT_FILE . DS . $data['name']);
										if (!empty($data['name']) && file_exists(WWW_ROOT . SIN_FILE . DS . $data['name'])) {
												unlink(WWW_ROOT . SIN_FILE . DS . $data['name']);
										}
										
									}							
								}							
								
								if(isset($userDetail['hourly_rate']) && !empty($userDetail['hourly_rate'])){
									$hourly_rate = $userDetail['hourly_rate'];
								}else{
									$hourly_rate = 0;
								}
								
								
								$jobRecordUpdate['status'] = 2;
								$jobData = $this->Jobs->get($job_id);
								$jobUpdateData = $this->Jobs->patchEntity($jobData,$jobRecordUpdate);
								if ($this->Jobs->save($jobUpdateData)) {	 
								
									$offerData['user_id'] = $this->request->data['select_userId'];
									$offerData['job_id'] = $job_id;
									$offerData['description'] = '';
									$offerData['price'] = $hourly_rate;
									$offerData['status'] = 2;
									$this->loadModel('Offers');
									$offer = $this->Offers->newEntity();
									$offer = $this->Offers->patchEntity($offer,$offerData);                            
									if ($this->Offers->save($offer)) {
										$this->set([
											'success' => true,
												'data' => [
												'message' =>__('offerHasBeenSaved'),
												'job' => $job,
												'offer' => $offer
												],
											'_serialize' => ['data','success']
										]);
									} else {
										if ($offer->errors()) {
											$this->set([
													'success' => false,
													'data' => [
													'code' => 422,
													'url' => h($this->request->here()),
													'message' => count($offer->errors()).__('unableAddOffer'),
													'error' => '',
													'errorCount' => count($offer->errors()),
													'errors' => $offer->errors(),
													],
												'_serialize' => ['success', 'data']]);
											return ;
										}
									}							
									$this->set([
										'success' => true,
										'data' => [
											'message' =>__('jobHasBeenSaved'),
										],
										'_serialize' => ['data','success']
										]);
									return ;

								}else{							
									if ($job->errors()) {
										$this->set([
											'success' => false,
											'data' => [
												'code' => 422,
												'url' => h($this->request->here()),
												'message' => count($job->errors()).__('validationErrorsOccured'),
												'error' => '',
												'errorCount' => count($job->errors()),
												'errors' => $job->errors(),
											],
										'_serialize' => ['success', 'data']]);
										return ;
									}
								}
							}					   
						} else {
							if ($job->errors()) {
								$this->set([
									'success' => false,
									'data' => [
										'code' => 422,
										'url' => h($this->request->here()),
										'message' => count($job->errors()).__('validationErrorsOccured'),
										'error' => '',
										'errorCount' => count($job->errors()),
										'errors' => $job->errors(),
										],
									'_serialize' => ['success', 'data']]);
								return ;
							}
						}
					}else{

						
						
						$customTaskFielsData = "";
						$custom_data = json_decode($this->request->data['custome_fields']);
						if(isset($custom_data->task_type) && !empty($custom_data->task_type)){
							$task_type['value'] =  $custom_data->task_type;
							$task_type['custom_field_id'] =  $custom_data->radio_custom_field_id;
							$customTaskFielsData[] = $task_type;					
						}if(isset($custom_data->task_checkbox) && !empty($custom_data->task_checkbox)){
							$task_checkbox['value'] = $custom_data->task_checkbox;
							$task_checkbox['custom_field_id'] =  $custom_data->checkbox_custom_field_id;
							$customTaskFielsData[] = $task_checkbox;
						}if(isset($custom_data->task_textarea) && !empty($custom_data->task_textarea)){
							$task_textarea['value'] = $custom_data->task_textarea;
							$task_textarea['custom_field_id'] =  $custom_data->textarea_custom_field_id;
							$customTaskFielsData[] = $task_textarea;
						}if(isset($custom_data->task_text) && !empty($custom_data->task_text)){
							$task_text['value'] = $custom_data->task_text;
							$task_text['custom_field_id'] =  $custom_data->textbox_custom_field_id;
							$customTaskFielsData[] = $task_text;
						}
						
						$this->request->data['user_id'] = $userId;
						
						$job = $this->Jobs->patchEntity($job, $this->request->data);
						if ($this->Jobs->save($job)) {
						$job_id = $job->id;
					
				
						if(isset($custom_data->task_select) && !empty($custom_data->task_select)){
							$task_selected['value'] = $custom_data->task_select;
							$task_selected['custom_field_id'] =  $custom_data->select_custom_field_id;
							$task_selected['job_id'] =  $job->id;
							$customTaskSelectFielsData = $task_selected;


							foreach($customTaskSelectFielsData['value'] as $key=>$val){
						
							$selectvaluedata['custom_field_id'] = $key;
							$selectvaluedata['value'] = $val;
							$selectvaluedata['job_id'] =  $job->id;
							$customFieldValueData = $this->CustomFieldValues->newEntity();
							$customFieldPatchData = $this->CustomFieldValues->patchEntity($customFieldValueData,$selectvaluedata);	
							$this->CustomFieldValues->save($customFieldPatchData);


							}

						}
					
						
						
						if(isset($customTaskFielsData) && !empty($customTaskFielsData)){
							foreach($customTaskFielsData as $data){					
								$data['job_id'] = $job_id;
								$customFieldValue = $this->CustomFieldValues->newEntity();	
								$customFieldData = $this->CustomFieldValues->patchEntity($customFieldValue,$data);
								if ($this->CustomFieldValues->save($customFieldData)) {
									$this->Flash->admin_flash_success(__('customCategorySaved'));
								}
							}
							$this->CustomFieldValues->save($customFieldData);
						}
						
						if(isset($this->request->data['job_attachment']) && !empty($this->request->data['job_attachment'])){
							foreach($this->request->data['job_attachment'] as $data){					
								$data['job_id'] = $job_id;
								$data['name'] = $data['name'];
								$jobAttachmentValue = $this->JobAttachments->newEntity();	
								$jobAttachmentData = $this->JobAttachments->patchEntity($jobAttachmentValue,$data);
								$this->JobAttachments->save($jobAttachmentData);
								@copy (WWW_ROOT . SIN_FILE . DS . $data['name'],WWW_ROOT . JOB_ATTACHMENT_FILE . DS . $data['name']);
								if (!empty($data['name']) && file_exists(WWW_ROOT . SIN_FILE . DS . $data['name'])) {
										unlink(WWW_ROOT . SIN_FILE . DS . $data['name']);
								}								
							}							
						}
						
					$this->set([
					'success' => true,
					'data' => [
					'message' =>__('jobHasBeenSaved'),
					],
					'_serialize' => ['data','success']
					]);
						return ;
						} else {
							if ($job->errors()) {
								$this->set([
									'success' => false,
									'data' => [
										'code' => 422,
										'url' => h($this->request->here()),
										'message' => count($job->errors()).__('validationErrorsOccured'),
										'error' => '',
										'errorCount' => count($job->errors()),
										'errors' => $job->errors(),
										],
									'_serialize' => ['success', 'data']]);
								return ;
							}
						}
					}
			}else{			
				$this->set([
					'success' => true,
					'data' => [
						// 'message' =>__('offerHasBeenSaved')
					],
					'_serialize' => ['data','success']
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
	
	
    
    /**
     *Get the list of my jobs
    */
    public function myJobs()
    {  
        if ($this->request->is('get')) {
            $checkAuth = $this->check_user_authrization();
			
            if ($checkAuth) {
                $user = $this->Auth->identify();
                $userId = $user['id'];
                $roleId = $user['role_id']; 
			
                if ($roleId == USER_CLIENT_ROLE) {
                    $this->loadModel('Offers');
                    $this->loadModel('CustomFieldValues');
                    $conditions = array();
                    $conditions[] = ['Jobs.user_id'=>$userId];
                   
					$this->Jobs->hasMany('AwardedOffers', [
                        'className' => 'Offers',
                        'foreignKey'=>'job_id'
                    ]);
						
						$this->CustomFieldValues->belongsTo('CustomFields', [
						'className' => 'CustomFields',
						'foreignKey' => 'custom_field_id'
						]);

						$this->Jobs->hasMany('CustomFieldValues', [
						'className' => 'CustomFieldValues',
						'foreignKey' => 'job_id'
						]);
                    $this->paginate = [
                       'limit' => FRONT_PAGE_LIMIT,
                        'contain'=>[
							 'CustomFieldValues'=>['CustomFields'],	
                             'AwardedOffers' => function (\Cake\ORM\Query $q) {
                                return $q->where(['AwardedOffers.status IN'=>[ACCEPTED_OFFER_STATUS,COMPLETED_OFFER_STATUS]])
								->group(['id']);
								
                            },
                        ],
                        'conditions' => $conditions,
                        'order' => [
                            'Jobs.created' => 'DESC'
                        ],
                        'group'=>'Jobs.id'
                    ];
                    $myJobs = $this->paginate($this->Jobs);
				
                    $this->set([
                    'success' => true,
                    'data' => $myJobs,
                    'paging'=>['page'=>$this->request->params['paging']['Jobs']['page'],
                                'current'=>$this->request->params['paging']['Jobs']['current'],
                                'pageCount'=>$this->request->params['paging']['Jobs']['pageCount'],
                                'current'=>$this->request->params['paging']['Jobs']['page'],
                                'nextPage'=>$this->request->params['paging']['Jobs']['nextPage'],
                                'prevPage'=>$this->request->params['paging']['Jobs']['prevPage'],
                                'count'=>$this->request->params['paging']['Jobs']['count'],
                                'perPage'=>$this->request->params['paging']['Jobs']['perPage'],
                            ],
                    '_serialize' => ['data','success','paging']
                    ]);
                } elseif ($roleId == USER_PRO_EXPERT_ROLE) {
                    $this->loadModel("Offers");
                    $this->loadModel("Jobs");
                    $this->loadModel("CustomFieldValues");
                    $conditions[] = ['Offers.user_id'=>$userId];
                    $conditions[] = ['OR'=>[['Offers.status'=>ACCEPTED_OFFER_STATUS],['Offers.status'=>COMPLETED_OFFER_STATUS]]];
                    
					$this->CustomFieldValues->belongsTo('CustomFields');
					$this->Jobs->hasMany('CustomFieldValues');
                    $this->Offers->belongsTo('Jobs');

                    $this->paginate = [
                        'limit' => FRONT_PAGE_LIMIT,
                        'conditions' => $conditions,
                        'contain'=>[
                           'Jobs'=>['CustomFieldValues'=>['CustomFields']]
                        ],
                        'order' => [
                            'Offers.created' => 'DESC'
                        ],
                        'group'=>'Offers.id'
                    ];
                    
                    $myJobs = $this->paginate($this->Offers);
                    $this->set([
                    'success' => true,
                    'data' => $myJobs,
                    'paging'=>['page'=>$this->request->params['paging']['Offers']['page'],
                                'current'=>$this->request->params['paging']['Offers']['current'],
                                'pageCount'=>$this->request->params['paging']['Offers']['pageCount'],
                                'current'=>$this->request->params['paging']['Offers']['page'],
                                'nextPage'=>$this->request->params['paging']['Offers']['nextPage'],
                                'prevPage'=>$this->request->params['paging']['Offers']['prevPage'],
                                'count'=>$this->request->params['paging']['Offers']['count'],
                                'perPage'=>$this->request->params['paging']['Offers']['perPage'],
                            ],
                    '_serialize' => ['data','success','paging']
                    ]);
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
     * Delete the job
    */
    public function delete()
    {
        if ($this->request->is(['post'])) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $getUser = $this->Auth->identify();
                if (!empty($this->request->data['jobId'])) {
                    $id = $this->request->data['jobId'];
                    $jobTable = TableRegistry::get('Jobs');
                    $user = $this->Auth->identify();
                    $userID = $user['id'];
                    $exists = $jobTable->exists(['id' => $id,'user_id' => $userID,'status' => OPEN_JOB_STATUS]);
                    if ($exists) {
                        $offerData = TableRegistry::get('Offers');
                        $queryOffer = $offerData->find()->where(['job_id' => $id]);
                        $offerRowCount = $queryOffer->count();
                        if ($offerRowCount > 0) {
                            $this->set([
                                'success' => false,
                                'data' => [
                                    'code' => 422,
                                    'url' => h($this->request->here()),
                                    'message' =>__('youCannotDeleteJobItContainsOffers'),
                                    'error' => '',
                                    'errorCount' => 1,
                                    'errors' => __('youCannotDeleteJobItContainsOffers'),
                                    ],
                                '_serialize' => ['success', 'data']]);
                            return ;
                        }
                        $jobsData = $jobTable->get($id);
                        if ($jobTable->delete($jobsData)) {
                            $this->set([
                                'success' => true,
                                'data' => [
                                    'message' =>__("jobDeletedSuccessfully")
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
                                'message' =>__('invalidJobId'),
                                'error' => '',
                                'errorCount' => 1,
                                'errors' => __('invalidJobId'),
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
    
    /**
     *Edit the job
    */
    public function edit()
    {
		$this->loadModel('JobAttachments');
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
			// echo $checkAuth;die;
            if ($checkAuth) {
                $user = $this->Auth->identify();
            
                if ($user['role_id'] == USER_PRO_EXPERT_ROLE) {
                    $this->response->statusCode(422);
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' =>__('1validationErrorsOccured'),
                            'error' => '',
                            'errorCount' => 1,
                            'errors' => __('youAreNotAuthrizedForThis'),
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
                $job = $this->Jobs->newEntity();
				if(empty($this->request->data['custome_fields'])){
					$job->errors(['custom_data' => ['_empty'=>__('youAreNotAuthrizedForThis')]]);
				}
                if (empty($this->request->data['jobSlug'])) {
                    $job->errors(['jobSlug' => ['_empty'=>__('jobSlugIsRequired')]]);
                }
                if (empty($this->request->data['title'])) {
                    $job->errors(['title' => ['_empty'=>__('titleRequired')]]);
                }
				if (strlen($this->request->data['title'])  < 5) {
                    $job->errors(['title' => ['_empty'=>__('titleLength')]]);
                }
                if (empty($this->request->data['description'])) {
                    $job->errors(['description' => ['_empty'=>__('DescriptionRequired')]]);
                }
                if (empty($this->request->data['categoryId'])) {
                    $job->errors(['category_id' => ['_empty'=>__('categoryRequired')]]);
                } else {
                    $this->loadModel('Categories');
                    $categoryExists = $this->Categories->exists(['Categories.id' => $this->request->data['categoryId']]);
                    if ($categoryExists) {
                        $categoryDetail = $this->Categories->find()
                            ->where(['id' => $this->request->data['categoryId']])
                            ->first()->toArray();
                        if ($categoryDetail['parent_id'] == 0) {
                            $this->set([
                            'success' => false,
                            'data' => [
                                'code' => 422,
                                'url' => h($this->request->here()),
                                'message' =>__('1validationErrorsOccured'),
                                'error' => '',
                                'errorCount' => 1,
                                'errors' => __('youCanUseOnlyChildCategoriesForTheJob'),
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
                            'error' => '',
                            'errorCount' => 1,
                            'errors' => __('categoryNotExists'),
                            ],
                        '_serialize' => ['success', 'data']]);
                        return ;
                    }
                }
                if (empty($this->request->data['end_date'])) {
                    $job->errors(['end_date' => ['_empty'=>__('EnddateRequired')]]);
                } else {
                    $date= $this->request->data['end_date'];

                  /*   if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) (2[0-3]|[01][0-9]):([0-5][0-9])$/", $date)) {
                        $job->errors(['end_date' => ['validFormat'=>__('invalidEndDate')]]);
                    } */
                }
                if (empty($this->request->data['location'])) {
                    $job->errors(['location' => ['_empty'=>__('locationRequired')]]);
                }
               
                if ($job->errors()) {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' => count($job->errors()).__('validationErrorsOccured'),
                            'error' => '',
                            'errorCount' => count($job->errors()),
                            'errors' => $job->errors(),
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
                    
                $jobSlug = $this->request->data['jobSlug'];
                $exists = $this->Jobs->exists(['Jobs.slug' => $jobSlug]);
                if ($exists) {
                    $jobDetail = $this->Jobs->find()
                                ->where(['slug' => $jobSlug])
                                ->first()->toArray();
                                
                    if ($jobDetail['user_id'] != $user['id']) {
                        $this->set([
                            'success' => false,
                            'data' => [
                                'code' => 422,
                                'urlss' => h($user),
                                'url' => h($this->request->here()),
                                'message' =>__('1validationErrorsOccured'),
                                'error' => '',
                                'errorCount' => 1,
                                'errors' => __('youAreNotOwnerOfTheJobYouCannotUpdateTheJob'),
                                ],
                            '_serialize' => ['success', 'data']]);
                        return ;
                    }
                    if ($jobDetail['status'] != OPEN_JOB_STATUS) {
                        $this->set([
                            'success' => false,
                            'data' => [
                                'code' => 422,
                                'url' => h($this->request->here()),
                                'message' =>__('1validationErrorsOccured'),
                                'error' => '',
                                'errorCount' => 1,
                                'errors' => __('youCannotUpdateTheJobBecauseItsNotOpenJob'),
                                ],
                            '_serialize' => ['success', 'data']]);
                        return ;
                    }
                    if (isset($this->request->data['categoryId']) && !empty($this->request->data['categoryId'])) {
                        $this->loadModel('Categories');
                        $categoryExists = $this->Categories->exists(['Categories.id' => $this->request->data['categoryId']]);
                        if ($categoryExists) {
                            $categoryDetail = $this->Categories->find()
                                ->where(['id' => $this->request->data['categoryId']])
                                ->first()->toArray();
                            if ($categoryDetail['parent_id'] == 0) {
                                $this->set([
                                'success' => false,
                                'data' => [
                                    'code' => 422,
                                    'url' => h($this->request->here()),
                                    'message' =>__('1validationErrorsOccured'),
                                    'error' => '',
                                    'errorCount' => 1,
                                    'errors' => __('youCanUseOnlyChildCategoriesForTheJob'),
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
                                'error' => '',
                                'errorCount' => 1,
                                'errors' => __('categoryNotExists'),
                                ],
                            '_serialize' => ['success', 'data']]);
                            return ;
                        }
                    }
                    $job = $this->Jobs->get($jobDetail['id']);
                    $this->Jobs->patchEntity($job, $this->request->data);
					
					
				$custom_data = json_decode($this->request->data['custome_fields']);	
			
				if(isset($custom_data->task_type) && !empty($custom_data->task_type)){
					$task_type['value'] =  $custom_data->task_type;
					$task_type['custom_field_id'] =  $custom_data->radio_custom_field_id;
					$task_type['job_id'] =  $job->id;
					$customTaskFielsData[] = $task_type;					
				}
			
				
				if(isset($custom_data->task_checkbox) && !empty($custom_data->task_checkbox)){
					$task_checkbox['value'] = $custom_data->task_checkbox;
					$task_checkbox['custom_field_id'] =  $custom_data->checkbox_custom_field_id;
					$task_checkbox['job_id'] =  $job->id;
					$customTaskFielsData[] = $task_checkbox;
				}if(isset($custom_data->task_textarea) && !empty($custom_data->task_textarea)){
					$task_textarea['value'] = $custom_data->task_textarea;
					$task_textarea['custom_field_id'] =  $custom_data->textarea_custom_field_id;
					$task_textarea['job_id'] =  $job->id;
					$customTaskFielsData[] = $task_textarea;
				}if(isset($custom_data->task_text) && !empty($custom_data->task_text)){
					$task_text['value'] = $custom_data->task_text;
					$task_text['custom_field_id'] =  $custom_data->textbox_custom_field_id;
					$task_text['job_id'] =  $job->id;
					$customTaskFielsData[] = $task_text;
				}
			
				$this->loadModel('CustomFieldValues');
				
				
				if(isset($custom_data->task_select) && !empty($custom_data->task_select)){
					$task_selected['value'] = $custom_data->task_select;
					$task_selected['custom_field_id'] =  $custom_data->select_custom_field_id;
					$task_selected['job_id'] =  $job->id;
					$customTaskSelectFielsData = $task_selected;
					
						
					foreach($customTaskSelectFielsData['value'] as $key=>$val){

						$customSelectedData = $this->CustomFieldValues->find()
							->where(['job_id' => $job->id,'custom_field_id' => $key])
							->first();
						if(isset($customSelectedData->id) && !empty($customSelectedData->id)){
							$selectvaluedata['id'] = $customSelectedData->id;						
						}
						$selectvaluedata['custom_field_id'] = $key;
						$selectvaluedata['value'] = $val;
						$selectvaluedata['job_id'] =  $job->id;
						$customFieldValueData = $this->CustomFieldValues->newEntity();
						$customFieldPatchData = $this->CustomFieldValues->patchEntity($customFieldValueData,$selectvaluedata);	
						$this->CustomFieldValues->save($customFieldPatchData);
							
					
					}
				
				}
				
				
				foreach($customTaskFielsData as $data){		
					$customData = $this->CustomFieldValues->find()
					->where(['job_id' => $data['job_id'],'custom_field_id' => $data['custom_field_id']])
					->first();
					if(isset($customData->id) && !empty($customData->id)){
						$data['id'] = $customData->id;						
					}
					
					$customFieldValue = $this->CustomFieldValues->newEntity();	
					$customFieldData = $this->CustomFieldValues->patchEntity($customFieldValue,$data);
					if ($this->CustomFieldValues->save($customFieldData)) {
						$this->Flash->admin_flash_success(__('customCategorySaved'));
					} 
				}

				/* if(isset($this->request->data['job_attachment']) && !empty($this->request->data['job_attachment'])){
					foreach($this->request->data['job_attachment'] as $data){					
						$data['job_id'] =  $job->id;
						$data['name'] = $data['name'];
						$jobAttachmentValue = $this->JobAttachments->newEntity();	
						$jobAttachmentData = $this->JobAttachments->patchEntity($jobAttachmentValue,$data);
						$this->JobAttachments->save($jobAttachmentData);
					}							
				} */
					
                    if ($this->Jobs->save($job)) {
                        $this->set([
                            'success' => true,
                            'data' => [
                                'message' =>__('jobUpdatedSuccessfully'),
                                'id'=>$job->id
                            ],
                            '_serialize' => ['data','success']
                        ]);
                    } else {
                        if ($job->errors()) {
                            $this->set([
                                'success' => false,
                                'data' => [
                                    'code' => 422,
                                    'url' => h($this->request->here()),
                                    'message' => count($job->errors()).__('validationErrorsOccured'),
                                    'error' => '',
                                    'errorCount' => count($job->errors()),
                                    'errors' => $job->errors(),
                                    ],
                                '_serialize' => ['success', 'data']]);
                            return ;
                        }
                    }
                } else {
                    $errors = ['jobSlug'=>['_required'=>__('jobSlugIsInvalid')]];
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
    
    public function getVehicleType()
    {
        if ($this->request->is('get')) {
            $vehicles = array('1'=> __('noVehicleRequired'),'2'=> __('needsCar'),'3'=>__('needsTruck'),'4'=>__('needsCarTruck'));
            $this->set([
                'success' => true,
                'vehicles' => $vehicles,
                '_serialize' => ['vehicles','success']
            ]);
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
	
    public function getTaskType()
    {
        if ($this->request->is('get')) {
            $taskTypes = array('1'=> __('small'),'2'=> __('medium'),'3'=>__('large'));
            $this->set([
                'success' => true,
                'taskTypes' => $taskTypes,
                '_serialize' => ['taskTypes','success']
            ]);
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
    
    public function generateSlug($string, $id=null)
    {
        $slug = Inflector::slug($string, '-');
        $i = 0;
        
        $params[]= array();
        $params ['conditions'][$this->name.'.slug']= $slug;
        
        $jobData = TableRegistry::get('Jobs');
        
        while ($jobData->exists([$this->name.'.slug' => $slug])) {
            if (!preg_match('/-{1}[0-9]+$/', $slug)) {
                $slug .= '-' . ++$i;
            } else {
                $slug = preg_replace('/[0-9]+$/', ++$i, $slug);
            }
    
            $params[$this->name.'.slug'] = $slug;
        }
        return $slug;
    }
    
    public function makeOffer()
    {	
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
          
            if ($checkAuth) {
                $getUser = $this->Auth->identify();
                $userId = $getUser['id'];
                
                if ($getUser['role_id'] == USER_CLIENT_ROLE) {
                    $this->response->statusCode(422);
                    $this->set([
                            'success' => false,
                            'data' => [
                                'code' => 422,
                                'url' => h($this->request->here()),
                                'message' =>__('1validationErrorsOccured'),
                                'error' => '',
                                'errorCount' => 1,
                                'errors' => __('youAreNotAuthrizedForThis'),
                                ],
                            '_serialize' => ['success', 'data']]);
                    return ;
                }
                $this->loadModel('Offers');
                        
                $jobSlug = $this->request->data['slug'];
                $offer = $this->Offers->newEntity();
                $exists = $this->Jobs->exists(['Jobs.slug' => $jobSlug]);

                if (!$exists) {
                    $offer->errors(['slug' => ['_invalid'=>__('jobSlugIsInvalid')]]);
                    if ($offer->errors()) {
                        $this->set([
                                'success' => false,
                                'data' => [
                                'code' => 422,
                                'url' => h($this->request->here()),
                                'message' => count($offer->errors()).__('unableAddOffer'),
                                'error' => '',
                                'errorCount' => count($offer->errors()),
                                'errors' => $offer->errors(),
                                ],
                            '_serialize' => ['success', 'data']]);
                        return ;
                    }
                } else {
                    $job = $this->Jobs->find()
                                                ->where(['slug' => $jobSlug])
                                                ->first();
                    $jobId = $job['id'];
					
                    $offer = $this->Offers->find('all', [
                        'conditions' => ['Offers.job_id' => $jobId ,'Offers.user_id'=>$userId]
                        ])->first();

                    if (!empty($offer)) {
                        $this->request->data['user_id'] = $userId;
                        $this->request->data['job_id'] = $jobId;
                                
                        $offer = $this->Offers->patchEntity($offer, $this->request->data, ['validate' => 'front']);
                            
                        if ($this->Offers->save($offer)) {
                            // Updating new message
                            $thread = TableRegistry::get('Threads')->find()
                                ->where(['job_id' => $jobId])
                                ->first();
                            if ($thread) {
                                // creating new message for thread
                                $messages = TableRegistry::get('ThreadMessages');
                                $message = $messages->newEntity();
                                $message->thread_id = $thread->id;
                                $message->author = $userId;
                                $message->counterpart = $job['user_id'];
                                $message->body = $offer->description;
                                $message->unix_time = time();
                                $messages->save($message);
                            }
                            $this->set([
                                    'success' => true,
                                        'data' => [
                                        'message' =>__('offerHasBeenSaved'),
                                        'job' => $job,
                                        'offer' => $offer
                                        ],
                                    '_serialize' => ['data','success']
                                ]);
                        } else {
                            if ($offer->errors()) {
                                $this->set([
                                        'success' => false,
                                        'data' => [
                                        'code' => 422,
                                        'url' => h($this->request->here()),
                                        'message' => count($offer->errors()).__('unableAddOffer'),
                                        'error' => '',
                                        'errorCount' => count($offer->errors()),
                                        'errors' => $offer->errors(),
                                        ],
                                    '_serialize' => ['success', 'data']]);
                                return ;
                            }
                        }
                    } else {	
						
						$offers = TableRegistry::get('Offers');
						$offerTable = $offers->newEntity($this->request->data,['validate' => 'front']);

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
						$offerTable->user_id = $userId;	
						$offerTable->job_id = $jobId;
						$offerTable->price = $this->request->data['price'];
						$offers->save($offerTable);
                        // Connecting expert and client
                        $threads = TableRegistry::get('Threads');
                        $thread = $threads->newEntity();
                        $thread->member_id = $userId;
                        $thread->counterpart_id = $job['user_id'];
                        $thread->thread_type = 2; // for thread type job
                        $thread->job_id = $job['id'];
                        $thread->unix_time = time();
                        $threads->save($thread);
                        $threadId = $thread->id;
                        // creating new message for thread
                        $messages = TableRegistry::get('ThreadMessages');
                        $message = $messages->newEntity();
                        $message->thread_id = $threadId;
                        $message->author = $userId;
                        $message->counterpart = $job['user_id'];
                        $message->body = $offerTable->description;
                        $message->unix_time = time();
                        $messages->save($message);

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
                }
            }
        }
    }
    
    public function paypalIpn()
    {
        if ($this->request->is('post')) {
            $transactionData = TableRegistry::get('Transactions');
            $this->loadModel('Offers');
            $this->Offers->belongsTo('Jobs', [
            'className' => 'Jobs',
            'foreignKey'=>'job_id'
            ]);
            $this->Jobs->belongsTo('Users', [
            'className' => 'Users',
            'foreignKey'=>'user_id'
            ]);
            $offer = $this->Offers->get($this->request->data['custom'], ['contain'=>['Jobs'=>['Users']]]);
            
            $queryTransaction = $transactionData->find()->where(['offer_id' =>$this->request->data['custom'],'job_id'=>$offer->job->id]);
            $transactionRowCount = $queryTransaction->count();
            if ($transactionRowCount == 0) {
                $transactionNewData = $transactionData->newEntity();
                $transactionNewData->offer_id = $this->request->data['custom'];
                $transactionNewData->job_id = $offer->job->id;
                $transactionNewData->user_id = $offer->job->user->id;
                $transactionNewData->member_id = $offer->user_id;
                $transactionNewData->transaction_id = $this->request->data['txn_id'];
                $transactionNewData->price = $this->request->data['mc_gross'];
                $transactionNewData->paypal_commission = $this->request->data['mc_fee'];
                $transactionNewData->status = 1;
                $transactionNewData->created = date('Y-m-d H:i:s');
                if ($transactionData->save($transactionNewData)) {
                    $this->loadModel('Offers');
                    $this->Offers->belongsTo('Jobs', [
                    'className' => 'Jobs',
                    'foreignKey'=>'job_id'
                    ]);
                    $this->Offers->belongsTo('Users', [
                    'className' => 'Users',
                    'foreignKey'=>'user_id'
                    ]);
                    $offer = $this->Offers->get($this->request->data['custom'], ['contain'=>['Jobs','Users']]);
                    if (isset($offer->user->email) && !empty($offer->user->email)) {
                        I18n::locale($this->request->session()->read('Config.language'));
                        $emailData = TableRegistry::get('EmailTemplates');
                        $emailDataResult = $emailData->find()->where(['slug' => 'payment_confirmed']);
                        $emailContent = $emailDataResult->first();

                        if (isset($offer->user->username) && !empty($offer->user->username)) {
                            $name = $offer->user->username;
                        } elseif (isset($offer->user->first_name) && !empty($offer->user->first_name)) {
                            $name = $offer->user->first_name;
                        }
                        $job_title = $offer->job->title;
                        $to = $offer->user->email;
                        $subject = $emailContent->subject;
                        $mail_message_data = $emailContent->description;
                        $from = SITE_EMAIL;

                        $mail_text_message = str_replace(array('{NAME}','{JOB_TITLE}'), array($name,$job_title), $mail_message_data);
						$mail_message = str_replace('{CONTENT}',$mail_text_message,$this->email_template());
                        parent::sendEmail($from, $to, $subject, $mail_message);
                        $this->_init_language();
                        $this->set([
                                'success' => true,
                                    'data' => [
                                    'message' =>'success',
                                    'job' => $job,
                                    'offer' => $offer
                                    ],
                                '_serialize' => ['data','success']
                            ]);
                    }
                }
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
	
	public function getOffersCount(){
		if ($this->request->is('post')) {
		
			$job_id = $this->request->data['jobId'];
			$offersData = TableRegistry::get('Offers');
			$queryOffer = $offersData->find()->where(['job_id'=>$job_id]);
			$offerRowCount = $queryOffer->count();			
			
			$this->set([
				'success' => true,
				'offerCount' => $offerRowCount,
				'_serialize' => ['offerCount','success']
			]);
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
	
	public function getJobDataById()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            $user = $this->Auth->identify();
            if ($checkAuth) {
                $user = $this->Auth->identify();
                $userId = $user['id'];
                if (!empty($this->request->data['jobId'])) {
                    $jobId = $this->request->data['jobId'];
                    $exists = $this->Jobs->exists(['Jobs.id' => $jobId]);
                    if ($exists) {
                      $jobDetail = $this->Jobs->find()
                                    ->where(['id' => $jobId])
                                    ->first()->toArray();
    
                        $jobDetailArray = array();         
                        $jobDetailArray['user_id'] = $jobDetail['user_id'];
                        $jobDetailArray['id'] = $jobDetail['id'];
                     
                        $this->set([
                            'success' => true,
                            'jobDetail' => $jobDetailArray,
                            '_serialize' => ['jobDetail','success']
                        ]);
                        
                        return ;
                    } else {
                        $errors = ['jobSlug'=>['_required'=>__('jobSlugIsInvalid')]];
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
                    $errors = ['jobSlug'=>['_required'=>__('jobSlugIsInvalid')]];
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
	
	
	public function awardedJobs()
    {  
	
		

        if ($this->request->is('get')) {
            $checkAuth = $this->check_user_authrization();
			
            if ($checkAuth) {
				$getUser = $this->Auth->identify();
				if ($getUser['role_id'] == USER_CLIENT_ROLE) {
                    $this->response->statusCode(422);
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' =>__('1validationErrorsOccured'),
                            'error' => '',
                            'errorCount' => 1,
                            'errors' => __('youAreNotAuthrizedForThis'),
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
               
			
			
			
                $user = $this->Auth->identify();
                $userId = $user['id'];
                $roleId = $user['role_id']; 
				$this->loadModel("Offers");
				$this->loadModel("CustomFieldValues");
				$this->loadModel("Jobs");
				$conditions[] = ['Offers.user_id'=>$userId];
				$conditions[] = ['OR'=>[['Offers.status'=>ACCEPTED_OFFER_STATUS],['Offers.status'=>COMPLETED_OFFER_STATUS]]];
				
				$this->CustomFieldValues->belongsTo('CustomFields');
				$this->Jobs->hasMany('CustomFieldValues');
				
				$this->Offers->belongsTo('Jobs');
				$this->paginate = [
					'limit' => FRONT_PAGE_LIMIT,
					'conditions' => $conditions,
					'contain'=>[
						'Jobs'=>['CustomFieldValues'=>['CustomFields']]
					],
					'order' => [
						'Offers.created' => 'DESC'
					],
					'group'=>'Offers.id'
				];
				
				$myJobs = $this->paginate($this->Offers);
				$this->set([
				'success' => true,
				'data' => $myJobs,
				'paging'=>['page'=>$this->request->params['paging']['Offers']['page'],
							'current'=>$this->request->params['paging']['Offers']['current'],
							'pageCount'=>$this->request->params['paging']['Offers']['pageCount'],
							'current'=>$this->request->params['paging']['Offers']['page'],
							'nextPage'=>$this->request->params['paging']['Offers']['nextPage'],
							'prevPage'=>$this->request->params['paging']['Offers']['prevPage'],
							'count'=>$this->request->params['paging']['Offers']['count'],
							'perPage'=>$this->request->params['paging']['Offers']['perPage'],
						],
				'_serialize' => ['data','success','paging']
				]);
                
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

	
	/* 	$this->Jobs->hasMany('AwardedOffers', [
                        'className' => 'Offers',
                        'foreignKey'=>'job_id'
                    ]);
						
						$this->CustomFieldValues->belongsTo('CustomFields', [
						'className' => 'CustomFields',
						'foreignKey' => 'custom_field_id'
						]);

						$this->Jobs->hasMany('CustomFieldValues', [
						'className' => 'CustomFieldValues',
						'foreignKey' => 'job_id'
						]);
                    $this->paginate = [
                       'limit' => FRONT_PAGE_LIMIT,
                        'contain'=>[
							 'CustomFieldValues'=>['CustomFields'],	
                             'AwardedOffers' => function (\Cake\ORM\Query $q) {
                                return $q->where(['AwardedOffers.status IN'=>[ACCEPTED_OFFER_STATUS,COMPLETED_OFFER_STATUS]])
								->group(['id']);
								
                            },
                        ],
                        'conditions' => $conditions,
                        'order' => [
                            'Jobs.created' => 'DESC'
                        ],
                        'group'=>'Jobs.id'
                    ]; */
	public function postedJobs()
    {	 
        if ($this->request->is('get')) {
            $checkAuth = $this->check_user_authrization();
			
            if ($checkAuth) {
                $user = $this->Auth->identify();
                $userId = $user['id'];
                $roleId = $user['role_id']; 
               
				$this->loadModel('Offers');
				$this->loadModel('CustomFieldValues');
				$conditions = array();
				$conditions[] = ['Jobs.user_id'=>$userId];
				
				$this->Jobs->hasMany('AwardedOffers', [
					'className' => 'Offers',
					'foreignKey'=>'job_id'
				]);
				$this->CustomFieldValues->belongsTo('CustomFields', [
					'className' => 'CustomFields',
					'foreignKey' => 'custom_field_id'
				]);

				$this->Jobs->hasMany('CustomFieldValues', [
					'className' => 'CustomFieldValues',
					'foreignKey' => 'job_id'
				]);
				
				$this->paginate = [
				   'limit' => FRONT_PAGE_LIMIT,
					'contain'=>[
						 'CustomFieldValues'=>['CustomFields'],	
						 'AwardedOffers' => function (\Cake\ORM\Query $q) {
							return $q->where(['AwardedOffers.status IN'=>[ACCEPTED_OFFER_STATUS,COMPLETED_OFFER_STATUS]])
							->group(['id']);
							
						}, 
				
					],
					'conditions' => $conditions,
					'order' => [
						'Jobs.created' => 'DESC'
					],
					'group'=>'Jobs.id'
				];
				$myJobs = $this->paginate($this->Jobs);
				
				
				
				$this->set([
				'success' => true,
				'data' => $myJobs,
				'paging'=>['page'=>$this->request->params['paging']['Jobs']['page'],
							'current'=>$this->request->params['paging']['Jobs']['current'],
							'pageCount'=>$this->request->params['paging']['Jobs']['pageCount'],
							'current'=>$this->request->params['paging']['Jobs']['page'],
							'nextPage'=>$this->request->params['paging']['Jobs']['nextPage'],
							'prevPage'=>$this->request->params['paging']['Jobs']['prevPage'],
							'count'=>$this->request->params['paging']['Jobs']['count'],
							'perPage'=>$this->request->params['paging']['Jobs']['perPage'],
						],
				'_serialize' => ['data','success','paging']
				]);
                
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
     *Get the review list of user
    */
	public function getJobReviewList(){
		
		if ($this->request->is('post')) {		
				$userID = $this->request->data['userId'];
				$this->loadModel('JobFeedbacks');	
				$this->loadModel('Users');	
				$this->paginate = [
					'conditions'=>['JobFeedbacks.member_id'=>$userID],
					'limit'=>APIPageLimit,
					'order'=>['JobFeedbacks.rate'=>'desc']
				];
				
				$this->JobFeedbacks->belongsTo('Jobs', [

					'className' => 'Jobs',
					'foreignKey'=>'job_id'
				]);
				$this->JobFeedbacks->belongsTo('Users', [

					'className' => 'Users',
					'foreignKey'=>'user_id'
				]);
				$this->Users->hasOne('UserDetails', [
					'className' => 'UserDetails',
					'foreignKey'=>'user_id'
				]);
				
				 $this->paginate = [
                    'contain'=>['Jobs','Users'=>['UserDetails']],
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
			
			
		}else {
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
	
	public function addJobAttachmentFile()
    {
        if ($this->request->is('post')) {
              
			if (isset($this->request->data['files'][0]) && !empty($this->request->data['files'][0])) {
				$this->request->data['file'] = $this->request->data['files'][0];
				unset($this->request->data['files']);
			}
			
			
			if (!empty($this->request->data['file']['tmp_name'])) {
				$jobAttachment = $this->request->data['file'];
				$allowed    =    array('docx','pdf','doc','jpg','jpeg','gif','png','JPG','JPEG','GIF','PNG','PSD','psd','DOCX','PDF','DOC','TXT','txt','xls','xml','xlsm','xlsx');// extensions are allowe
				$temp        =    explode(".", $jobAttachment["name"]);
				$extension    =    end($temp);
				
				if (!in_array($extension, $allowed)) { // check the extension of document
					$errors[] = ['extension'=>['_required'=>'Only pdf, word files allowed']];
				}
				if ($jobAttachment['size'] > 10485760) { // check the size of Curruculum Vitae
					$errors[] = ['size'=>['_required'=>'Size must be less than 10 MB']];
				}
				if (!empty($errors)) {
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
			
				############### police certificate  uploading code start here ###################
				
				
				$fileName    =    'job_attachment_'.microtime(true).'.'.$extension;
				if (move_uploaded_file($jobAttachment['tmp_name'], WWW_ROOT . SIN_FILE . DS . $fileName)) {
					$this->set([
						'success' => true,
						'data' => [
							'message' =>__('uploadingDone'),
							'name'=>SITE_URL.SIN_FILE.DS.$fileName,
							'title'=>$fileName
						],
						'_serialize' => ['data','success']
					]);
				}
				
				############### police certificate uploading code end here ###################
			} else {
				$errors = ['error'=>['_required'=>__('fileIsEmpty')]];
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
	
	public function editJobAttachmentFile()
    {

        if ($this->request->is('post')) {
              
			if (isset($this->request->data['files'][0]) && !empty($this->request->data['files'][0])) {
				$this->request->data['file'] = $this->request->data['files'][0];
				unset($this->request->data['files']);
			}
			
			
			if (!empty($this->request->data['file']['tmp_name'])) {
				$jobAttachment = $this->request->data['file'];
				
				
				
				$allowed  = array('docx','pdf','doc','jpg','jpeg','gif','png','JPG','JPEG','GIF','PNG','PSD','psd','DOCX','PDF','DOC','TXT','txt','xls','xml','xlsm','xlsx');// extensions are allowe
				$temp        =    explode(".", $jobAttachment["name"]);
				$extension    =    end($temp);
				
				if (!in_array($extension, $allowed)) { // check the extension of document
					$errors[] = ['extension'=>['_required'=>'Only pdf, word files allowed']];
				}
				if ($jobAttachment['size'] > 10485760) { // check the size of Curruculum Vitae
					$errors[] = ['size'=>['_required'=>'Size must be less than 10 MB']];
				}
				if (!empty($errors)) {
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
			
				############### police certificate  uploading code start here ###################
				
				
				$fileName    =    'job_attachment_'.microtime(true).'.'.$extension;
				if (move_uploaded_file($jobAttachment['tmp_name'], WWW_ROOT . JOB_ATTACHMENT_FILE . DS . $fileName)) {
				$this->loadModel('JobAttachments');
				if(isset($this->request['data']['job_id']) && !empty($this->request['data']['job_id'])){
								
						$data['job_id'] =  $this->request['data']['job_id'];
						$data['name'] = $fileName;
						$jobAttachmentValue = $this->JobAttachments->newEntity();	
						$jobAttachmentData = $this->JobAttachments->patchEntity($jobAttachmentValue,$data);
						$insertData= $this->JobAttachments->save($jobAttachmentData);
									
				} 
				
				
				
				
				
					$this->set([
						'success' => true,
						'data' => [
							'message' =>__('uploadingDone'),
							'name'=>SITE_URL.JOB_ATTACHMENT_FILE.DS.$fileName,
							'title'=>$fileName,
							'insert_id'=>$insertData['id']
						],
						'_serialize' => ['data','success']
					]);
				}
				
				############### police certificate uploading code end here ###################
			} else {
				$errors = ['error'=>['_required'=>__('fileIsEmpty')]];
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
	
	
	public function addJobAttachmentFileRemove()
    {
		 if ($this->request->is('post')) {
			$jobAttachment = TableRegistry::get('JobAttachments');
			$queryJobAttachment = $jobAttachment->find()->where(['name' =>$this->request->data['file_name']])->first();
			if($queryJobAttachment['id']){
				$f = $jobAttachment->delete($queryJobAttachment);
			}
			if (!empty($this->request->data['file_name']) && file_exists(WWW_ROOT . JOB_ATTACHMENT_FILE . DS . $this->request->data['file_name'])) {
				unlink(WWW_ROOT . JOB_ATTACHMENT_FILE . DS . $this->request->data['file_name']);
			}
			if (!empty($this->request->data['file_name']) && file_exists(WWW_ROOT . SIN_FILE . DS . $this->request->data['file_name'])) {
				unlink(WWW_ROOT . SIN_FILE . DS . $this->request->data['file_name']);
			}
			$this->set([
				'success' => true,
				'data' => [
					'message' =>__('uploadingDone'),
					'id'=>$queryJobAttachment['id'],
					'name'=>SITE_URL.JOB_ATTACHMENT_FILE.DS.$this->request->data['file_name'],
					'title'=>$this->request->data['file_name']
				],
				'_serialize' => ['data','success']
			]);
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
          
        }
		
		
		  return ;
    }
	
	
}
