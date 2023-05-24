<?php
namespace App\Controller\Api;

require_once(ROOT . DS .'vendor' . DS  . 'intervention' . DS .'vendor' . DS . 'autoload.php');

use Cake\Event\Event;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Utility\Security;
use Firebase\JWT\JWT;
use Cake\ORM\TableRegistry;
use Cake\I18n\I18n;
use Intervention\Image\ImageManager;

class UsersController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->Auth->allow(['changePassword', 'add', 'token','fbLogin','twitterLogin','googlePlus','viewProfileSetting','forgotPassword','editProfileSetting','uploadProfilePhoto','uploadPoliceCertificate','uploadIdentificationFile','editBackgroundInformation','editSkillsInformation','getUserVehicleInformation','editVehicleInformation','editPersonalInformation','editAvailabilityInformation','editAvailabilityTime','logout','editAboutYouInformation','getReviewList','viewProfilePicture','viewAvailabilityTime','getBankDetail','viewAvailabilityTimeByDay','getUserInfo','resetPassword','accountActivation','getUserRow','userListByCategory','accountDelete','transactionList']);
        $this->loadComponent('Flash'); // Include the FlashComponent
    }
    
    /**
     * Create new user and return id plus JWT token
     */
     
    public function add()
    {
        if (!empty($this->request->data)) {
            foreach ($this->request->data as $key => $value) {
                $this->request->data[$key] = trim($value);
            }
        }
        
        $this->Crud->on('afterSave', function (Event $event) {
            if ($event->subject->created && $event->subject->success) {
                if ($this->request->data['role_id'] == 2) {
                    $roleId = USER_CLIENT_ROLE;
                } elseif ($this->request->data['role_id'] == 16) {
                    $roleId = USER_PRO_EXPERT_ROLE;
                } else {
                    $roleId = USER_CLIENT_ROLE;
                }
                $user_data = $this->Users->get($event->subject->entity->id); // Return user regarding id
				$token = JWT::encode(
                        [
                            'sub' => $event->subject->entity->id,
                            'exp' =>  time() + 604800
							],
                        Security::salt()
                    );
                $verification_code = substr(md5(uniqid()), 0, 20);
                $user_data->activation_code = $verification_code;
                $user_data->status = STATUS_INACTIVE;
                $user_data->role_id = $roleId;
				$user_data->token = $token;
                $this->Users->save($user_data);
				$user_id = $user_data->id;
				if (isset($user_id)) {
					$userDetail = TableRegistry::get('UserDetails');
					$userDetailNewData = $userDetail->newEntity();
					$userDetailNewData->user_id = $user_id;
					$userDetail->save($userDetailNewData);
					
					$userBankDetails = TableRegistry::get('UserBankDetails');
					$userBankNewData = $userBankDetails->newEntity();
					$userBankNewData->user_id = $user_id;
					$userBankDetails->save($userBankNewData);
				}				
                $email = $this->request->data['email'];
                $emailData = TableRegistry::get('EmailTemplates');
                $emailDataResult = $emailData->find()->where(['slug' => 'user_registration']);
                $emailContent = $emailDataResult->first();
                $activation_url = WEBSITE_PASSWORD_URL . 'en/users/activate/'. base64_encode($email).'/'.$verification_code;
                $activation_link    = $activation_url;
                $to = $email;
                $subject = $emailContent->subject;
                $mail_message_data = $emailContent->description;
                $activation_link    =' <a href="'.$activation_url.'" target="_blank" shape="rect">'.__("activationLink").'</a>';
                $mail_text_message = str_replace(array('{EMAIL}','{ACTIVATION_LINK}','{PASSWORD}'), array($email,$activation_link,$this->request->data['password']), $mail_message_data);
                $from = SITE_EMAIL;
				$mail_message = str_replace('{CONTENT}',$mail_text_message,$this->email_template());
				
                parent::sendEmail($from, $to, $subject, $mail_message);
                $this->set('data', [
                    'id' => base64_encode($event->subject->entity->id),
					'token' => $token,
					'message'=>__("pleaseCheckEmailAfterRegistrationSucessfull")
                ]);
                $this->Crud->action()->config('serialize.data', 'data');
            }
        });
        
        return $this->Crud->execute();
    }
    
    /**
     * Return JWT token if posted user credentials pass FormAuthenticate
     */
    public function token()
    {
        if ($this->request->is('post')) {
            $user = $this->Auth->identify();
            if (!$user) {
                throw new UnauthorizedException('Invalid email or password');
            }
            $token = JWT::encode(
                        [
                            'sub' => $user['id'],
                            'exp' =>  time() + 604800
                        ],
                        Security::salt()
                    );
            
            $user_data = $this->Users->get($user['id']); // Return user regarding id
            if (!empty($user_data->activation_code)) {
                $this->set([
                'success' => false,
                'data' => [
                    'code' =>422,
                    'message' =>__("pleaseActivateYourAcountByActivationLink")
                ],
                '_serialize' => ['success', 'data']
                ]);
                return ;
            } elseif (empty($user_data->activation_code) && $user_data->status == STATUS_INACTIVE) {
                $this->set([
                'success' => false,
                'data' => [
                    'code' =>422,
                    'message' =>__("accountBlockByAdministartor")
                ],
                '_serialize' => ['success', 'data']
                ]);
                return ;
            } elseif (empty($user_data->activation_code) && $user_data->status == STATUS_ACTIVE) {
                $user_data->token = $token;
                $user_data->last_login = date('Y-m-d H:i:s');
                $this->Users->save($user_data);
                $this->set([
                    'success' => true,
                    'data' => [
                        'token' => $token,
                        'userId' => base64_encode($user['id']),
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
    
    public function logout()
    {
        if ($this->request->is('get')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $user = $this->Auth->identify();
                if (!$user) {
                    throw new UnauthorizedException('Invalid username or password');
                }
                $user_data = $this->Users->get($user['id']); // Return user regarding id
                $user_data->token = null;
                $this->Users->save($user_data);
                $this->set([
                    'success' => true,
                    'data' => [
                        'message' =>__('logoutSuccessfully')
                    ],
                    '_serialize' => ['success', 'data']
                ]);
                return ;
            } else {
                $this->set([
                    'success' => false,
                    'data' => [
                        'message' =>__('invalidAccess')
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
    
    public function fbLogin()
    {
        if ($this->request->is('post')) {
            $userData = TableRegistry::get('Users');
            $usersTable = $userData->newEntity($this->request->data, ['validate' => 'facebooklogin']);
            if ($usersTable->errors()) {
                $this->set([
                    'success' => false,
                    'data' => [
                        'code' => 422,
                        'url' => h($this->request->here()),
                        'message' => count($usersTable->errors()).__('validationErrorsOccured'),
                        'error' => '',
                        'errorCount' => count($usersTable->errors()),
                        'errors' => $usersTable->errors(),
                        ],
                    '_serialize' => ['success', 'data']]);
                return ;
            }
            $facebookRowCount = 0;
            $emailRowCount = 0;
            $email = "";
            if (!empty($this->request->data['facebook_id'])) {
                $facebookId = trim($this->request->data['facebook_id']);
                $queryFacebook = $userData->find()->where(['facebook_id' => $facebookId,'status'=>STATUS_INACTIVE]);
                $facebookRowCount = $queryFacebook->count();
                if ($facebookRowCount != 0) {
                    $this->set([
                    'success' => false,
                    'data' => [
                        'code' =>422,
                        'message' =>__('accountBlockedByAdmin')
                    ],
                    '_serialize' => ['success', 'data']
                    ]);
                    return ;
                }
                $queryFacebook = $userData->find()->where(['facebook_id' => $facebookId,'status'=>STATUS_ACTIVE]);
                $facebookRowCount = $queryFacebook->count();
            }
            if (!empty($this->request->data['email'])) {
                $email = trim($this->request->data['email']);
                $queryEmail = $userData->find()->where(['email' => $email,'status'=>STATUS_INACTIVE]);
                $emailRowCount = $queryEmail->count();
                if ($emailRowCount != 0) {
                    $this->set([
                    'success' => false,
                    'data' => [
                        'code' =>422,
                        'message' =>__('accountBlockedByAdmin')
                    ],
                    '_serialize' => ['success', 'data']
                    ]);
                    return ;
                }
                
                
                $queryEmail = $userData->find()->where(['email' => $email,'status'=>STATUS_ACTIVE]);
                $emailRowCount = $queryEmail->count();
            }
            $firstName = (!empty(trim($this->request->data['first_name'])))? $this->request->data['first_name']:"";
            $lastName = (!empty(trim($this->request->data['last_name'])))? $this->request->data['last_name']:"";
            if ($facebookRowCount == 0 && $emailRowCount == 0) {
                $userNewData = $userData->newEntity();
                // $userNewData->username = $this->genreateRandomNickname($firstName);
                $userNewData->first_name = $firstName;
                $userNewData->last_name = $lastName;
                $userNewData->facebook_id = $facebookId;
                $userNewData->email = $email;
                $userNewData->status = STATUS_ACTIVE;
                $userNewData->last_login = date('Y-m-d H:i:s');
                $userNewData->created = date('Y-m-d H:i:s');
                $userNewData->modified = date('Y-m-d H:i:s');
                if ($userData->save($userNewData)) {
                    $id = $userNewData->id;
                    $token = JWT::encode(
                        [
                            'sub' => $id,
                            'exp' =>  time() + 604800
                        ],
                        Security::salt()
                    );
                    $user_data = $this->Users->get($id); // Return user regarding id
                    $user_data->token = $token;
                    $this->Users->save($user_data);
                    $this->set([
                        'success' => true,
                        'data' => [
                            'token' => $token,
                            'userId' => base64_encode($id),
                        ],
                        '_serialize' => ['success', 'data']
                    ]);
                    return ;
                }
            } elseif ($facebookRowCount == 1 && $emailRowCount == 1) {
                $result = $queryFacebook->toArray()[0];
                $id = $result->id;
                $token = JWT::encode(
                    [
                        'sub' => $id,
                        'exp' =>  time() + 604800
                    ],
                    Security::salt()
                );
                $user_data = $this->Users->get($id); // Return user regarding id
                $user_data->token = $token;
                $this->Users->save($user_data);
                $this->set([
                    'success' => true,
                    'data' => [
                        'token' => $token,
                        'userId' => base64_encode($id),
                    ],
                    '_serialize' => ['success', 'data']
                ]);
                return ;
            } elseif ($facebookRowCount == 1 && $emailRowCount == 0) {
                $result = $queryFacebook->toArray()[0];
                $id = $result->id;
                $token = JWT::encode(
                    [
                        'sub' => $id,
                        'exp' =>  time() + 604800
                    ],
                    Security::salt()
                );
                $user_data = $this->Users->get($id); // Return user regarding id
                $user_data->token = $token;
                $this->Users->save($user_data);
                $this->set([
                    'success' => true,
                    'data' => [
                        'token' => $token,
                        'userId' => base64_encode($id),
                    ],
                    '_serialize' => ['success', 'data']
                ]);
                return ;
            } elseif ($facebookRowCount == 0 && $emailRowCount == 1) {
                $result = $queryEmail->toArray()[0];
                $id = $result->id;
                $token = JWT::encode(
                    [
                        'sub' => $id,
                        'exp' =>  time() + 604800
                    ],
                    Security::salt()
                );
                $user_data = $this->Users->get($id); // Return user regarding id
                $user_data->facebook_id = $facebookId;
                $user_data->token = $token;
                $this->Users->save($user_data);
                $this->set([
                    'success' => true,
                    'data' => [
                        'token' => $token,
                        'userId' => base64_encode($id),
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
    
    public function genreateRandomNickname($name=null)
    {
        $userData = TableRegistry::get('Users');
        $queryUser = $userData->find()->where(['username LIKE' => $name]);
        $queryRowCount = $queryUser->count();
        if ($queryRowCount > 0) {
            $userName = $name.rand(0, 100);
            return $userName;
        }
        return $name;
    }
    
    public function twitterLogin()
    {
        if ($this->request->is('post')) {
            $userData = TableRegistry::get('Users');
            $usersTable = $userData->newEntity($this->request->data, ['validate' => 'twitterlogin']);
            if ($usersTable->errors()) {
                $this->set([
                    'success' => false,
                    'data' => [
                        'code' => 422,
                        'url' => h($this->request->here()),
                        'message' => count($usersTable->errors()).__('validationErrorsOccured'),
                        'error' => '',
                        'errorCount' => count($usersTable->errors()),
                        'errors' => $usersTable->errors(),
                        ],
                    '_serialize' => ['success', 'data']]);
                return ;
            }
            $twitterId = trim($this->request->data['twitter_id']);
            $name = trim($this->request->data['name']);
            $queryTwitter = $userData->find()->where(['twitter_id' => $twitterId,'status'=>STATUS_INACTIVE]);
            $twitterRowCount = $queryTwitter->count();
            if ($twitterRowCount > 0) {
                $this->set([
                'success' => false,
                'data' => [
                    'code' =>422,
                    'message' =>__('accountBlockedByAdmin')
                ],
                '_serialize' => ['success', 'data']
                ]);
                return ;
            }
            $queryTwitter = $userData->find()->where(['twitter_id' => $twitterId,'status'=>STATUS_ACTIVE]);
            $twitterRowCount = $queryTwitter->count();
            if ($twitterRowCount == 0) {
                $userNewData = $userData->newEntity();
                $userNewData->first_name = $name;
                $userNewData->twitter_id = $twitterId;
                $userNewData->status = STATUS_ACTIVE;
                $userNewData->last_login = date('Y-m-d H:i:s');
                $userNewData->created = date('Y-m-d H:i:s');
                $userNewData->modified = date('Y-m-d H:i:s');
                $userData->save($userNewData);
                $id = $userNewData->id;
                $token = JWT::encode(
                    [
                        'sub' => $id,
                        'exp' =>  time() + 604800
                    ],
                    Security::salt()
                );
                $user_data = $this->Users->get($id); // Return user regarding id
                $user_data->token = $token;
                $this->Users->save($user_data);
                $this->set([
                    'success' => true,
                    'data' => [
                        'token' => $token,
                        'userId' => base64_encode($id),
                    ],
                    '_serialize' => ['success', 'data']
                ]);
                return ;
            } else {
                $result = $queryTwitter->toArray()[0];
                $id = $result->id;
                $token = JWT::encode(
                    [
                        'sub' => $id,
                        'exp' =>  time() + 604800
                    ],
                    Security::salt()
                );
                $user_data = $this->Users->get($id); // Return user regarding id
                $user_data->token = $token;
                $this->Users->save($user_data);
                $this->set([
                    'success' => true,
                    'data' => [
                        'token' => $token,
                        'userId' => base64_encode($id),
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
    
    public function googlePlus()
    {
        if ($this->request->is('post')) {
            $userData = TableRegistry::get('Users');
            $usersTable = $userData->newEntity($this->request->data, ['validate' => 'googlpluslogin']);
            if ($usersTable->errors()) {
                $this->set([
                    'success' => false,
                    'data' => [
                        'code' => 422,
                        'url' => h($this->request->here()),
                        'message' => count($usersTable->errors()).__('validationErrorsOccured'),
                        'error' => '',
                        'errorCount' => count($usersTable->errors()),
                        'errors' => $usersTable->errors(),
                        ],
                    '_serialize' => ['success', 'data']]);
                return ;
            }
            $googleRowCount = 0;
            $emailRowCount = 0;
            $email = "";
            if (!empty($this->request->data['googleplus_id'])) {
                $googleId = trim($this->request->data['googleplus_id']);
                $queryGoogle = $userData->find()->where(['googleplus_id' => $googleId,'status'=>STATUS_INACTIVE]);
                $googleRowCount = $queryGoogle->count();
                if ($googleRowCount > 0) {
                    $this->set([
                    'success' => false,
                    'data' => [
                        'code' =>422,
                        'message' =>__('accountBlockedByAdmin')
                    ],
                    '_serialize' => ['success', 'data']
                    ]);
                    return ;
                }
                $queryGoogle = $userData->find()->where(['googleplus_id' => $googleId,'status'=>STATUS_ACTIVE]);
                $googleRowCount = $queryGoogle->count();
            }
            if (!empty($this->request->data['email'])) {
                $email = trim($this->request->data['email']);
                $queryEmail = $userData->find()->where(['email' => $email,'status'=>STATUS_INACTIVE]);
                $emailRowCount = $queryEmail->count();
                if ($emailRowCount > 0) {
                    $this->set([
                    'success' => false,
                    'data' => [
                        'code' =>422,
                        'message' =>__('accountBlockedByAdmin')
                    ],
                    '_serialize' => ['success', 'data']
                    ]);
                    return ;
                }
                
                
                $queryEmail = $userData->find()->where(['email' => $email,'status'=>STATUS_ACTIVE]);
                $emailRowCount = $queryEmail->count();
            }
            $firstName = (!empty($this->request->data['first_name']))? $this->request->data['first_name']:"";
            if ($googleRowCount == 0 && $emailRowCount == 0) {
                $userNewData = $userData->newEntity();
                $userNewData->first_name = $firstName;
                $userNewData->googleplus_id = $googleId;
                $userNewData->email = $email;
                $userNewData->status = STATUS_ACTIVE;
                $userNewData->last_login = date('Y-m-d H:i:s');
                $userNewData->created = date('Y-m-d H:i:s');
                $userNewData->modified = date('Y-m-d H:i:s');
                $userData->save($userNewData);
                $id = $userNewData->id;
                $token = JWT::encode(
                    [
                        'sub' => $id,
                        'exp' =>  time() + 604800
                    ],
                    Security::salt()
                );
                $user_data = $this->Users->get($id); // Return user regarding id
                $user_data->token = $token;
                $user_data->last_login = date('Y-m-d H:i:s');
                $user_data->modified = date('Y-m-d H:i:s');
                $this->Users->save($user_data);
                $this->set([
                    'success' => true,
                    'data' => [
                        'token' => $token,
                        'userId' => base64_encode($id),
                    ],
                    '_serialize' => ['success', 'data']
                ]);
                return ;
            } elseif ($googleRowCount == 1 && $emailRowCount == 1) {
                $result = $queryGoogle->toArray()[0];
                $id = $result->id;
                $token = JWT::encode(
                    [
                        'sub' => $id,
                        'exp' =>  time() + 604800
                    ],
                    Security::salt()
                );
                $user_data = $this->Users->get($id); // Return user regarding id
                $user_data->token = $token;
                $user_data->last_login = date('Y-m-d H:i:s');
                $user_data->modified = date('Y-m-d H:i:s');
                $this->Users->save($user_data);
                $this->set([
                    'success' => true,
                    'data' => [
                        'token' => $token,
                        'userId' => base64_encode($id),
                    ],
                    '_serialize' => ['success', 'data']
                ]);
                return ;
            } elseif ($googleRowCount == 1 && $emailRowCount == 0) {
                $result = $queryGoogle->toArray()[0];
                $id = $result->id;
                $token = JWT::encode(
                    [
                        'sub' => $id,
                        'exp' =>  time() + 604800
                    ],
                    Security::salt()
                );
                $user_data = $this->Users->get($id); // Return user regarding id
                $user_data->token = $token;
                $user_data->last_login = date('Y-m-d H:i:s');
                $user_data->modified = date('Y-m-d H:i:s');
                $this->Users->save($user_data);
                $this->set([
                    'success' => true,
                    'data' => [
                        'token' => $token,
                        'userId' => base64_encode($id),
                    ],
                    '_serialize' => ['success', 'data']
                ]);
                return ;
            } elseif ($googleRowCount == 0 && $emailRowCount == 1) {
                $result = $queryEmail->toArray()[0];
                $id = $result->id;
                $token = JWT::encode(
                    [
                        'sub' => $id,
                        'exp' =>  time() + 604800
                    ],
                    Security::salt()
                );
                $user_data = $this->Users->get($id); // Return user regarding id
                $user_data->googleplus_id = $googleId;
                $user_data->last_login = date('Y-m-d H:i:s');
                $user_data->modified = date('Y-m-d H:i:s');
                $user_data->token = $token;
                $this->Users->save($user_data);
                $this->set([
                    'success' => true,
                    'data' => [
                        'token' => $token,
                        'userId' => base64_encode($id),
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
    
    public function forgotPassword()
    { 
        if ($this->request->is('post')) {
            $userData = TableRegistry::get('Users');
            $usersTable = $userData->newEntity($this->request->data, ['validate' => 'forgotpassword']);
            if ($usersTable->errors()) {
                $this->set([
                    'success' => false,
                    'data' => [
                        'code' => 422,
                        'url' => h($this->request->here()),
                        'message' => count($usersTable->errors()).__('validationErrorsOccured'),
                        'error' => '',
                        'errorCount' => count($usersTable->errors()),
                        'errors' => $usersTable->errors(),
                        ],
                    '_serialize' => ['success', 'data']]);
                return ;
            }
            $email = $this->request->data['email'];
            if (!empty($email)) {
                $queryUserDetail = $userData->find()->where(['email' => $email]);
                $userRowCount = $queryUserDetail->count();
                if ($userRowCount==0) {
                    $usersTable->errors(['email' => ['_invalid'=>__('Email not exists in our record.')]]);
                    $this->set([
                    'success' => false,
                    'data' => [
                        'code' => 422,
                        'url' => h($this->request->here()),
                        'message' => count($usersTable->errors()).__('validationErrorsOccured'),
                        'error' => '',
                        'errorCount' => count($usersTable->errors()),
                        'errors' => $usersTable->errors(),
                        ],
                    '_serialize' => ['success', 'data']]);
                    return ;
                }
                
                
                
                $queryUserDetail = $userData->find()->where(['email' => $email,'status'=>STATUS_INACTIVE]);
                $userRowCount = $queryUserDetail->count();
                if ($userRowCount>0) {
                    $usersTable->errors(['email' => ['_invalid'=>__('accountNotActive')]]);
                    $this->set([
                    'success' => false,
                    'data' => [
                        'code' => 422,
                        'url' => h($this->request->here()),
                        'message' => count($usersTable->errors()).__('validationErrorsOccured'),
                        'error' => '',
                        'errorCount' => count($usersTable->errors()),
                        'errors' => $usersTable->errors(),
                        ],
                    '_serialize' => ['success', 'data']]);
                    return ;
                } else {
                    $queryUserDetail = $userData->find()->where(['email' => $email,'status'=>STATUS_ACTIVE]);
                    $userRowCount = $queryUserDetail->count();
                    $userRowDetail = $queryUserDetail->first();
                    if ($userRowCount> 0 && !empty($userRowDetail)) {
                        I18n::locale($this->request->session()->read('Config.language'));
                        $id = $userRowDetail->id;
                        $user_data = $this->Users->get($id);
                        $random_hash = md5(uniqid(rand(), true));
                        $user_data->activation_code = $random_hash;
                        $this->Users->save($user_data);
                        $activation_url = WEBSITE_PASSWORD_URL.$this->request->session()->read('Config.language').'/password-reset/'.$random_hash.'/'.base64_encode($id);
                        $emailData = TableRegistry::get('EmailTemplates');
                        $emailDataResult = $emailData->find()->where(['slug' => 'forgot_password']);
                        $emailContent = $emailDataResult->first();
                        $first_name = $userRowDetail->username;
                        $to = $userRowDetail->email;
                        $subject = $emailContent->subject;
                        $mail_message_data = $emailContent->description;
                        $from = SITE_EMAIL;
                        $this->_init_language();
                        $activation_link    =' <a href="'.$activation_url.'" target="_blank" shape="rect">'.__('passwordRestLink').'</a>';
                        $mail_text_message = str_replace(array('{NAME}','{ACTIVATION_LINK}'), array($first_name,$activation_link), $mail_message_data);
						$mail_message = str_replace('{CONTENT}',$mail_text_message,$this->email_template());
						parent::sendEmail($from, $to, $subject, $mail_message);
                        
                        $this->set([
                        'success' => true,
                        'data' => [
                            'code' =>422,
                            'message' =>__('passwordResetLink')
                        ],
                        '_serialize' => ['success', 'data']
                        ]);
                        return ;
                    } else {
                        $this->set([
                        'success' => false,
                        'data' => [
                            'code' =>422,
                            'message' =>__('emailNotExists')
                        ],
                        '_serialize' => ['success', 'data']
                        ]);
                        return ;
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
    /**
     *Get the profile detail of user
    */
    public function getUserInfo()
    {
        if ($this->request->is('post')) {
            $this->loadModel('Users');
            $userId = $this->request->data['userId'];
            $token = $this->request->data['token'];
            $this->Users->hasOne('UserDetails', [
				'className' => 'UserDetails',
				'foreignKey' => 'user_id'
			]);
			$user = $this->Users->find()
                                ->where(['Users.id' =>base64_decode($userId),'Users.token'=>$token])
								->contain(['UserDetails'])
                                ->first()->toArray();
								
            $userDetail = array();
            if (!empty($user)) {
                $userDetail = $user;
            }
            $this->set([
                    'success' => true,
                    'data' => $userDetail,
                    '_serialize' => ['data','success']
                ]);
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
	public function getUserRow()
    {
        if ($this->request->is('post')) {
            $this->loadModel('Users');
            $userId = $this->request->data['userId'];    
            $user = $this->Users->find()
                                ->where(['id' =>base64_decode($userId)])
                                ->first();
            $userDetail = array();
            if (!empty($user)) {
                $userDetail = $user;
            }
			if(isset($userDetail['profile_image']) && !empty($userDetail['profile_image'])){			
				$filename =  WWW_ROOT . USERS_FULL_DIR . DS . USERS_144X137_DIR . DS .$userDetail['profile_image'];
				if (file_exists($filename)) {
					$user_profile_img = $userDetail['profile_image'];
				}	
				
				unset($userDetail['profile_image']);	
				
				if (isset($user_profile_img) && !empty($user_profile_img)){
					$userDetail['profile_image'] = $user_profile_img;						
				} else {
					$userDetail['profile_image'] = " ";
				}
			}
            $this->set([
                    'success' => true,
                    'data' => $userDetail,
                    '_serialize' => ['data','success']
                ]);
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
     *Get the profile detail of user
    */
    public function viewProfileSetting()
    {
        if ($this->request->is('get')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
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
                $this->Users->hasMany('UserAvailabilities');
                $this->Users->belongsToMany('Categories');
                $user = $this->Users->get($userId, ['contain'=>['UserDetails','UserBankDetails','Categories','UserAvailabilities']]);
                $userDetail = array();
                if (!empty($user)) {
                    $userDetail['user_id'] = $user->id;
                    $userDetail['first_name'] = $user->first_name;
                    $userDetail['last_name'] = $user->last_name;
                    $userDetail['email'] = $user->email;
                    $userDetail['location'] = $user->location;
                    $userDetail['profile_image'] = $user->profile_image;
                    $userDetail['rating'] = $user->rating;
                    $userDetail['dob'] = $user->dob;
					$userDetail['gender'] = $user->gender;
					$userDetail['phone_number'] = $user->phone_number;
					$userDetail['subscribe_newsletter'] = $user->subscribe_newsletter;
				
                    if (!empty($user->profile_image)) {
									
						if (file_exists(WWW_ROOT . USERS_FULL_DIR . DS . USERS_144X137_DIR . DS .$user->profile_image)) {
							$userDetail['thumb144X137'] = SITE_URL . USERS_FULL_DIR . DS . USERS_144X137_DIR . DS .  $user->profile_image;
						}else{						
							$userDetail['thumb144X137'] = " ";
						}
						
						if (file_exists(WWW_ROOT . USERS_FULL_DIR . DS . USERS_154X138_DIR . DS .$user->profile_image)) {
							$userDetail['thumb154X138'] = SITE_URL . USERS_FULL_DIR . DS . USERS_154X138_DIR . DS .  $user->profile_image;
						}else{						
							$userDetail['thumb154X138'] = " ";
						}
						
						if (file_exists(WWW_ROOT . USERS_FULL_DIR . DS . USERS_ORIGINAL_DIR . DS .$user->profile_image)) {
							$userDetail['original'] = SITE_URL . USERS_FULL_DIR . DS . USERS_ORIGINAL_DIR . DS .  $user->profile_image;
						}else{						
							$userDetail['original'] = " ";
						}	
                    } else {
                        $userDetail['original'] = "";
                        $userDetail['thumb144X137']="";
                        $userDetail['thumb154X138']="";
                    }
                    //if ($user['role_id'] == USER_PRO_EXPERT_ROLE) {
                        $userDetail['user_detail']['availability'] = 0;
                        $userDetail['user_detail']['availability_location'] = "";
                        $userDetail['user_detail']['community_description'] = "";
                        $userDetail['user_detail']['interest_description'] = "";
                        $userDetail['user_detail']['confident_with_work_description'] = "";
                        $userDetail['user_detail']['interested_become_worker'] = "";
                        $userDetail['user_detail']['tagline_category'] = "";
                        $userDetail['user_detail']['expertise_level'] = "";
                        $userDetail['user_detail']['hourly_rate'] = "";
                        $userDetail['user_bank_detail']['iban'] = "";
                        $userDetail['user_bank_detail']['bic'] = "";
                        $userDetail['user_bank_detail']['name'] = "";
                        $userDetail['user_bank_detail']['swift_code'] = "";
                        $userDetail['user_bank_detail']['account_number'] = "";
                        $userDetail['user_bank_detail']['paypal_email'] = "";
                        $userDetail['categories'] = array();
                        $userDetail['user_availabilities'] = array();
                        if (isset($user->user_detail->police_certificate_file) && !empty($user->user_detail->police_certificate_file)) {                           
							$userDetail['user_detail']['police_certificate_file']=SITE_URL . USER_POLICE_CERTIFICATE_FILE . DS . $user->user_detail->police_certificate_file;
                        }
						if (isset($user->user_detail->user_indentification_file) && !empty($user->user_detail->user_indentification_file)) {						
							$userDetail['user_detail']['user_indentification_file'] = SITE_URL . USER_IDENTIFICATION_FILE . DS .  $user->user_detail->user_indentification_file;
                        }
						if (isset($user->user_detail->availability) && !empty($user->user_detail->availability)) {
                            $userDetail['user_detail']['availability'] = $user->user_detail->availability;
                        }
                        if (isset($user->user_detail->availability_location) && !empty($user->user_detail->availability_location)) {
                            $userDetail['user_detail']['availability_location'] = $user->user_detail->availability_location;
                        }
                        if (isset($user->user_detail->community_description) && !empty($user->user_detail->community_description)) {
                            $userDetail['user_detail']['community_description'] = $user->user_detail->community_description;
                        }
                        if (isset($user->user_detail->interest_description) && !empty($user->user_detail->interest_description)) {
                            $userDetail['user_detail']['interest_description'] = $user->user_detail->interest_description;
                        }
                        if (isset($user->user_detail->confident_with_work_description) && !empty($user->user_detail->confident_with_work_description)) {
                            $userDetail['user_detail']['confident_with_work_description'] = $user->user_detail->confident_with_work_description;
                        }
                        if (isset($user->user_detail->interested_become_worker) && !empty($user->user_detail->interested_become_worker)) {
                            $userDetail['user_detail']['interested_become_worker'] = $user->user_detail->interested_become_worker;
                        }
                        if (isset($user->user_detail->expertise_level) && !empty($user->user_detail->expertise_level)) {
                            $userDetail['user_detail']['expertise_level'] = $user->user_detail->expertise_level;
                        }
                        if (isset($user->user_detail->tagline_category) && !empty($user->user_detail->tagline_category)) {
                            $userDetail['user_detail']['tagline_category'] = $user->user_detail->tagline_category;
                        }
                        if (isset($user->user_detail->hourly_rate) && !empty($user->user_detail->hourly_rate)) {
                            $userDetail['user_detail']['hourly_rate'] = $user->user_detail->hourly_rate;
                        }
                        if (isset($user->user_bank_detail->iban) && !empty($user->user_bank_detail->iban)) {
                            $userDetail['user_bank_detail']['iban'] = $user->user_bank_detail->iban;
                        }
                        if (isset($user->user_bank_detail->bic) && !empty($user->user_bank_detail->bic)) {
                            $userDetail['user_bank_detail']['bic'] = $user->user_bank_detail->bic;
                        }
                        if (isset($user->user_bank_detail->name) && !empty($user->user_bank_detail->name)) {
                            $userDetail['user_bank_detail']['name'] = $user->user_bank_detail->name;
                        }
                        if (isset($user->user_bank_detail->swift_code) && !empty($user->user_bank_detail->swift_code)) {
                            $userDetail['user_bank_detail']['swift_code'] = $user->user_bank_detail->swift_code;
                        }
                        if (isset($user->user_bank_detail->account_number) && !empty($user->user_bank_detail->account_number)) {
                            $userDetail['user_bank_detail']['account_number'] = $user->user_bank_detail->account_number;
                        }
                        if (isset($user->user_bank_detail->paypal_email) && !empty($user->user_bank_detail->paypal_email)) {
                            $userDetail['user_bank_detail']['paypal_email'] = $user->user_bank_detail->paypal_email;
                        }
                        if (isset($user->categories) && !empty($user->categories)) {
                            foreach ($user->categories as $category) {
                                $dataCategory['category_id'] = $category->id;
                                $dataCategory['category_name'] = $category->name;
                                $dataResultCategories[] = $dataCategory;
                            }
                            $userDetail['categories'] = $dataResultCategories;
                        }
                        if (isset($user->user_availabilities) && !empty($user->user_availabilities)) {
                            foreach ($user->user_availabilities as $user_availabilities) {
                                $dataAvailability['day'] = $user_availabilities->day;
                                $dataAvailability['morning_time'] = $user_availabilities->morning_time;
                                $dataAvailability['afternoon_time'] = $user_availabilities->afternoon_time;
                                $dataAvailability['evening_time'] = $user_availabilities->evening_time;
                                $dataResultAvailability[] = $dataAvailability;
                            }
                            $userDetail['user_availabilities'] = $dataResultAvailability;
                        }
                    //}
                }
                    
                 
                $this->set([
                    'success' => true,
                    'data' => $userDetail,
                    '_serialize' => ['data','success']
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
    
    public function viewAvailabilityTime()
    {
        if ($this->request->is('get')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $user = $this->Auth->identify();
                $userId = $user['id'];
                $this->loadModel('Users');
                

                $this->Users->hasMany('UserAvailabilities');
                $user = $this->Users->get($userId, ['contain'=>['UserAvailabilities']]);
                
                $userDetail = array();
                if (!empty($user)) {
                    $userDetail['user_availabilities'] = array();
                    
                    if (isset($user->user_availabilities) && !empty($user->user_availabilities)) {
                        foreach ($user->user_availabilities as $user_availabilities) {
                            $dataAvailability['day'] = $user_availabilities->day;
                            $dataAvailability['morning_time'] = $user_availabilities->morning_time;
                            $dataAvailability['afternoon_time'] = $user_availabilities->afternoon_time;
                            $dataAvailability['evening_time'] = $user_availabilities->evening_time;
                            $dataResult[] = $dataAvailability;
                        }
                        $userDetail['user_availabilities'] = $dataResult;
                    }
                }
                
                $this->set([
                    'success' => true,
                    'data' => $userDetail,
                    '_serialize' => ['data','success']
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
    
    public function viewAvailabilityTimeByDay()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $user = $this->Auth->identify();
                $userId = $user['id'];
                $user = $this->Users->get($userId);
                if (empty($this->request->data['day'])) {
                    $user->errors(['day' => [__('dayIsRequired')]]);
                }
                
                if (isset($this->request->data['day']) && !empty($this->request->data['day'])) {
                    $day = array('sun','mon','tue','wed','thu','fri','sat');
                    if (!in_array($this->request->data['day'], $day)) {
                        $user->errors(['day' => [__('invalidDay')]]);
                    }
                }
                if ($user->errors()) {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' => count($user->errors()).__('validationErrorsOccured'),
                            'error' => '',
                            'errorCount' => count($user->errors()),
                            'errors' => $user->errors(),
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
                $this->loadModel('UserAvailabilities');
                $exists = $this->UserAvailabilities->exists(['UserAvailabilities.user_id' => $userId,'day'=>$this->request->data['day']]);
                $UserAvailabilitiesArray = array();
                if ($exists) {
                    $UserAvailabilitiesDetail = $this->UserAvailabilities->find()
                                ->where(['user_id' => $userId,'day'=>$this->request->data['day']])
                                ->first();
                                
                    
                    $UserAvailabilitiesArray['day'] = $UserAvailabilitiesDetail['day'];
                    $UserAvailabilitiesArray['morning_time'] = $UserAvailabilitiesDetail['morning_time'];
                    $UserAvailabilitiesArray['afternoon_time'] = $UserAvailabilitiesDetail['afternoon_time'];
                    $UserAvailabilitiesArray['evening_time'] = $UserAvailabilitiesDetail['evening_time'];
                    $this->set([
                        'success' => true,
                        'UserAvailabilities' => $UserAvailabilitiesArray,
                        '_serialize' => ['UserAvailabilities','success']
                    ]);
                    
                    return ;
                } else {
                    $this->set([
                    'success' => true,
                    'data' => $UserAvailabilitiesArray,
                    '_serialize' => ['data','success']
                    ]);
                    
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
     *Get the profile detail of user
    */
    public function editProfileSetting()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $getUser = $this->Auth->identify();
                $id = $getUser['id'];
                $this->Users->hasOne('UserDetails', [
                'className' => 'UserDetails',
                'foreignKey' => 'user_id'
                ]);
                $user = $this->Users->get($id, ['contain'=>['UserDetails']]);
				$lat = 0;
				$long = 0;
				
                if (isset($this->request->data['interested_become_worker']) && !empty($this->request->data['interested_become_worker'])) {
                    $this->request->data['user_detail']['interested_become_worker'] = $this->request->data['interested_become_worker'];
                    unset($this->request->data['interested_become_worker']);
                }
				if(isset($this->request->data['location']) && !empty($this->request->data['location'])){

					$address = $this->request->data['location'];
					$formattedAddr = str_replace(' ','+',$address);
					$geocode = file_get_contents('http://maps.google.com/maps/api/geocode/json?address='.$formattedAddr.'&sensor=false');

					$output= json_decode($geocode);

					if(isset($output->results[0]->geometry->location->lat) && isset($output->results[0]->geometry->location->lng)){
						$lat = $output->results[0]->geometry->location->lat;
						$long = $output->results[0]->geometry->location->lng;
					}
					$this->request->data['latitude'] = $lat;
					$this->request->data['longitude'] = $long;
				}
				
				
				if(isset($this->request->data['subscribe_newsletter']) && !empty($this->request->data['subscribe_newsletter'])){
					
					$this->request->data['subscribe_newsletter'] = $this->request->data['subscribe_newsletter'];
				}
				
				
	
                $user = $this->Users->patchEntity($user, $this->request->data, ['validate'=>'editprofile']);
                if ($this->Users->save($user)) {
                    $this->set([
                                    'success' => true,
                                    'data' => [
                                        'message' =>__('profileSettingUpdatedSuccessfully'),
                                    ],
                                    '_serialize' => ['data','success']
                                ]);
                    return ;
                } else {
                    if ($user->errors()) {
                        $this->set([
                            'success' => false,
                            'data' => [
                                'code' => 422,
                                'url' => h($this->request->here()),
                                'message' => count($user->errors()).__('validationErrorsOccured'),
                                'error' => '',
                                'errorCount' => count($user->errors()),
                                'errors' => $user->errors(),
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
     *Update the Background Information  of user
    */
    public function editBackgroundInformation()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $getUser = $this->Auth->identify();
           /*      if ($getUser['role_id'] == USER_PRO_EXPERT_ROLE) {
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
                } */
                
                $id = $getUser['id'];
                $this->Users->hasOne('UserDetails', [
                'className' => 'UserDetails',
                'foreignKey' => 'user_id'
                ]);
                $this->Users->hasOne('UserBankDetails', [
                'className' => 'UserBankDetails',
                'foreignKey' => 'user_id'
                ]);
                
                $user = $this->Users->get($id, ['contain'=>['UserDetails','UserBankDetails']]);
                if (empty($this->request->data['first_name'])) {
                    $user->errors(['first_name' => ['_empty'=>__('firstNameRequired')]]);
                } else {
                    $firstNameLength = strlen(trim($this->request->data['first_name']));
                    if ($firstNameLength < 4 || $firstNameLength > 100) {
                        $user->errors(['first_name' => ['length'=>__('firstNameBetween4To100')]]);
                    }
                }
                if (empty($this->request->data['last_name'])) {
                    $user->errors(['last_name' => ['_empty'=>__('lastNameRequired')]]);
                } else {
                    $lastNameLength = strlen(trim($this->request->data['last_name']));
                    if ($lastNameLength < 4 || $lastNameLength > 100) {
                        $user->errors(['last_name' => ['length'=>__('lastNameBetween4To100')]]);
                    }
                }
                if (empty($this->request->data['paypal_email'])) {
                    $user->errors(['paypal_email' => ['_empty'=>__('paypalEmailIsRequired')]]);
                } else {
                    if (!filter_var($this->request->data['paypal_email'], FILTER_VALIDATE_EMAIL)) {
                        $user->errors(['paypal_email' => ['validFormat'=>__('paypalEmailIsRequired')]]);
                    }
                }
                
                if ($user->errors()) {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' => count($user->errors()).__('validationErrorsOccured'),
                            'error' => '',
                            'errorCount' => count($user->errors()),
                            'errors' => $user->errors(),
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
                if (isset($this->request->data['account_number']) && !empty($this->request->data['account_number'])) {
                    $this->request->data['user_bank_detail']['account_number'] = $this->request->data['account_number'];
                    unset($this->request->data['account_number']);
                }
                if (isset($this->request->data['name']) && !empty($this->request->data['name'])) {
                    $this->request->data['user_bank_detail']['name'] = $this->request->data['name'];
                    unset($this->request->data['name']);
                }
                if (isset($this->request->data['swift_code']) && !empty($this->request->data['swift_code'])) {
                    $this->request->data['user_bank_detail']['swift_code'] = $this->request->data['swift_code'];
                    unset($this->request->data['swift_code']);
                }
                if (isset($this->request->data['iban']) && !empty($this->request->data['iban'])) {
                    $this->request->data['user_bank_detail']['iban'] = $this->request->data['iban'];
                    unset($this->request->data['iban']);
                }
                if (isset($this->request->data['bic']) && !empty($this->request->data['bic'])) {
                    $this->request->data['user_bank_detail']['bic'] = $this->request->data['bic'];
                    unset($this->request->data['bic']);
                }
                if (isset($this->request->data['paypal_email']) && !empty($this->request->data['paypal_email'])) {
                    $this->request->data['user_bank_detail']['paypal_email'] = $this->request->data['paypal_email'];
                    unset($this->request->data['paypal_email']);
                }
                $user = $this->Users->patchEntity($user, $this->request->data);
                
                if ($this->Users->save($user)) {
                    $this->set([
                                    'success' => true,
                                    'data' => [
                                        'message' =>__('backgroundInformationUpdatedSuccessfully'),
                                    ],
                                    '_serialize' => ['data','success']
                                ]);
                    return ;
                } else {
					
                    if ($user->errors()) {
                        $this->set([
                            'success' => false,
                            'data' => [
                                'code' => 422,
                                'url' => h($this->request->here()),
                                'message' => count($user->errors()).__('validationErrorsOccured'),
                                'error' => '',
                                'errorCount' => count($user->errors()),
                                'errors' => $user->errors(),
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
     *Update the Background Information  of user
    */
    public function editSkillsInformation()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            // echo $checkAuth;die;
            if ($checkAuth) {
                $getUser = $this->Auth->identify();
               /*  if ($getUser['role_id'] == USER_PRO_EXPERT_ROLE) {
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
                } */
                
                
                $id = $getUser['id'];
                $this->Users->hasOne('UserDetails', [
                'className' => 'UserDetails',
                'foreignKey' => 'user_id'
                ]);
                $this->Users->belongsToMany('Categories');
                $user = $this->Users->get($id, ['contain'=>['UserDetails']]);
                
                
                if (!isset($this->request->data['hourly_rate']) || empty($this->request->data['hourly_rate'])) {
                    $user->errors(['hourly_rate' => ['_empty'=>__('hourlyRateRequired')]]);
                }
                if (!isset($this->request->data['tagline_category']) || empty($this->request->data['tagline_category'])) {
                    $user->errors(['tagline_category' => ['_empty'=>__('categoryTaglineRequired')]]);
                }
                if (!isset($this->request->data['expertise_level']) || empty($this->request->data['expertise_level'])) {
                    $user->errors(['expertise_level' => ['_empty'=>__('expertiseLevelRequired')]]);
                }
                if (!isset($this->request->data['categories']) || empty($this->request->data['categories'])) {
                    $user->errors(['categories' => ['_empty'=>__('selectYourSkills')]]);
                }
                if (isset($this->request->data['expertise_level']) && !empty($this->request->data['expertise_level'])) {
                    $expertiseLevel = array('entry_level','intermediate','expert');
                    if (in_array($this->request->data['expertise_level'], $expertiseLevel)) {
                        $this->request->data['user_detail']['expertise_level'] = $this->request->data['expertise_level'];
                        unset($this->request->data['expertise_level']);
                    } else {
                        $user->errors(['expertise_level' => ['_invalid'=>__('invalidExpertiseLevel')]]);
                    }
                }
                if (isset($this->request->data['categories']) && !empty($this->request->data['categories'])) {
                    $re = '/^\d+(?:,\d+)*$/';
                    if (preg_match($re, $this->request->data['categories'])) {
                        $categories = $this->request->data['categories'];
                        unset($this->request->data['categories']);
                        if (!empty($categories)) {
                            $categoriesArray = explode(",", $categories);
                            $data = array();
                            $this->loadModel('Categories');
                            $categoryError = false;
                            
                            foreach ($categoriesArray as $category) {
                                $exists = $this->Categories->exists(['Categories.id' => $category,'Categories.parent_id != 0']);
                                if ($exists) {
                                    $category;
                                    $data[] = $category;
                                } else {
                                    $categoryError = true;
                                    break;
                                }
                            }
                            
                            if (!$categoryError) {
                                $this->request->data['categories']['_ids'] = $data;
                            } else {
                                $user->errors(['categories' => ['_invalid'=>__('invalidCategoryIds')]]);
                            }
                        }
                    } else {
                        $user->errors(['categories' => ['_invalid'=>__('invalidCommaSepratedString')]]);
                    }
                }
                if ($user->errors()) {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' => count($user->errors()).__('validationErrorsOccured'),
                            'error' => '',
                            'errorCount' => count($user->errors()),
                            'errors' => $user->errors(),
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
                if (isset($this->request->data['hourly_rate']) && !empty($this->request->data['hourly_rate'])) {
                    $this->request->data['user_detail']['hourly_rate'] = $this->request->data['hourly_rate'];
                    unset($this->request->data['hourly_rate']);
                }
                if (isset($this->request->data['tagline_category']) && !empty($this->request->data['tagline_category'])) {
                    $this->request->data['user_detail']['tagline_category'] = $this->request->data['tagline_category'];
                    unset($this->request->data['tagline_category']);
                }
                
                $user = $this->Users->patchEntity($user, $this->request->data);
                if ($this->Users->save($user)) {
                    $this->set([
                                    'success' => true,
                                    'data' => [
                                        'message' =>__('skillsUpdatedSuccessfully'),
                                    ],
                                    '_serialize' => ['data','success']
                                ]);
                    return ;
                } else {
                    if ($user->errors()) {
                        $this->set([
                            'success' => false,
                            'data' => [
                                'code' => 422,
                                'url' => h($this->request->here()),
                                'message' => count($user->errors()).__('validationErrorsOccured'),
                                'error' => '',
                                'errorCount' => count($user->errors()),
                                'errors' => $user->errors(),
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
     * Upload the photo
    */
    public function uploadProfilePhoto()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                if (isset($this->request->data['files'][0]) && !empty($this->request->data['files'][0])) {
                    $this->request->data['image'] = $this->request->data['files'][0];
                    unset($this->request->data['files']);
                }
                $user = $this->Auth->identify();
                $userID = $user['id'];
                
                if (!empty($this->request->data['image']['tmp_name'])) {
                    ############### Image uploading code start here ###################
                    $pImage = $this->request->data['image'];
                    
                    $allowed    =    array('image/gif','image/jpeg','image/jpg','image/png');// extensions are allowe
                    if (!in_array($pImage["type"], $allowed)) { // check the extension of document
                        $errors[] = ['extension'=>['_required'=>'Only pdf, word files allowed']];
                    }
                    if ($pImage['size'] > 10485760) { // check the size of Curruculum Vitae
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
							'_serialize' => ['success', 'data']
						]);
                        return ;
                    }
                
                    if ((isset($pImage['tmp_name'])) && $pImage['tmp_name'] != '') {
                        if ($pImage['tmp_name'] != '' && !empty($user['profile_image']) && file_exists(WWW_ROOT . USERS_FULL_DIR . DS . USERS_144X137_DIR . DS .$user['profile_image'])) {
                            unlink(WWW_ROOT . USERS_FULL_DIR . DS . USERS_144X137_DIR . DS . $user['profile_image']);
                        }
                        
                        if ($pImage['tmp_name'] != '' && !empty($user['profile_image']) && file_exists(WWW_ROOT . USERS_FULL_DIR . DS . USERS_154X138_DIR . DS . $user['profile_image'])) {
                            unlink(WWW_ROOT . USERS_FULL_DIR . DS . USERS_154X138_DIR . DS . $user['profile_image']);
                        }
                        
                        if ($pImage['tmp_name'] != '' && !empty($user['profile_image']) && file_exists(WWW_ROOT . USERS_FULL_DIR . DS . USERS_ORIGINAL_DIR . DS . $user['profile_image'])) {
                            unlink(WWW_ROOT . USERS_FULL_DIR . DS . USERS_ORIGINAL_DIR . DS . $user['profile_image']);
                        }
                    
                        $manager = new ImageManager();
                        $file = $pImage;
                        $random = rand(1, 99999);
                        $temp        =    explode(".", $pImage["name"]);
                        $extension    =    end($temp);
                        $filename    =    'profile_image_'.microtime(true).'.'.$extension;
                        
                        // Tine
                        $path_144X137 = WWW_ROOT . USERS_FULL_DIR . DS . USERS_144X137_DIR . DS . $filename;
                        $manager->make($pImage["tmp_name"])->fit(120)->resize(144, 137)->save($path_144X137);
                        // Thumbnail
                        $path_154X138= WWW_ROOT . USERS_FULL_DIR . DS . USERS_154X138_DIR . DS  . $filename;
                        $manager->make($pImage["tmp_name"])->fit(152)->resize(154, 138)->save($path_154X138);
                        // Original
                        $path_original = WWW_ROOT . USERS_FULL_DIR . DS . USERS_ORIGINAL_DIR . DS . $filename;
                        $manager->make($pImage["tmp_name"])->save($path_original);

                        $usersTable = TableRegistry::get('Users');
                        
                        $query = $usersTable->find()->where(['id' => $userID]);
                        $result = $query->toArray()[0];
                        $id = $result->id;
                        $query->update()
                        ->set(['profile_image' => $filename,'modified'=>date('Y-m-d H:i:s')])
                        ->where(['id' => $userID])
                        ->execute();
                        
                        $this->set([
                            'success' => true,
                            'data' => [
                                'message' =>__('uploadingDone'),
                                'original'=>SITE_URL . USERS_FULL_DIR . DS . USERS_ORIGINAL_DIR . DS . $filename,
                                'thumb144X137'=>SITE_URL . USERS_FULL_DIR . DS . USERS_144X137_DIR . DS . $filename,
                                'thumb154X138'=>SITE_URL . USERS_FULL_DIR . DS . USERS_154X138_DIR . DS . $filename,
                            ],
                            '_serialize' => ['data','success']
                        ]);
                    }
                    
                ############### Image uploading code end here ###################
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
    
    public function uploadPoliceCertificate()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $user = $this->Auth->identify();
                if ($user['role_id'] == USER_PRO_EXPERT_ROLE) {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' =>__('youAreNotAuthrizedForThis'),
                            'error' => '',
                            'errorCount' => 1,
                            'errors' => __('youAreNotAuthrizedForThis'),
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
                $userID = $user['id'];
                
                if (isset($this->request->data['files'][0]) && !empty($this->request->data['files'][0])) {
                    $this->request->data['file'] = $this->request->data['files'][0];
                    unset($this->request->data['files']);
                }
                
                
                if (!empty($this->request->data['file']['tmp_name'])) {
                    $policeCertificate = $this->request->data['file'];
                    $allowed    =    array('docx','pdf','doc','txt','DOCX','PDF','DOC','TXT');// extensions are allowe
                    $temp        =    explode(".", $policeCertificate["name"]);
                    $extension    =    end($temp);
                    
                    if (!in_array($extension, $allowed)) { // check the extension of document
                        $errors[] = ['extension'=>['_required'=>'Only pdf, word files allowed']];
                    }
                    if ($policeCertificate['size'] > 10485760) { // check the size of Curruculum Vitae
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
                    
                    
                    $fileName    =    'police_certificate_'.microtime(true).'.'.$extension;
                    if (move_uploaded_file($policeCertificate['tmp_name'], WWW_ROOT . USER_POLICE_CERTIFICATE_FILE . DS . $fileName)) {
                        $userDetail = TableRegistry::get('UserDetails');
                        $queryUserDetail = $userDetail->find()->where(['user_id' =>$userID]);
                        $userDetailDataArray = $queryUserDetail->toArray();
                        $userDetailDataArray = array_shift($userDetailDataArray);
                        $userDetailRowCount = $queryUserDetail->count();
                        if ($userDetailRowCount>0) {
                            if (isset($userDetailDataArray)  && !empty($userDetailDataArray['police_certificate_file']) && file_exists(WWW_ROOT . USER_POLICE_CERTIFICATE_FILE . DS . $userDetailDataArray['police_certificate_file'])) {
                                unlink(WWW_ROOT . USER_POLICE_CERTIFICATE_FILE . DS . $userDetailDataArray['police_certificate_file']);
                            }
                        
                            $query  = $userDetail->query();
                            $data = $query->update()
                                ->set(['police_certificate_file' => $fileName])
                                ->where(['user_id' =>$userID])
                                ->execute();
                                
                            $this->set([
                                    'success' => true,
                                    'data' => [
                                        'message' =>__('uploadingDone'),
                                        'name'=>SITE_URL.USER_POLICE_CERTIFICATE_FILE.DS.$fileName
                                    ],
                                    '_serialize' => ['data','success']
                                ]);
                        } else {
                            $userDetailNewData = $userDetail->newEntity();
                            $userDetailNewData->user_id = $userID;
                            $userDetailNewData->police_certificate_file = $fileName;
                            if ($userDetail->save($userDetailNewData)) {
                                $this->set([
                                    'success' => true,
                                    'data' => [
                                        'message' =>__('uploadingDone'),
                                        'name'=>SITE_URL.USER_POLICE_CERTIFICATE_FILE.DS.$fileName
                                    ],
                                    '_serialize' => ['data','success']
                                ]);
                            }
                        }
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
    
    public function uploadIdentificationFile()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $user = $this->Auth->identify();
                if ($user['role_id'] == USER_PRO_EXPERT_ROLE) {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' =>__('youAreNotAuthrizedForThis'),
                            'error' => '',
                            'errorCount' => 1,
                            'errors' => __('youAreNotAuthrizedForThis'),
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
                $userID = $user['id'];
                if (isset($this->request->data['files'][0]) && !empty($this->request->data['files'][0])) {
                    $this->request->data['file'] = $this->request->data['files'][0];
                    unset($this->request->data['files']);
                }
                
                if (!empty($this->request->data['file']['tmp_name'])) {
                    $identificationFile = $this->request->data['file'];
                    $allowed    =    array('docx','pdf','doc','jpg','jpeg','gif','png','JPG','JPEG','GIF','PNG','PSD','psd','DOCX','PDF','DOC','TXT','txt');// extensions are allowe
                    $temp        =    explode(".", $identificationFile["name"]);
                    $extension    =    end($temp);
                    
                    if (!in_array($extension, $allowed)) { // check the extension of document
                        $errors[] = ['extension'=>['_required'=>'Only pdf, word,pic,psd files allowed']];
                    }
                    if ($identificationFile['size'] > 10485760) { // check the size of Curruculum Vitae
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
                    
                    $temp        =    explode(".", $identificationFile["name"]);
                    $extension    =    end($temp);
                    $fileName    =    'indentification_'.microtime(true).'.'.$extension;
                    if (move_uploaded_file($identificationFile['tmp_name'], WWW_ROOT . USER_IDENTIFICATION_FILE . DS . $fileName)) {
                        $userDetail = TableRegistry::get('UserDetails');
                        $queryUserDetail = $userDetail->find()->where(['user_id' =>$userID]);
                        $userDetailDataArray = $queryUserDetail->toArray();
                        $userDetailDataArray = array_shift($userDetailDataArray);
                        $userDetailRowCount = $queryUserDetail->count();
                        if ($userDetailRowCount>0) {
                            if (isset($userDetailDataArray)  && !empty($userDetailDataArray['user_indentification_file']) && file_exists(WWW_ROOT . USER_IDENTIFICATION_FILE . DS . $userDetailDataArray['user_indentification_file'])) {
                                unlink(WWW_ROOT . USER_IDENTIFICATION_FILE . DS . $userDetailDataArray['user_indentification_file']);
                            }
                        
                            $query  = $userDetail->query();
                            $data = $query->update()
                                ->set(['user_indentification_file' => $fileName])
                                ->where(['user_id' =>$userID])
                                ->execute();
                            $resultArray = array('status'=>true,'fileName'=>$fileName);
                                
                            $this->set([
                                    'success' => true,
                                    'data' => [
                                        'message' =>__('uploadingDone'),
                                        'name'=>SITE_URL.USER_IDENTIFICATION_FILE.DS.$fileName
                                    ],
                                    '_serialize' => ['data','success']
                                ]);
                        } else {
                            $userDetailNewData = $userDetail->newEntity();
                            $userDetailNewData->user_id = $userId;
                            $userDetailNewData->user_indentification_file = $fileName;
                            if ($userDetail->save($userDetailNewData)) {
                                $this->set([
                                    'success' => true,
                                    'data' => [
                                        'message' =>__('uploadingDone'),
                                        'name'=>SITE_URL.USER_IDENTIFICATION_FILE.DS.$fileName
                                    ],
                                    '_serialize' => ['data','success']
                                ]);
                            }
                        }
                    }
                    
                    ############### police certificate uploading code end here ###################
                } else {
                    $errors = ['error'=>['_required'=>__('fileIsEmpty')]];
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
    
    public function getUserVehicleInformation()
    {
        if ($this->request->is('get')) {
            $this->loadmodel('UserVehicleTypes');
            
            $userVehicleDetail = $this->UserVehicleTypes->find('all');
            
            foreach ($userVehicleDetail as $key=>$value) {
                $vehicles[] = $value->vehicle_type;
            }
            
            $this->set([
                'success' => true,
                'vehicleType' => $vehicles,
                '_serialize' => ['vehicleType','success']
            ]);
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
     *Update the vehicle Information  of user
    */
    public function editVehicleInformation()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $getUser = $this->Auth->identify();
                $id = $getUser['id'];
                $user = $this->Users->get($id);
                $UserVehicle = TableRegistry::get('UserVehicleTypes');
         /*        if ($user['role_id'] == USER_PRO_EXPERT_ROLE) {
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
                } */
                
                if (empty($this->request->data['vehicles'])) {
                    $user->errors(['vehicles' => ['_empty'=>__('vehiclesRequired')]]);
                }
                if (isset($this->request->data['vehicles']) && !empty($this->request->data['vehicles'])) {
                    $vehicles = $this->request->data['vehicles'];
                    unset($this->request->data['vehicles']);
                    if (!empty($vehicles)) {
                        $vehiclesArray = explode(",", $vehicles);
                        $data = array();
                        $vehicleError = false;
                        $validVehiclesArray = array('Bicycle','Pickup Truck');
                            
                        foreach ($vehiclesArray as $vehicle) {
                            if (!in_array($vehicle, $validVehiclesArray)) {
                                $vehicleError = true;
                                break;
                            }
                        }
                            
                        if (!$vehicleError) {
                            $UserVehicle->deleteAll(['user_id' => $id]);
                            if (!empty($vehicles)) {
                                unset($this->request->data['vehicles']);
                                if (!empty($vehicles)) {
                                    $vehcilesArray = explode(",", $vehicles);
                                    $data = array();
                                        
                                    foreach ($vehcilesArray as $vehicle) {
                                        $UserVehicleNewData = $UserVehicle->newEntity();
                                        $UserVehicleNewData->user_id = $id;
                                        $UserVehicleNewData->vehicle_type = $vehicle;
                                        $UserVehicleNewData->created = date('Y-m-d H:i:s');
                                        $UserVehicle->save($UserVehicleNewData);
                                    }
                                }
                            }
                        } else {
                            $user->errors(['vehicles' => ['_invalid'=>__('invalidVehicleTypes')]]);
                        }
                    }
                }
                if ($user->errors()) {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' => count($user->errors()).__('validationErrorsOccured'),
                            'error' => '',
                            'errorCount' => count($user->errors()),
                            'errors' => $user->errors(),
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
                
                $this->set([
                                'success' => true,
                                'data' => [
                                    'message' =>__('vehicleInformationUpdatedSuccessfully'),
                                ],
                                '_serialize' => ['data','success']
                            ]);
                return ;
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
     *Update the Background Information  of user
    */
    public function editPersonalInformation()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $getUser = $this->Auth->identify();
                
                $id = $getUser['id'];
                $this->Users->hasOne('UserDetails', [
                    'className' => 'UserDetails',
                    'foreignKey' => 'user_id'
                ]);
                $user = $this->Users->get($id, ['contain'=>['UserDetails']]);
                
                if (!isset($this->request->data['interested_become_worker']) || empty($this->request->data['interested_become_worker'])) {
                    $user->errors(['interested_become_worker' => ['_empty'=>__('interestedBecomeWorkerRequired')]]);
                }
                if (!isset($this->request->data['location']) || empty($this->request->data['location'])) {
                    $user->errors(['location' => ['_empty'=>__('locationRequired')]]);
                }
                if (!isset($this->request->data['dob']) || empty($this->request->data['dob'])) {
                    $user->errors(['dob' => ['_empty'=>__('dateOfBirthIsRequired')]]);
                }
                if (!empty($this->request->data['dob'])) {
                    if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])\-(0[1-9]|[1-2][0-9]|3[0-1])$/", $this->request->data['dob'])) {
                        $user->errors(['dob' => ['validFormat'=>__('Invalid date of birth.')]]);
                    }
                }
                if ($user->errors()) {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' => count($user->errors()).__('validationErrorsOccured'),
                            'error' => '',
                            'errorCount' => count($user->errors()),
                            'errors' => $user->errors(),
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
                
                if (isset($this->request->data['interested_become_worker']) && !empty($this->request->data['interested_become_worker'])) {
                    $this->request->data['user_detail']['interested_become_worker'] = $this->request->data['interested_become_worker'];
                    unset($this->request->data['interested_become_worker']);
                }
				if(isset($this->request->data['location']) && !empty($this->request->data['location'])){
					$address = $this->request->data['location'];
					$formattedAddr = str_replace(' ','+',$address);
					$geocode = file_get_contents('http://maps.google.com/maps/api/geocode/json?address='.$formattedAddr.'&sensor=false');
					$output= json_decode($geocode);
					$lat = 0;
					$long = 0;
					if(isset($output->results[0]->geometry->location->lat) && isset($output->results[0]->geometry->location->lng)){
					$lat = $output->results[0]->geometry->location->lat;
					$long = $output->results[0]->geometry->location->lng;
					}
					$this->request->data['latitude'] = $lat;
					$this->request->data['longitude'] = $long;
				}
                $user = $this->Users->patchEntity($user, $this->request->data);
			
                if ($this->Users->save($user)) {
                    $this->set([
                        'success' => true,
                        'data' => [
                            'message' =>__('personalInformationUpdatedSuccessfully'),
                        ],
                        '_serialize' => ['data','success']
                    ]);
                    return ;
                } else {
                    if ($user->errors()) {
                        $this->set([
                            'success' => false,
                            'data' => [
                                'code' => 422,
                                'url' => h($this->request->here()),
                                'message' => count($user->errors()).__('validationErrorsOccured'),
                                'error' => '',
                                'errorCount' => count($user->errors()),
                                'errors' => $user->errors(),
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
     *Update the About Information  of user
    */
    public function editAboutYouInformation()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $getUser = $this->Auth->identify();
           /*      if ($getUser['role_id'] == USER_PRO_EXPERT_ROLE) {
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
                } */
                $id = $getUser['id'];
                $this->Users->hasOne('UserDetails', [
                'className' => 'UserDetails',
                'foreignKey' => 'user_id'
                ]);
                $user = $this->Users->get($id, ['contain'=>['UserDetails']]);
                
                if (!isset($this->request->data['community_description']) || empty($this->request->data['community_description'])) {
                    $user->errors(['community_description' => ['_empty'=>__('community_description')]]);
                }
                if (!isset($this->request->data['interest_description']) || empty($this->request->data['interest_description'])) {
                    $user->errors(['interest_description' => ['_empty'=>__('interest_description')]]);
                }
                if (!isset($this->request->data['confident_with_work_description']) || empty($this->request->data['confident_with_work_description'])) {
                    $user->errors(['confident_with_work_description' => ['_empty'=>__('confident_with_work_description')]]);
                }
                if ($user->errors()) {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' => count($user->errors()).__('validationErrorsOccured'),
                            'error' => '',
                            'errorCount' => count($user->errors()),
                            'errors' => $user->errors(),
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }
                
                if (isset($this->request->data['community_description']) && !empty($this->request->data['community_description'])) {
                    $this->request->data['user_detail']['community_description'] = $this->request->data['community_description'];
                    unset($this->request->data['community_description']);
                }
                if (isset($this->request->data['interest_description']) && !empty($this->request->data['interest_description'])) {
                    $this->request->data['user_detail']['interest_description'] = $this->request->data['interest_description'];
                    unset($this->request->data['interest_description']);
                }
                if (isset($this->request->data['confident_with_work_description']) && !empty($this->request->data['confident_with_work_description'])) {
                    $this->request->data['user_detail']['confident_with_work_description'] = $this->request->data['confident_with_work_description'];
                    unset($this->request->data['confident_with_work_description']);
                }
				$this->request->data['user_detail']['apply_worker'] = 1;
                $user = $this->Users->patchEntity($user, $this->request->data);
                if ($this->Users->save($user)) {
					if ($getUser['role_id'] == USER_CLIENT_ROLE) {
						if(!empty($user->email))
						{
							$email = $user->email;
							$emailData = TableRegistry::get('EmailTemplates');
							$emailDataResult = $emailData->find()->where(['slug' => 'apply_worker_acknowledge']);
							$emailContent = $emailDataResult->first();
							$to = $email;
							$subject = $emailContent->subject;
							$mail_message_data = $emailContent->description;
							$mail_text_message = $mail_message_data;
							$from = SITE_EMAIL;
							$mail_message = str_replace('{CONTENT}',$mail_text_message,$this->email_template());
							parent::sendEmail($from, $to, $subject, $mail_message);
						}
					}
                    $this->set([
                                    'success' => true,
                                    'data' => [
                                        'message' =>__('aboutYouInformationUpdatedSuccessfully'),
                                    ],
                                    '_serialize' => ['data','success']
                                ]);
                    return ;
                } else {
                    if ($user->errors()) {
                        $this->set([
                            'success' => false,
                            'data' => [
                                'code' => 422,
                                'url' => h($this->request->here()),
                                'message' => count($user->errors()).__('validationErrorsOccured'),
                                'error' => '',
                                'errorCount' => count($user->errors()),
                                'errors' => $user->errors(),
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
     *Update the Availability Information  of user
    */
    public function editAvailabilityInformation()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $getUser = $this->Auth->identify();
             /*    if ($getUser['role_id'] == USER_PRO_EXPERT_ROLE) {
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
                } */
                $id = $getUser['id'];
                $this->Users->hasOne('UserDetails', [
                'className' => 'UserDetails',
                'foreignKey' => 'user_id'
                ]);
                
                
                $user = $this->Users->get($id, ['contain'=>['UserDetails']]);
                if (empty($this->request->data['availability'])) {
                    $user->errors(['availability' => ['_empty'=>__('availabilityRequired')]]);
                } else {
                    $availability = array('1','2','3');
                    if (!in_array($this->request->data['availability'], $availability)) {
                        $user->errors(['availability' => ['_invalid'=>__('invalidAvailability')]]);
                    }
                }
                if (empty($this->request->data['availability_location'])) {
                    $user->errors(['availability_location' => ['_empty'=>__('availabilityLocationRequired')]]);
                }
                
                if ($user->errors()) {
                    $this->set([
                        'success' => false,
                        'data' => [
                            'code' => 422,
                            'url' => h($this->request->here()),
                            'message' => count($user->errors()).__('validationErrorsOccured'),
                            'error' => '',
                            'errorCount' => count($user->errors()),
                            'errors' => $user->errors(),
                            ],
                        '_serialize' => ['success', 'data']]);
                    return ;
                }

                if (isset($this->request->data['availability_location']) && !empty($this->request->data['availability_location'])) {
                    $this->request->data['user_detail']['availability_location'] = $this->request->data['availability_location'];
                    unset($this->request->data['availability_location']);
                }
                $this->request->data['user_detail']['availability'] = $this->request->data['availability'];
                
                $user = $this->Users->patchEntity($user, $this->request->data);
                
                if ($this->Users->save($user)) {
                    $this->set([
                                    'success' => true,
                                    'data' => [
                                        'message' =>__('availabilityInformationUpdatedSuccessfully'),
                                    ],
                                    '_serialize' => ['data','success']
                                ]);
                    return ;
                } else {
                    if ($user->errors()) {
                        $this->set([
                            'success' => false,
                            'data' => [
                                'code' => 422,
                                'url' => h($this->request->here()),
                                'message' => count($user->errors()).__('validationErrorsOccured'),
                                'error' => '',
                                'errorCount' => count($user->errors()),
                                'errors' => $user->errors(),
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
     *Update the Availability Information  of user
    */
    public function editAvailabilityTime()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $getUser = $this->Auth->identify();
            /*     if ($getUser['role_id'] == USER_PRO_EXPERT_ROLE) {
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
                } */
                
                if (empty($this->request->data['day'])) {
                    $errors[] = ['error'=>['_required'=>__('day is required.')]];
                }
                if (empty($this->request->data['dayTime'])) {
                    $errors[] = ['error'=>['_required'=>'dayTime is required.']];
                }
                if (!isset($this->request->data['dayTimeStatus'])) {
                    $errors[] = ['error'=>['_required'=>'dayTimeStatus is required.']];
                }
                if (!empty($this->request->data['day'])) {
                    $day = array('sun','mon','tue','wed','thr','fri','sat');
                    if (!in_array($this->request->data['day'], $day)) {
                        $errors[] = ['error'=>['_inValid'=>__('Invalid day value.')]];
                    }
                }
                if (!empty($this->request->data['dayTimeStatus'])) {
                    $dayTimeStatus = array('0','1');
                    if (!in_array($this->request->data['dayTimeStatus'], $dayTimeStatus)) {
                        $errors[] = ['error'=>['_inValid'=>__('Invalid dayTimeStatus value.')]];
                    }
                }
                if (!empty($this->request->data['dayTime'])) {
                    $dayTime = array('morning','afternoon','evening');
                    if (!in_array($this->request->data['dayTime'], $dayTime)) {
                        $errors[] = ['error'=>['_inValid'=>__('Invalid dayTime.')]];
                    }
                }
                if (!empty($errors)) {
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
                
                $id = $getUser['id'];
                $userAvailabilitiesData = TableRegistry::get('UserAvailabilities');
                $queryDay = $userAvailabilitiesData->find()->where(['user_id' => $id,'day'=>$this->request->data['day']]);
                $dayRowCount = $queryDay->count();
                if ($dayRowCount == 0) {
                    $userAvailabilitiesDataNewData = $userAvailabilitiesData->newEntity();
                    
                    if (isset($this->request->data['day']) && !empty($this->request->data['day'])) {
                        $userAvailabilitiesDataNewData->day = $this->request->data['day'];
                        unset($this->request->data['day']);
                    }
                    if (isset($this->request->data['dayTime']) && !empty($this->request->data['dayTime'])) {
                        if ($this->request->data['dayTime'] == 'morning') {
                            if ($this->request->data['dayTimeStatus'] == 1) {
                                $userAvailabilitiesDataNewData->morning_time = 1;
                                ;
                            } else {
                                $userAvailabilitiesDataNewData->morning_time = 0;
                            }
                        }
                        if ($this->request->data['dayTime'] == 'afternoon') {
                            if ($this->request->data['dayTimeStatus'] == 1) {
                                $userAvailabilitiesDataNewData->afternoon_time = 1;
                                ;
                            } else {
                                $userAvailabilitiesDataNewData->afternoon_time = 0;
                            }
                        }
                        if ($this->request->data['dayTime'] == 'evening') {
                            if ($this->request->data['dayTimeStatus'] == 1) {
                                $userAvailabilitiesDataNewData->evening_time = 1;
                            } else {
                                $userAvailabilitiesDataNewData->evening_time = 0;
                            }
                        }
                        unset($this->request->data['dayTime']);
                    }
                    $userAvailabilitiesDataNewData->created = date('Y-m-d H:i:s');
                    $userAvailabilitiesDataNewData->user_id = $id;
                    if ($userAvailabilitiesData->save($userAvailabilitiesDataNewData)) {
                        $this->set([
                            'success' => true,
                            'data' => [
                                'message' =>__('availabilityTimeUpdatedSuccessfully'),
                            ],
                            '_serialize' => ['data','success']
                        ]);
                        return ;
                    }
                } else {
                    $this->loadModel('UserAvailabilities');
                    $result = $queryDay->toArray()[0];
                    $userAvailabilities = $result->id;
                    $userAvailabilitiesData = $this->UserAvailabilities->get($userAvailabilities); // Return user regarding id
                    
                    if (isset($this->request->data['day']) && !empty($this->request->data['day'])) {
                        $userAvailabilitiesData->day = $this->request->data['day'];
                        unset($this->request->data['day']);
                    }
                    if (isset($this->request->data['dayTime']) && !empty($this->request->data['dayTime'])) {
                        if ($this->request->data['dayTime'] == 'morning') {
                            if ($this->request->data['dayTimeStatus'] == 1) {
                                $userAvailabilitiesData->morning_time = 1;
                                ;
                            } else {
                                $userAvailabilitiesData->morning_time = 0;
                            }
                        }
                        if ($this->request->data['dayTime'] == 'afternoon') {
                            if ($this->request->data['dayTimeStatus'] == 1) {
                                $userAvailabilitiesData->afternoon_time = 1;
                                ;
                            } else {
                                $userAvailabilitiesData->afternoon_time = 0;
                            }
                        }
                        if ($this->request->data['dayTime'] == 'evening') {
                            if ($this->request->data['dayTimeStatus'] == 1) {
                                $userAvailabilitiesData->evening_time = 1;
                            } else {
                                $userAvailabilitiesData->evening_time = 0;
                            }
                        }
                        unset($this->request->data['dayTime']);
                    }
                    
                    
                    if ($this->UserAvailabilities->save($userAvailabilitiesData)) {
                        $this->set([
                            'success' => true,
                            'data' => [
                                'message' =>__('availabilityTimeUpdatedSuccessfully'),
                            ],
                            '_serialize' => ['data','success']
                        ]);
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
     *Get the review list of user
    */
    public function getReviewList()
    {
        if ($this->request->is('get')) {
            $checkAuth = $this->check_user_authrization();
			  
            if ($checkAuth) { 
                $user = $this->Auth->identify();
                $userID = $user['id'];
                $this->loadModel('JobFeedbacks');
                $this->JobFeedbacks->belongsTo('Jobs', [
                    'className' => 'Jobs',
                    'foreignKey'=>'job_id'
                ]);
				$this->JobFeedbacks->belongsTo('Users', [
                    'className' => 'Users',
                    'foreignKey'=>'member_id'
                ]);
              
				 $this->paginate = [
                        'contain'=>['Jobs','Users'],
						'conditions'=>['JobFeedbacks.member_id'=>$userID],
                        'order' => [
                            'JobFeedbacks.rating' => 'DESC']
                    ];
                   $jobfeedbacks = $this->paginate($this->JobFeedbacks);
				
				
                $this->set([
                    'success' => true,
                    'jobfeedbacks' => $jobfeedbacks,
                 
					   'paging'=>['page'=>$this->request->params['paging']['JobFeedbacks']['page'],
                                        'current'=>$this->request->params['paging']['JobFeedbacks']['current'],
                                        'pageCount'=>$this->request->params['paging']['JobFeedbacks']['pageCount'],
                                        'current'=>$this->request->params['paging']['JobFeedbacks']['page'],
                                        'nextPage'=>$this->request->params['paging']['JobFeedbacks']['nextPage'],
                                        'prevPage'=>$this->request->params['paging']['JobFeedbacks']['prevPage'],
                                        'count'=>$this->request->params['paging']['JobFeedbacks']['count'],
                                        'perPage'=>$this->request->params['paging']['JobFeedbacks']['perPage']
                                    ],			
								
                    '_serialize' => ['jobfeedbacks','success','paging']
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
    
    public function viewProfilePicture()
    {
        if ($this->request->is('get')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) {
                $user = $this->Auth->identify();
                $userId = $user['id'];
                $user = $this->Users->get($userId);
                
                $userDetail = array();
                if (!empty($user)) {
                    if (!empty($user->profile_image)) {
                        $userDetail['original'] = SITE_URL . USERS_FULL_DIR . DS . USERS_ORIGINAL_DIR . DS .  $user->profile_image;
                        $userDetail['thumb144X137']=SITE_URL . USERS_FULL_DIR . DS . USERS_144X137_DIR . DS .  $user->profile_image;
                        $userDetail['thumb154X138']=SITE_URL . USERS_FULL_DIR . DS . USERS_154X138_DIR . DS .  $user->profile_image;
                    } else {
                        $userDetail['original'] = "";
                        $userDetail['thumb144X137']="";
                        $userDetail['thumb154X138']="";
                    }
                }
                    
                 
                $this->set([
                    'success' => true,
                    'data' => $userDetail,
                    '_serialize' => ['data','success']
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
    
    public function getBankDetail()
    {
        if ($this->request->is('post')) {
            $checkAuth = $this->check_user_authrization();
            $user = $this->Auth->identify();
            
            if ($checkAuth) {
                $this->loadModel('UserBankDetails');
                $user = $this->Auth->identify();
                $userId = $user['id'];
                if (!empty($this->request->data['userId'])) {
                    $UserId = base64_decode($this->request->data['userId']);
                    
                    $exists = $this->UserBankDetails->exists(['UserBankDetails.user_id' => $UserId]);
                    if ($exists) {
                        $this->loadModel('Categories');
                        $this->UserBankDetails->belongsTo('Users', [
                            'className' => 'Users',
                            'foreignKey'=>'user_id'
                        ]);
                        $bankDetail = $this->UserBankDetails->find()
                                    ->contain(['Users'])
                                    ->where(['user_id' => $UserId])
                                    ->first()->toArray();
                                    
                        $bankDetailArray = array();
                        $bankDetailArray['email'] = $bankDetail['user']['email'];
                        $bankDetailArray['first_name'] = $bankDetail['user']['first_name'];
                        $bankDetailArray['last_name'] = $bankDetail['user']['last_name'];
                        $bankDetailArray['paypal_email'] = $bankDetail['paypal_email'];
                        $bankDetailArray['swift_code'] = $bankDetail['swift_code'];
                        $bankDetailArray['account_number'] = $bankDetail['account_number'];
                        $bankDetailArray['bank_name'] = $bankDetail['name'];
                        $bankDetailArray['iban'] = $bankDetail['iban'];
                        $bankDetailArray['cash_payment_status'] = $bankDetail['cash_payment_status'];
                        $this->set([
                            'success' => true,
                            'bankDetail' => $bankDetailArray,
                            '_serialize' => ['bankDetail','success']
                        ]);
                        
                        return ;
                    } else {
                        $errors = ['paypal_email'=>['_required'=>__('paypalIdIsInvalid')]];
                        $this->set([
                            'success' => false,
                            'data' => [
                                'code' => 422,
                                'url' => h($this->request->here()),
                                'message' =>__('userNotExists'),
                                'errorCount' => 1
                                ],
                            '_serialize' => ['success', 'data']]);
                        return ;
                    }
                } else {
                    $errors = ['paypal_email'=>['_required'=>__('paypalIdIsInvalid')]];
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
    
    public function resetPassword()
    {
        if ($this->request->is('post')) {
            $activationCode = $this->request->data['activationCode'];
            $userId = $this->request->data['userId'];
            $user = $this->Users->newEntity();
            if (empty($activationCode) || empty($userId)) {
                $user->errors(['invalidAccess' => ['_invalid'=>__('invalidAccess')]]);
            }
            $user = $this->Users->get($userId);
            if (empty($user->activation_code)) {
                $user->errors(['linkExpire' => ['_linkExpire'=>__('linkExpire')]]);
            }
            if ($user->errors()) {
				$this->set([
					'success' => false,
					'data' => [
						'code' => 422,
						'url' => h($this->request->here()),
						'message' => count($user->errors()).__('validationErrorsOccured'),
						'error' => '',
						'errorCount' => count($user->errors()),
						'errors' => $user->errors(),
						],
					'_serialize' => ['success', 'data']]);
				return ;
			}
            if (!empty($this->request->data)) {
                $user = $this->Users->patchEntity($user, [
                        'password'      => $this->request->data['password1'],
                        'password1'     => $this->request->data['password1'],
                        'password2'     => $this->request->data['password2']
                    ],
                    ['validate' => 'password']
                );
                
                $user->activation_code = null;
                if ($this->Users->save($user)) {
                    $this->set([
                        'success' => true,
                        'data' => [
                            'code' =>422,
                            'message' =>__('passwordUpdatedSuccessfully')
                        ],
                        '_serialize' => ['success', 'data']
                        ]);
                    return ;
                } else {
                    if ($user->errors()) {
                        $this->set([
                            'success' => false,
                            'data' => [
                                'code' => 422,
                                'url' => h($this->request->here()),
                                'message' => count($user->errors()).__('validationErrorsOccured'),
                                'error' => '',
                                'errorCount' => count($user->errors()),
                                'errors' => $user->errors(),
                                ],
                            '_serialize' => ['success', 'data']]);
                        return ;
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
	
	
    public function changePassword()
    {
        if ($this->request->is('post')) {
            $userId = $this->request->data['userId'];
            $user = $this->Users->newEntity();
            $user = $this->Users->get($userId);
            if (!empty($this->request->data)) {
                $user = $this->Users->patchEntity($user, [
                        /* 'password'      => $this->request->data['password1'],
                        'password1'     => $this->request->data['password1'],
                        'password2'     => $this->request->data['password2'] */
						 'old_password'  => $this->request->data['old_password'],
						'password'      => $this->request->data['password1'],
						'password1'     => $this->request->data['password1'],
						'password2'     => $this->request->data['password2']
                    ],
                    ['validate' => 'password']
                );
                if ($this->Users->save($user)) {
                    $this->set([
                        'success' => true,
                        'data' => [
                            'code' =>422,
                            'message' =>__('passwordUpdatedSuccessfully')
                        ],
                        '_serialize' => ['success', 'data']
                        ]);
                    return ;
                } else {
                    if ($user->errors()) {
                        $this->set([
                            'success' => false,
                            'data' => [
                                'code' => 422,
                                'url' => h($this->request->here()),
                                'message' => count($user->errors()).__('validationErrorsOccured'),
                                'error' => '',
                                'errorCount' => count($user->errors()),
                                'errors' => $user->errors(),
                                ],
                            '_serialize' => ['success', 'data']]);
                        return ;
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
    
    public function accountActivation()
    {
        if ($this->request->is('post')) {
            $verification_code = $this->request->data['verificationCode'];
            $email = $this->request->data['email'];
            $user = $this->Users->newEntity();
            $userTable = TableRegistry::get('Users');
            $userExists = $userTable->exists(['email' => $email,'activation_code' => $verification_code]);
            if ($userExists) {
                $user_data = $userTable->find()
                                ->where(['email'=>$email,'activation_code'=>$verification_code])
                                ->first();
				$user_data->status = STATUS_ACTIVE;
                $user_data->modified = date('Y-m-d H:i:s');
                $verification_code = substr(md5(time()), 0, 20);
                $user_data->activation_code = null;
                $userTable->save($user_data);
                $this->set([
                'success' => true,
                'data' => [
                    'code' =>422,
					'userDetail'=>$user_data,
                    'message' =>__('Your_account_has_been_activated')
                ],
                '_serialize' => ['success', 'data']
                ]);
                return ;
            } else {
                $user->errors(['linkExpire' => ['_linkExpire'=>__('linkExpire')]]);
				if ($user->errors()) {
                        $this->set([
                            'success' => false,
                            'data' => [
                                'code' => 422,
                                'url' => h($this->request->here()),
                                'message' => count($user->errors()).__('validationErrorsOccured'),
                                'error' => '',
                                'errorCount' => count($user->errors()),
                                'errors' => $user->errors(),
                                ],
                            '_serialize' => ['success', 'data']]);
                        return ;
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
	
	public function userListByCategory(){
	
		if ($this->request->is('post')) { 
		 $errors = array();
				if (empty($this->request->data['categorySlug'])) {				
				$errors[] = ['categorySlug'=>['_required'=>__('categorySlugRequired')]];
				}if (empty($this->request->data['latitude']) || $this->request->data['latitude'] == 0) {
				$errors[] = ['latitude'=>['_required'=>__('Invalid dayTime.')]];
				}if (empty($this->request->data['logitude']) || $this->request->data['logitude'] == 0) {
				$errors[] = ['logitude'=>['_required'=>__('Invalid dayTime.')]];
				} 
			if ($errors) {
			
				$this->set([
					'success' => false,
					'data' => [
						'code' => 422,
						'message' => count($errors).__('validationErrorsOccured'),
						'error' => '',
						'errorCount' => count($errors),
						'errors' => $errors,
						],
					'_serialize' => ['success', 'data']]);
				return ;
			}

			$slug = $this->request->data['categorySlug'];
			$categoryTable = TableRegistry::get('Categories');
			$categoryExists = $categoryTable->exists(['slug'=>$slug]);
			
			if ($categoryExists) {
				
				$categoryDetail = $categoryTable->find()
				->where(['slug' => $slug])
				->first()->toArray();
				
				$latitude = $this->request->data['latitude'];
				$longitude = $this->request->data['logitude'];
				

				$this->loadModel('CategoriesUsers');
				$categoryData = $this->CategoriesUsers->find()->where(['category_id'=>$categoryDetail['id']]);
				$CategoriesUsersArray = $categoryData->toArray();

				$catUserArray = array();
				foreach($CategoriesUsersArray as $val){
					$catUserArray[] = "'".$val['user_id']."'";
				}

				$queryData = implode(',',$catUserArray);				
				$this->loadModel('Users');
				$distance=0;
				$distanceField = '( 3959 * acos( cos( radians( :latitude ) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians( :longitude ) ) + sin( radians( :latitude ) ) * sin( radians( latitude ) ) ) )';

				$this->Users->hasOne('UserDetails');


				$sightings = $this->Users->find()
				->select([
				'distance' => $distanceField,'id','role_id','first_name','last_name','location','profile_image','rating','UserDetails.hourly_rate'
				])
				->leftJoinWith('UserDetails')
				->where(["Users.role_id = '".USER_PRO_EXPERT_ROLE."' AND Users.status = '".STATUS_ACTIVE."' AND Users.id IN ($queryData)"])
				->order(['distance' => 'ASC'])
				->bind(':latitude', $latitude, 'float')
				->bind(':longitude', $longitude, 'float');
				$this->paginate = [
                    'maxLimit' => APIPageLimit,  
                ];
				$this->set([
				'success' => true,
				'data' =>$this->paginate($sightings),                
				'_serialize' => ['data','success','pagination']
				]);		
			return ;
			
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
			}  else {
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
	
	public function accountDelete(){
		if ($this->request->is('post')) {			
			$id = $this->request->data['id'];
			if (!$id) {
				throw new \Cake\Network\Exception\NotFoundException(__('Id is not valid!'));
			}
			$this->request->allowMethod(['post', 'delete']);
			
			$this->Users->hasMany('UserAvailabilities', [
				'className' => 'UserAvailabilities',
				'foreignKey' => 'user_id',
				'dependent' => true
			]);	 	
			$this->Users->hasMany('UserBankDetails', [
				'className' => 'UserBankDetails',
				'foreignKey' => 'user_id',
				'dependent' => true
			]);	 	
			$this->Users->hasOne('UserDetails');			
			$this->Users->hasMany('UserVehicleTypes', [
				'className' => 'UserVehicleTypes',
				'foreignKey' => 'user_id',
				'dependent' => true
			]); 
			$this->Users->hasMany('CategoriesUsers', [
				'className' => 'CategoriesUsers',
				'foreignKey' => 'user_id',
				'dependent' => true
			]);
			$this->Users->hasMany('Jobs', [
				'className' => 'Jobs',
				'foreignKey' => 'user_id',
				'dependent' => true
			]);
			$this->Users->hasMany('Offers', [
				'className' => 'Offers',
				'foreignKey' => 'user_id',
				'dependent' => true
			]);
			$this->Users->hasMany('Transactions', [
				'className' => 'Transactions',
				'foreignKey' => 'user_id',
				'dependent' => true
			]);
			$this->Users->hasMany('JobFeedbacks', [
				'className' => 'JobFeedbacks',
				'foreignKey' => 'user_id',
				'dependent' => true
			]);			
			$user = $this->Users->get($id,['contain'=>['UserAvailabilities','UserBankDetails','UserVehicleTypes','UserDetails','CategoriesUsers']]);
			
			if(!empty($user->user_detail))
			{				
				if(!empty($user['user_detail']['police_certificate_file']) &&  file_exists(WWW_ROOT . USER_POLICE_CERTIFICATE_FILE . DS . $user['user_detail']['police_certificate_file']))
				{
					unlink(WWW_ROOT . USER_POLICE_CERTIFICATE_FILE . DS . $user['user_detail']['police_certificate_file']);
				}
				
				if(!empty($user['user_detail']['user_indentification_file']) &&  file_exists(WWW_ROOT . USER_IDENTIFICATION_FILE . DS . $user['user_detail']['user_indentification_file']))
				{
					unlink(WWW_ROOT . USER_IDENTIFICATION_FILE . DS . $user['user_detail']['user_indentification_file']);
				}
			}
			
			if(!empty($user->profile_image) && file_exists(WWW_ROOT . USERS_FULL_DIR . DS . $user->profile_image)){
				unlink(WWW_ROOT . USERS_FULL_DIR . DS . $user->profile_image);
			}
			
			if ($this->Users->delete($user)){
				
				$this->set([
					'success' => true,
					'data' => [
						'code' =>200,
						'message' =>__('deleteYourAccount')
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
	
	
	public function transactionList()
    {
	
	
        if ($this->request->is('get')) {
            $checkAuth = $this->check_user_authrization();
            if ($checkAuth) { 
                $user = $this->Auth->identify();
                $userID = $user['id'];
           
                $this->loadModel('Transactions');
                $this->Transactions->belongsTo('Jobs', [
                    'className' => 'Jobs',
                    'foreignKey'=>'job_id'
                ]); 
				$this->Transactions->belongsTo('Offers', [
                    'className' => 'Offers',
                    'foreignKey'=>'offer_id'
                ]);
				$this->Transactions->belongsTo('Users', [
                    'className' => 'Users',
                    'foreignKey'=>'member_id'
                ]);
              
				 $this->paginate = [
                        'contain'=>['Offers','Jobs','Users'],
						'conditions'=>['Transactions.member_id'=>$userID],
                        'order' => [
                            'Transactions.rate' => 'ASC']
                    ];

                   $transactions = $this->paginate($this->Transactions);
				
				
                $this->set([
                    'success' => true,
                    'transactions' => $transactions,
					   'paging'=>['page'=>$this->request->params['paging']['Transactions']['page'],
                                        'current'=>$this->request->params['paging']['Transactions']['current'],
                                        'pageCount'=>$this->request->params['paging']['Transactions']['pageCount'],
                                        'current'=>$this->request->params['paging']['Transactions']['page'],
                                        'nextPage'=>$this->request->params['paging']['Transactions']['nextPage'],
                                        'prevPage'=>$this->request->params['paging']['Transactions']['prevPage'],
                                        'count'=>$this->request->params['paging']['Transactions']['count'],
                                        'perPage'=>$this->request->params['paging']['Transactions']['perPage']
                                    ],			
								
                    '_serialize' => ['transactions','success','paging']
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
	
	
	
}
