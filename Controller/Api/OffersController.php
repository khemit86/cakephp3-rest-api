<?php
namespace App\Controller\Api;

use Cake\Event\Event;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Utility\Security;
use Firebase\JWT\JWT;
use Cake\ORM\TableRegistry;
use Cake\I18n\I18n;
use Intervention\Image\ImageManager;

class OffersController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Flash'); // Include the FlashComponent
         $this->Auth->allow(['add','view','offersList','edit','updateOfferStatus','feedback','viewFeedback','getOfferByJob']);
    }
    
    /**
     *Make a offer
    */
    public function add()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $getUser = $this->Auth->identify();
                $userId = $getUser['id'];
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
                $offer = $this->Offers->newEntity();
                if (!isset($this->request->data['jobSlug']) || empty($this->request->data['jobSlug'])) {
                    $offer->errors(['jobSlug' => [__('jobSlugIsRequired')]]);
                }
                if (!isset($this->request->data['description']) || empty($this->request->data['description'])) {
                    $offer->errors(['description' => [__('DescriptionRequired')]]);
                }
                if (!isset($this->request->data['price']) || empty($this->request->data['price'])) {
                    $offer->errors(['price' => [__('priceIsRequired')]]);
                }
                if ($offer->errors()) {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' => count($offer->errors())." ".__("validationErrorsOccured"),
                            'error' => '',
                            'errorCount' => count($offer->errors()),
                            'errors' => $offer->errors(),
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
                $this->loadModel('Jobs');
                $jobSlug = $this->request->data['jobSlug'];
                $jobDetail = $this->Jobs->find()
                                ->where(['slug' => $jobSlug])
                                ->first()->toArray();
                if (!$offer->errors()) {
                    $exists = $this->Jobs->exists(['Jobs.slug' => $jobSlug]);
                    if ($exists) {
                        if ($jobDetail['status'] != OPEN_JOB_STATUS) {
                            $this->set([
                            'success' => false,
                            'data' => [
                                'code' => 422,
                                'url' => h($this->request->here()),
                                'message' =>__('1validationErrorsOccured'),
                                'error' => '',
                                'errorCount' => 1,
                                'errors' => __('youCannotMakeTheOfferBecauseJobIsNotOpen'),
                                ],
                            '_serialize' => ['success', 'data']]);
                            return ;
                        }
                        $offerExists = $this->Offers->exists(['Offers.user_id' => $userId,'Offers.job_id'=>$jobDetail['id']]);
                        if ($offerExists) {
                            $this->set([
                            'success' => false,
                            'data' => [
                                'code' => 422,
                                'url' => h($this->request->here()),
                                'message' =>__('1validationErrorsOccured'),
                                'error' => '',
                                'errorCount' => 1,
                                'errors' => __('youHaveAlreadyMadeTheOffer'),
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
                            'errors' => __('jobSlugIsInvalid'),
                            ],
                        '_serialize' => ['success', 'data']]);
                        return ;
                    }
                }
                $this->request->data['user_id'] = $userId;
                $this->request->data['job_id'] = $jobDetail['id'];
                $offer = $this->Offers->patchEntity($offer, $this->request->data);
                if ($this->Offers->save($offer)) {
                //if (true) {
					
					################ Email for create offer start #########
					$this->loadModel('Users');
					$userDetail = $this->Users->find()
                                ->where(['id' => $userId])
                                ->first()->toArray();
					$emailData = TableRegistry::get('EmailTemplates');
					$emailDataResult = $emailData->find()->where(['slug' => 'make_offer']);
					$emailContent = $emailDataResult->first();
					
						
					$job_title = $jobDetail['title'];
					$to = $getUser['email'];
					$subject = $emailContent->subject;
					$mail_message_data = $emailContent->description;
					$from = $userDetail['email'];
					$clientName = ucfirst($getUser['first_name'])."&nbsp;".ucfirst($getUser['last_name']);
					$expertName = ucfirst($userDetail['first_name'])."&nbsp;".ucfirst($userDetail['last_name']);
					
					$offerPrice = $this->request->data['price'];
					$offerDescription = $this->request->data['description'];
					$mail_text_message = str_replace(array('{NAME}','{JOB_TITLE}','{OFFER_DESCRIPTION}','{OFFER_PRICE}','{EXPERT_NAME}'), array($clientName,$job_title,$offerDescription,$offerPrice,$expertName), $mail_message_data);
					$mail_message = str_replace('{CONTENT}',$mail_text_message,$this->email_template());
					parent::sendEmail($from, $to, $subject, $mail_message);
					################ Email for create offer end #########
					
					
					
					
					
					
					
                    $this->set([
                        'success' => true,
                        'data' => [
                            'message' =>__('offerCreatedSuccessfully'),
                            'id' =>$offer->id
                        ],
                        '_serialize' => ['data','success']
                    ]);
                    return ;
                } else {
                    if ($offer->errors()) {
                        $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' => count($offer->errors()).' '.__('validationErrorsOccured'),
                            'error' => '',
                            'errorCount' => count($offer->errors()),
                            'errors' => $offer->errors(),
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
     *Edit the offer
    */
    public function edit()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $user = $this->Auth->identify();
                if ($user['role_id'] == USER_CLIENT_ROLE) {
                    $errors = ['error'=>__('youAreNotAuthrizedForThis')];
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
                $errors = array();
                if (!isset($this->request->data['offerId']) || empty($this->request->data['offerId'])) {
                    $errors[] = array('offerId' =>__('offerIdRequired'));
                }
                if (!isset($this->request->data['description']) || empty($this->request->data['description'])) {
                    $errors[] = array('description' =>__('DescriptionRequired'));
                }
                if (!isset($this->request->data['price']) || empty($this->request->data['price'])) {
                    $errors[] = array('price' =>__('priceIsRequired'));
                }
                if (!empty($errors)) {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' => count($errors)." ".__("validationErrorsOccured"),
                            'error' => '',
                            'errorCount' => count($errors),
                            'errors' => $errors,
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
                $offerId = $this->request->data['offerId'];
                $offerExists = $this->Offers->exists(['id' => $offerId]);
                
                if (!$offerExists) {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' => __('1validationErrorsOccured'),
                            'error' => '',
                            'errorCount' => 1,
                            'errors' => __('offerIdIsInvalid'),
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
                $offer = $this->Offers->get($offerId);
                if (!empty($offerDetail)) {
                    if ($offer->status != OPEN_OFFER_STATUS) {
                        $errors[] = ['error'=>__('youCannotUpdateTheOffer')];
                    }
                    if ($offer->user_id != $user['id']) {
                        $errors[] = ['error'=>__('youAreInvalidUserForUpdateTheOffer')];
                    }
                    
                    if (!empty($errors)) {
                        $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' => count($errors).' '.__('validationErrorsOccured'),
                            'error' => '',
                            'errorCount' => count($errors),
                            'errors' => $errors,
                            ],
                        '_serialize' => ['success', 'data']]);
                        return ;
                    }
                }
                $this->request->data['id'] = $offerId;
                unset($this->request->data['offerId']);
                $this->Offers->patchEntity($offer, $this->request->data);
                if ($this->Offers->save($offer)) {
                    $this->set([
                        'success' => true,
                        'data' => [
                            'message' =>__('offerUpdatedSuccessfully'),
                            'id'=>$offerId
                        ],
                        '_serialize' => ['data','success']
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
     *Get the offer list
    */
    public function offersList()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $getUser = $this->Auth->identify();
                $userId = $getUser['id'];
                if ($getUser['role_id'] == USER_PRO_EXPERT_ROLE) {
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
                $this->loadModel('Jobs');
                $jobSlug = $this->request->data['jobSlug'];
                $exists = $this->Jobs->exists(['Jobs.slug' => $jobSlug]);
                if ($exists) {
                    $jobDetail = $this->Jobs->find()
                                ->where(['slug' => $jobSlug])
                                ->first()->toArray();
                    
                    $conditions = array();
                    $conditions[] = ['Offers.job_id'=>$jobDetail['id']];
                    
                    $this->paginate = [
                        'limit'=>APIPageLimit,
                        'conditions' => $conditions,
                        'order' => [
                            'Offers.id' => 'DESC'
                        ]
                    ];
                    $offers = $this->paginate($this->Offers);
                    $this->set([
                        'success' => true,
                        'data' => $offers,
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
    
    /**
     *Get the offer by job
    */
    public function getOfferByJob()
    {
        if ($this->request->is('post')) {
            $user = $this->Auth->identify();
        
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $user = $this->Auth->identify();
                $userId = $user['id'];
                
                
                
                if (!empty($this->request->data['jobId'])) {
                    $jobId = $this->request->data['jobId'];
                    $userId = $this->request->data['userId'];
                    $exists = $this->Offers->exists(['Offers.job_id' => $jobId,'Offers.user_id'=>$userId]);
                    
                    if ($exists) {
                        $OfferDetail = $this->Offers->find('all', [
                                            'conditions' => ['Offers.job_id' => $jobId,'Offers.user_id'=>$userId]
                                            ])->first();
                        if ($OfferDetail->user_id != $userId) {
                            $errors = [__('youAreNotAuthrizedForThis')];
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
                        $offerDetailArray = array();
                        $offerDetailArray['id'] = $OfferDetail->id;
                        $offerDetailArray['status'] = $OfferDetail->status;
                        $offerDetailArray['offer_user_id'] = $OfferDetail->user_id;
                        $offerDetailArray['offer_description'] = $OfferDetail->description;
                        $offerDetailArray['offer_price'] = $OfferDetail->price;
                        $this->set([
                            'success' => true,
                            'offerDetail' => $offerDetailArray,
                            '_serialize' => ['offerDetail','success']
                        ]);
                        return ;
                    } else {
                        $errors = ['offerId'=>'offerIdNotExists'];
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
                    $errors = ['offerId'=>__('offerIdRequired')];
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
    
    /**
     *Get the offer detail
    */
    public function view()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $user = $this->Auth->identify();
                $userId = $user['id'];
                
                if (!empty($this->request->data['offerId'])) {
                    $offerId = $this->request->data['offerId'];
                    $exists = $this->Offers->exists(['Offers.id' => $offerId]);
                    if ($exists) {
                        $this->Offers->belongsTo('Jobs');
                        $OfferDetail = $this->Offers->get($offerId, [
                        'contain' => ['Jobs']
                        ]);
                        if ($OfferDetail->user_id != $userId &&  $OfferDetail->job->user_id != $userId) {
                            $errors = [__('youAreNotAuthrizedForThis')];
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
                        $offerDetailArray = array();
                        $offerDetailArray['offer_id'] = $OfferDetail->id;
                        $offerDetailArray['offer_user_id'] = $OfferDetail->user_id;
                        $offerDetailArray['offer_description'] = $OfferDetail->description;
                        $offerDetailArray['offer_price'] = $OfferDetail->price;
                        $offerStatus = "";
                        if ($OfferDetail->status == OPEN_OFFER_STATUS) {
                            $offerStatus = __('offerStatusOpen');
                        } elseif ($OfferDetail->status == ACCEPTED_OFFER_STATUS) {
                            $offerStatus = __('offerStatusAccepted');
                        } elseif ($OfferDetail->status == DECLINED_OFFER_STATUS) {
                            $offerStatus =  __('offerStatusDeclined');
                        } elseif ($OfferDetail->status == COMPLETED_OFFER_STATUS) {
                            $offerStatus =  __('offerStatusCompleted');
                        }
                        $offerDetailArray['offer_status'] = $offerStatus;
                        $offerDetailArray['offer_status_value'] = $OfferDetail->status;
                        if (!empty($OfferDetail->job)) {
                            $offerDetailArray['job_id'] = $OfferDetail->job->id;
                            $offerDetailArray['job_title'] = $OfferDetail->job->title;
                            $offerDetailArray['job_description'] = $OfferDetail->job->description;
                            $offerDetailArray['job_slug'] = $OfferDetail->job->slug;
                            $offerDetailArray['job_location'] = $OfferDetail->job->location;
                            $offerDetailArray['job_basic_supplies'] = $OfferDetail->job->basic_supplies;
                            $offerDetailArray['job_mop'] = $OfferDetail->job->mop;
                            $offerDetailArray['job_vaccum'] = $OfferDetail->job->vaccum;
                            $offerDetailArray['job_type'] = $OfferDetail->job->task_type;
                            $offerDetailArray['job_vehicle_type_value'] = $OfferDetail->job->vehicle_type;
                            if (!empty($OfferDetail->job->category_id)) {
                                $this->loadModel('Categories');
                                $categoryDetail = $this->Categories->find()
                                        ->where(['id' => $OfferDetail->job->category_id])
                                        ->first()->toArray();
                                $offerDetailArray['job_category'] = $categoryDetail['name'];
                            }
                        }
                        $this->set([
                            'success' => true,
                            'offerDetail' => $offerDetailArray,
                            '_serialize' => ['offerDetail','success']
                        ]);
                        
                        return ;
                    } else {
                        $errors = ['offerId'=>'offerIdNotExists'];
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
                    $errors = ['offerId'=>__('offerIdRequired')];
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
    
    /**
     *Update the offer
    */
    public function updateOfferStatus()
    {
        if ($this->request->is('post', 'put')) {
            $checkAuth = $this->check_user_authrization();
            
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
            
                if (empty($this->request->data['offerId'])) {
                    $errors[] = array('offerId'=>__('offerIdIsRequired'));
                }
                if (empty($this->request->data['type'])) {
                    $errors[] = array('type'=>__('typeIsRequired'));
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
                
                $id = $this->request->data['offerId'];
                $type = $this->request->data['type'];
                
                $user = $this->Auth->identify();
                $offersTable = TableRegistry::get('Offers');
                $jobsTable = TableRegistry::get('Jobs');
            
                $offersTable->belongsTo('Users');
                $offer = $offersTable->get($id, ['contain'=>['Users'=>['fields'=>['Users.first_name','Users.last_name','Users.email']]]]);
                $jobId = $offer->job_id;
                $checkJobUser = $jobsTable->exists(['user_id' => $user['id'],'id'=>$jobId]);
                if (!$checkJobUser) {
                    $errorsMsg = array(__('youAreNotAuthrizedToAcceptTheOffer'));
                }
                
                $offerExists = $offersTable->exists(['id' => $id]);
                
                if (!$offerExists) {
                    $errorsMsg = array(__('offerNotExists'));
                }
                if ($offerExists) {
                    if ($offer->status == ACCEPTED_OFFER_STATUS && $type == "accept") {
                        $errorsMsg = array(__('offerAlreadyAccepted'));
                    }
                    if ($offer->status == DECLINED_OFFER_STATUS  &&  $type == "decline") {
                        $errorsMsg = array(__('offerAlreadyDeclined'));
                    }
                    if ($offer->status == COMPLETED_OFFER_STATUS && $type == "complete") {
                        $errorsMsg = array(__('offerAlreadyCompleted'));
                    }
                }
                if (!empty($errors)) {
                    $this->response->statusCode(422);
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'urldd' => h($this->request->here()),
                            'url' => h($this->request->here()),
                            'message' => $errorsMsg,
                            'error' => '',
                            'errorCount' => count($errors),
                            'errors' => $errors,
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
                
                if ($type == "accept") {
                    $offer->status = ACCEPTED_OFFER_STATUS;
                    $offerMessage = __("offerAcceptedSuccessfully");
                } elseif ($type == "decline") {
                    $offer->status = DECLINED_OFFER_STATUS;
                    $offerMessage = __("offerDeclinedSuccessfully");
                } elseif ($type == "complete") {
                    $offer->status = COMPLETED_OFFER_STATUS;
                    $offerMessage = __("offerCompletedSuccessfully");
                }
                
                if ($offersTable->save($offer)) {
                    $job = $jobsTable->get($jobId);
                    
                    if ($type == "accept") {
                        $job->status = ACCEPTED_JOB_STATUS;
                        $jobsTable->save($job);
                        /***** mail code for accept the offer start  *****/
                        
                            if (!empty($offer->user) && !empty($offer->user->email)) {
                                $acceptOfferMailTo = $offer->user->email;
                                $acceptOfferNameTo = "";
                                if ($offer->user->first_name) {
                                    $acceptOfferNameTo = $offer->user->first_name;
                                }
                            
                                /*Email accept offer start*/
                                    $emailData = TableRegistry::get('EmailTemplates');
                                $emailDataResult = $emailData->find()->where(['slug' => 'offer_accept']);
                                $emailContent = $emailDataResult->first();

                                    
                                $job_title = $job->title;
                                $to = $acceptOfferMailTo;
                                $subject = $emailContent->subject;
                                $mail_message_data = $emailContent->description;
                                $from = SITE_EMAIL;

                                $mail_text_message = str_replace(array('{NAME}','{JOB_TITLE}'), array($acceptOfferNameTo,$job_title), $mail_message_data);
								$mail_message = str_replace('{CONTENT}',$mail_text_message,$this->email_template());
                                parent::sendEmail($from, $to, $subject, $mail_message);
                                    
                                /*Email accept offer close*/
                            }
                        /***** mail code for accept the offer end  *****/
                        
                        
                        
                        
                        /***** mail code for decline the offer start  *****/
                        $declineOfferQuery = $offersTable->find('all', ['conditions'=>['Offers.id != '=>$id,'Offers.job_id'=>$jobId,'Offers.status'=>OPEN_OFFER_STATUS],'contain'=>['Users'=>['fields'=>['Users.first_name','Users.last_name','Users.email']]]]);
                        $declineOfferList = $declineOfferQuery->toArray();
                        if (!empty($declineOfferList)) {
                            foreach ($declineOfferList as $key=>$value) {
                                if (!empty($value->user) && !empty($value->user->email)) {
                                    $declineOfferMailTo = $value->user->email;
                                    $declineOfferNameTo = "";
                                    if ($value->user->first_name) {
                                        $declineOfferNameTo = $value->user->first_name;
                                    }
                                    $offersTable->updateAll(['status' => DECLINED_OFFER_STATUS], ['id' => $value->id]);
                                
                                    /*Email decline offer start*/
                                        I18n::locale($this->request->session()->read('Config.language'));
                                    $emailData = TableRegistry::get('EmailTemplates');
                                    $emailDataResult = $emailData->find()->where(['slug' => 'offer_decline']);
                                    $emailContent = $emailDataResult->first();

                                        
                                    $job_title = $job->title;
                                    $to = $declineOfferMailTo;
                                    $subject = $emailContent->subject;
                                    $mail_message_data = $emailContent->description;
                                    $from = SITE_EMAIL;

                                    $mail_text_message = str_replace(array('{NAME}','{JOB_TITLE}'), array($declineOfferNameTo,$job_title), $mail_message_data);
									$mail_message = str_replace('{CONTENT}',$mail_text_message,$this->email_template());
                                    parent::sendEmail($from, $to, $subject, $mail_message);
                                    $this->_init_language();
                                    /*Email decline offer close*/
                                }
                            }
                        }
                        
                        /***** mail code for decline the offer end  *****/
                    }
                    if ($type == "decline") {
                        
                        /***** mail code for decline the offer start  *****/
                        if (!empty($offer->user) && !empty($offer->user->email)) {
                            $declineOfferMailTo = $offer->user->email;
                            $declineOfferNameTo = "";
                            if ($offer->user->first_name) {
                                $declineOfferNameTo = $offer->user->first_name;
                            }
                            
                            /*Email decline offer start*/
                            I18n::locale($this->request->session()->read('Config.language'));
                            $emailData = TableRegistry::get('EmailTemplates');
                            $emailDataResult = $emailData->find()->where(['slug' => 'offer_decline']);
                            $emailContent = $emailDataResult->first();

                            
                            $job_title = $job->title;
                            $to = $declineOfferMailTo;
                            $subject = $emailContent->subject;
                            $mail_message_data = $emailContent->description;
                            $from = SITE_EMAIL;

                            $mail_text_message = str_replace(array('{NAME}','{JOB_TITLE}'), array($declineOfferNameTo,$job_title), $mail_message_data);
							$mail_message = str_replace('{CONTENT}',$mail_text_message,$this->email_template());
                            parent::sendEmail($from, $to, $subject, $mail_message);
                            $this->_init_language();
                            /*Email decline offer close*/
                        }
                        /***** mail code for decline the offer end  *****/
                    }
                    if ($type == "complete") {
                        $job->status = COMPLETED_JOB_STATUS;
                        $jobsTable->save($job);
                        /***** mail code for complete  the offer  and job start  *****/
                        if (!empty($offer->user) && !empty($offer->user->email)) {
                            $completeOfferMailTo = $offer->user->email;
                            $completeOfferNameTo = "";
                            if ($offer->user->first_name) {
                                $completeOfferNameTo = $offer->user->first_name;
                            }
                            /*Email completed offer start*/
                            I18n::locale($this->request->session()->read('Config.language'));
                            $emailData = TableRegistry::get('EmailTemplates');
                            $emailDataResult = $emailData->find()->where(['slug' => 'job_complete']);
                            $emailContent = $emailDataResult->first();

                            
                            $job_title = $job->title;
                            $end_date = $job->end_date;
                            $to = $completeOfferMailTo;
                            $subject = $emailContent->subject;
                            $mail_message_data = $emailContent->description;
                            $from = SITE_EMAIL;

                            $mail_text_message = str_replace(array('{NAME}','{JOB_TITLE}','{END_DATE}'), array($completeOfferNameTo,$job_title,$end_date), $mail_message_data);
							$mail_message = str_replace('{CONTENT}',$mail_text_message,$this->email_template());
                            parent::sendEmail($from, $to, $subject, $mail_message);
                            $this->_init_language();
                            /*Email completed offer close*/
                        }
                        /***** mail code for complete  the offer  and job end  *****/
                    }
                    
                    $this->set([
                        'success' => true,
                        'data' => [
                            'message' =>$offerMessage,
                            'jobSlug' => $job->slug
                        ],
                        '_serialize' => ['data','success']
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
     *Give the feedback
    */
    public function feedback()
    {
        if ($this->request->is(['post', 'put'])) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $jobFeedbackTable = TableRegistry::get('JobFeedbacks');
                $jobFeedback = $jobFeedbackTable->newEntity();
                if (empty($this->request->data['offerId'])) {
                    $jobFeedback->errors(['offerId' => ['_empty'=>__('offerIdIsRequired')]]);
                }
                if (empty($this->request->data['jobId'])) {
                    $jobFeedback->errors(['jobId' => ['_empty'=>__('jobIdIsRequired')]]);
                }
                if (empty($this->request->data['memberId'])) {
                    $jobFeedback->errors(['memberId' => ['_empty'=>__('memberIdIsRequired')]]);
                }
                if (empty($this->request->data['message'])) {
                    $jobFeedback->errors(['message' => ['_empty'=>__('messageIsRequired')]]);
                }
                if (empty($this->request->data['rating'])) {
                    $jobFeedback->errors(['rating' => ['_empty'=>__('ratingIsRequired')]]);
                }
                if ($jobFeedback->errors()) {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' => count($jobFeedback->errors()).__('validationErrorsOccured'),
                            'error' => '',
                            'errorCount' => count($jobFeedback->errors()),
                            'errors' => $jobFeedback->errors(),
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
            
                $this->loadModel('Users');
                $this->loadModel('Jobs');
                $user = $this->Auth->identify();
                $userID = $user['id'];
                $jobId = $this->request->data['jobId'];
                $offerId = $this->request->data['offerId'];
                $memberId = $this->request->data['memberId'];
                
                if (!empty($this->request->data['jobId'])) {
                    $jobId = $this->request->data['jobId'];
                    $jobTable = TableRegistry::get('Jobs');
                    $jobExists = $jobTable->exists(['Jobs.id' => $jobId]);
                    if (!$jobExists) {
                        $errors[] = array('jobId'=>__('jobIdNotExist'));
                    }
                }
                if (!empty($errors)) {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' => count($errors).' '.__('validationErrorsOccured'),
                            'error' => '',
                            'errorCount' => count($errors),
                            'errors' => $errors,
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
                if (!empty($this->request->data['offer_id'])) {
                    $offerId = $this->request->data['offer_id'];
                    $offerTable = TableRegistry::get('Offers');
                    $offerExists = $offerTable->exists(['Offers.id' => $jobId]);
                    if (!$offerExists) {
                        $errors[] = array(__('offerNotExists'));
                    }
                }
                if (!empty($errors)) {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' => count($errors).' '.__('validationErrorsOccured'),
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
                
                $jobFeedbackCheck = $jobFeedbackTable->find()
                        ->where(['member_id'=>$memberId,'job_id'=>$jobId,'offer_id'=>$offerId])
                        ->first();
                
                
                
                $jobFeedback = $jobFeedbackTable->patchEntity($jobFeedback, $this->request->data);
                if (!empty($jobFeedback->errors())) {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' => count($jobFeedback->errors()).' '.__('validationErrorsOccured'),
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
                
                if (!empty($jobFeedbackCheck)) {
                    $errors[] = array(__('youHaveAlreadyGivenFeedback'));
                    if (!empty($errors)) {
                        $this->set([
                            'success' => false,
                            'data' => [
                                'code' => 422,
                                'url' => h($this->request->here()),
                                'message' => __('youHaveAlreadyGivenFeedback'),
                                'error' => '',
                                'errorCount' => count($errors),
                                'errors' => $errors,
                                ],
                            '_serialize' => ['success', 'data']]);
                        return ;
                    }
                }
                
                
                $validationCheck = true;
                $job = $this->Jobs->get($jobId, ['contain'=>['Offers'=>function (\Cake\ORM\Query $q) {
                    return $q->where(['Offers.status IN'=>[COMPLETED_OFFER_STATUS]]);
                }]]);
                
                if ($job->user_id == $userID &&  (isset($job->offer->user_id) && $job->offer->user_id == $memberId)) {
                    $validationCheck = false;
                }
                if ($job->user_id == $memberId &&  (isset($job->offer->user_id) && $job->offer->user_id == $userID)) {
                    $validationCheck = false;
                }
                if ($validationCheck) {
                    $errors[] = array(__('youCannotGivenTheFeedback'));
                    if (!empty($errors)) {
                        $this->set([
                            'success' => false,
                            'data' => [
                                'code' => 422,
                                'url' => h($this->request->here()),
                                'message' => __('youCannotGivenTheFeedback'),
                                'error' => '',
                                'errorCount' => count($errors),
                                'errors' => $errors,
                                ],
                            '_serialize' => ['success', 'data']]);
                        return ;
                    }
                }
                
                if ($jobFeedbackTable->save($jobFeedback)) {
                    $ratingQuery = $jobFeedbackTable->find();
                    $result = $ratingQuery->select([
                        'total' => $ratingQuery->func()->avg('rating')
                    ])
                    ->where(['JobFeedbacks.member_id' => $memberId])
                    ->group('member_id');
                    $totalPrice = $result->first();
                    if (!empty($totalPrice->total)) {
                        $this->loadModel('Users');
                        $user_data = $this->Users->get($memberId);
                        
                        $user_data->rating = $totalPrice->total;
                        $this->Users->save($user_data);
                        $memberDetail = parent::getUserDetail($memberId);
                        if (!empty($memberDetail['email'])) {
                            $name = '';
                            if (!empty($memberDetail['first_name'])) {
                                $name = $memberDetail['first_name'];
                            }
                            
                            $job = $this->Jobs->get($jobId);
                            $emailData = TableRegistry::get('EmailTemplates');
                            $emailDataResult = $emailData->find()->where(['slug' => 'job_rating']);
                            $emailContent = $emailDataResult->first();
                            $to = $memberDetail['email'];
                            $subject = $emailContent->subject;
                            $mail_message_data = $emailContent->description;
                            $mail_text_message = str_replace(array('{NAME}','{RATING}','{FEEDBACK}','{JOB_TITLE}'), array($name,$totalPrice->total,$this->request->data['message'],$job->title), $mail_message_data);
							$mail_message = str_replace('{CONTENT}',$mail_text_message,$this->email_template());
                            $from = SITE_EMAIL;
                            ///parent::sendEmail($from, $to, $subject, $mail_message);
                        }
                    }
                    
                    $id = $jobFeedback->id;
                    $this->set([
                    'success' => true,
                    'data' => [
                        'message' =>__('feedbackGivenSuccessfully'),
                        'id'=>$id
                    ],
                    '_serialize' => ['data','success']
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
    
    public function viewFeedback()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $user = $this->Auth->identify();
                $userId = $user['id'];
                
                
                if (empty($this->request->data['offerId'])) {
                    $errors['offerId'] = array(__('offerIdIsRequired'));
                }
                if (empty($this->request->data['jobId'])) {
                    $errors['jobId'] = array(__('jobIdIsRequired'));
                }
                if (empty($this->request->data['memberId'])) {
                    $errors['memberId'] = array(__('memberIdIsRequired'));
                }
                
                if (!empty($errors)) {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' => count($errors).' '.__('validationErrorsOccured'),
                            'error' => '',
                            'errorCount' => count($errors),
                            'errors' => $errors,
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
                
                $offerId = $this->request->data['offerId'];
                $jobId = $this->request->data['jobId'];
                $memberId = $this->request->data['memberId'];
                $exists = $this->Offers->exists(['Offers.id' => $offerId]);
                if ($exists) {
                    $this->Offers->belongsTo('Jobs');
                    $OfferDetail = $this->Offers->get($offerId, [
                    'contain' => ['Jobs']
                    ]);
                    $jobFeedbackTable = TableRegistry::get('JobFeedbacks');
					
					$jobFeedbackTable->belongsTo('Jobs');
					
			
                    $jobFeedback = $jobFeedbackTable->find()
									->contain(['Jobs'])
									->where(['member_id'=>$memberId,'job_id'=>$jobId,'offer_id'=>$offerId])
									->first();
					
                    $feedbackDetailArray['feedback'] = array();
                    if (!empty($jobFeedback)) {
                        $feedbackDetailArray['feedback']['jobTitle'] = $jobFeedback['job']['title'];
                        $feedbackDetailArray['feedback']['jobSlug'] = $jobFeedback['job']['slug'];
                        $feedbackDetailArray['feedback']['feedbackId'] = $jobFeedback->id;
                        $feedbackDetailArray['feedback']['rating'] = $jobFeedback->rating;
                        $feedbackDetailArray['feedback']['message'] = $jobFeedback->message;
                        $feedbackDetailArray['feedback']['jobId'] = $jobFeedback->job_id;
                        $feedbackDetailArray['feedback']['memberId'] = $jobFeedback->member_id;
                    } else {
                    }
                    
                    $this->set([
                        'success' => true,
                        'feedbackDetail' => $feedbackDetailArray['feedback'],
                        '_serialize' => ['feedbackDetail','success']
                    ]);
                } else {
                    $errors = ['offerId'=>'offerIdNotExists'];
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
}
