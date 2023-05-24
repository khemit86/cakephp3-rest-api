<?php
namespace App\Controller\Api;

use Cake\Event\Event;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Utility\Security;
use Firebase\JWT\JWT;
use Cake\ORM\TableRegistry;
use Cake\I18n\I18n;

class StaticPagesController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->Auth->allow(['view','help']);
    }
    
    public function view()
    {
        if ($this->request->is('post')) {
            if (!empty($this->request->data['pageSlug'])) {
                I18n::locale($this->request->session()->read('Config.language'));
                $pageSlug = $this->request->data['pageSlug'];
                $exists = $this->StaticPages->exists(['StaticPages.slug' => $pageSlug]);
                if ($exists) {
                    $pageDetail = $this->StaticPages->find()
                                ->where(['slug' => $pageSlug,'status'=>STATUS_ACTIVE])
                                ->first()->toArray();
                    
                    $this->set([
                        'success' => true,
                        'pageDetail' => $pageDetail,
                        '_serialize' => ['pageDetail','success']
                    ]);
                    $this->_init_language();
                } else {
                    $this->set([
                    'success' => false,
                    'data' => [
                        'code' =>422,
                        'message' =>__('pageSlugInvalid')
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
                            'message' =>__('pageSlugRequired')
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
	
	
	public function help()
    {
        if ($this->request->is('post')) {
            $staticPagesData = TableRegistry::get('StaticPages');
            $staticPagesTable = $staticPagesData->newEntity($this->request->data, ['validate' => 'help']);
            if ($staticPagesTable->errors()) {
                $this->set([
                    'success' => false,
                    'data' => [
                        'code' => 422,
                        'url' => h($this->request->here()),
                        'message' => count($staticPagesTable->errors()).__('validationErrorsOccured'),
                        'error' => '',
                        'errorCount' => count($staticPagesTable->errors()),
                        'errors' => $staticPagesTable->errors(),
                        ],
                    '_serialize' => ['success', 'data']]);
                return ;
            }else{
				
				$first_name = $this->request->data['first_name'];
				$last_name = $this->request->data['last_name'];
				$from = $this->request->data['email'];
				$subject = $this->request->data['subject'];
				$question = $this->request->data['question'];
				$message = $this->request->data['message'];
                $emailData = TableRegistry::get('EmailTemplates');
                $emailDataResult = $emailData->find()->where(['slug' => 'help']);
                $emailContent = $emailDataResult->first();
				$to = SITE_EMAIL;
                $subject = $emailContent->subject;
                $mail_message_data = $emailContent->description;
				$mail_text_message = str_replace(array('{FIRST_NAME}','{LAST_NAME}','{SUBJECT}','{QUESTION}','{MESSAGE}'), array($first_name,$last_name,$subject,$question,$message), $mail_message_data);
				$mail_message = str_replace('{CONTENT}',$mail_text_message,$this->email_template());
                parent::sendEmail($from, $to, $subject, $mail_message);
				$this->set([
					'success' => true,
					'data' => [
						'message' =>__('messageSendSuccessfully'),
					],
					'_serialize' => ['data','success']
				]);				
				
			}
		}
	}	
}
