<?php
namespace App\Controller\Api;

use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Network\Exception\MethodNotAllowedException;
use Cake\Utility\Security;

class CocktailsController extends AppController
{
    public $paginate = [
        'page' => 1,
        'limit' => 5,
        'maxLimit' => 15,
        'sortWhitelist' => [
            'id', 'name'
        ]
    ];
    
    
    public function initialize()
    {
        parent::initialize();
        $this->Auth->allow(['uploadCurruculumVitae','test']);
    }
    
    
    public function view($id)
    {
        $cocktail = $this->Cocktails->get($id, [
            'contain' => []
        ]);

        $this->set([
            'cocktail' => $cocktail,
            '_serialize' => ['cocktail']
        ]);
    }

    
    
    public function categories()
    {
        if ($this->request->is('get')) {
            $this->loadModel('Categories');
            $this->paginate = [
                'fields' => [
                    'name', 'description'
                ],
                'conditions'=>['Categories.status'=>1]
            ];
            $categries = $this->paginate($this->Categories);
            $this->set([
                'success' => true,
                'categries' => $categries,
                '_serialize' => ['categries','success']
            ]);
        }
    }
    
    
    
    public function uploadCurruculumVitae()
    {
        $userID = 1;
        $attach_curruculum_vitae = $this->request->data['fileToUpload'];
        
        if (!empty($attach_curruculum_vitae['tmp_name'])) {
            $allowed    =    array('application/msword','application/pdf');// extensions are allowe
            
            if (!in_array($attach_curruculum_vitae["type"], $allowed)) { // check the extension of document
                $message = "Only pdf, word files allowed.";
                $errors = ['categoryId'=>['_required'=>'Only pdf, word files allowed']];
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
        
            if ($attach_curruculum_vitae['size'] > 10485760) { // check the size of Curruculum Vitae
                $message = __('sizeMustBeLessThan');
                $success = false;
                $this->set([
                    'success' => $success,
                    'data' => [
                        'code' =>405,
                        'message' =>$message
                    ],
                    '_serialize' => ['success', 'data']
                ]);
            }
            $temp        =    explode(".", $attach_curruculum_vitae["name"]);
            $extension    =    end($temp);
            $fileName    =    'curruculum_vitae_'.microtime(true).'.'.$extension;
            if (move_uploaded_file($attach_curruculum_vitae['tmp_name'], WWW_ROOT . 'curruculum_vitae' . DS . $fileName)) {
                $userDetailsTable = TableRegistry::get('UserDetails');
                // Start a new query.
                $query = $userDetailsTable->find()
                ->where(['user_id' => $userID]);
                $row = $query->count();
                $result = $query->toArray()[0];
                
                if ($row == 0) {
                    $userDetail = $userDetailsTable->newEntity();
                    $userDetail->attach_curruculum_vitae = $fileName;
                    $userDetail->user_id = $userID;
                    $userDetail->created = date('Y-m-d H:i:s');
                    $userDetail->modified = date('Y-m-d H:i:s');
                    $userDetailsTable->save($userDetail);
                } else {
                    $query->update()
                    ->set(['attach_curruculum_vitae' => $fileName,'modified'=>date('Y-m-d H:i:s')])
                    ->where(['user_id' => $userID])
                    ->execute();
                    if (file_exists(WWW_ROOT . 'curruculum_vitae' . DS . $result->attach_curruculum_vitae)) {
                        unlink(WWW_ROOT . 'curruculum_vitae' . DS . $result->attach_curruculum_vitae);
                    }
                }
                
                $message = 'Uploading Done.';
                $success = true;
                $this->set([
                    'success' => $success,
                    'data' => [
                        'code' =>405,
                        'message' =>$message
                    ],
                    '_serialize' => ['success', 'data']
                ]);
            } else {
                $message = 'Uploading error.Please try again later.';
                $success = false;
                $this->set([
                    'success' => $success,
                    'data' => [
                        'code' =>405,
                        'message' =>$message
                    ],
                    '_serialize' => ['success', 'data']
                ]);
            }
        } else {
            $message = 'File is empty.';
            $success = false;
            $this->set([
                    'success' => $success,
                    'data' => [
                        'code' =>405,
                        'message' =>$message
                    ],
                    '_serialize' => ['success', 'data']
            ]);
        }
    }
    
    public function dddd()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "http://localhost/rest_api/action.php",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => "-----011000010111000001101001\r\nContent-Disposition: form-data; name=\"uploadedfile\"; filename=\"https://www.gstatic.com/images/icons/material/product/2x/drive_32dp.png\"\r\nContent-Type: image/png\r\n\r\n\r\n-----011000010111000001101001\r\nContent-Disposition: form-data; name=\"filename\"\r\n\r\ntest.png\r\n-----011000010111000001101001--",
          CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: multipart/form-data; boundary=---011000010111000001101001",
            "postman-token: 4d98904c-f93f-562d-3217-023f0bbcb22f"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }
    }
	
	
	
	
	
	
	public function test()
	{
		
		
		
		$email = "khemit.verma25@gmail.com";
		$password = "123456";
		$verification_code = "123456";
		$emailData = TableRegistry::get('EmailTemplates');
		$emailDataResult = $emailData->find()->where(['slug' => 'user_registration']);
		$emailContent = $emailDataResult->first();
		$activation_url = WEBSITE_PASSWORD_URL . 'en/users/activate/'. base64_encode($email).'/'.$verification_code;
		$activation_link    = $activation_url;
		$to = $email;
		$subject = $emailContent->subject;
		$mail_message_data = $emailContent->description;
		$activation_link    =' <a href="'.$activation_url.'" target="_blank" shape="rect">'.__("activationLink").'</a>';
		$mail_message = str_replace(array('{EMAIL}','{ACTIVATION_LINK}','{PASSWORD}'), array($email,$activation_link,$password), $mail_message_data);
		
		echo $mail_message = str_replace('{CONTENT}',$mail_message,$this->email_template());
		
		
		
		$from = SITE_EMAIL;
		die;
	}
}
