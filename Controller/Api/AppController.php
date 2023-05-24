<?php
namespace App\Controller\Api;

use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Network\Email\Email;
use Cake\I18n\I18n;

/**
 * AppController specific to API resources
 */
class AppController extends Controller
{
    use \Crud\Controller\ControllerTrait;
    public function initialize()
    {
        parent::initialize();
        
        
        $this->request->params['language'] = $this->request->query('lang');
        $this->loadComponent('RequestHandler');
        $this->loadComponent('Cookie');
        $this->loadComponent('Crud.Crud', [
            'actions' => [
                'Crud.Index',
                'Crud.View',
                'Crud.Add',
                'Crud.Edit',
                'Crud.Delete',
            ],
            'listeners' => [
                'Crud.Api',
                'Crud.ApiPagination',
                'Crud.ApiQueryLog'
            ]
        ]);
        $this->loadComponent('Auth', [
            'storage' => 'Memory',
            'authenticate' => [
                'Form' => [
                    //'scope' => ['Users.status' => STATUS_ACTIVE],
                    'fields' => [
                        'username' => 'email'
                    ]
                ],
                'ADmad/JwtAuth.Jwt' => [
                    'parameter' => 'token',
                    'userModel' => 'Users',
                   // 'scope' => ['Users.status' => STATUS_ACTIVE],
                    'fields' => [
                        'username' => 'id'
                    ],
                    'queryDatasource' => true,
                ]
            ],
            'unauthorizedRedirect' => false,
            'checkAuthIn' => 'Controller.initialize'
        ]);
        
        $this->_init_language();
    }
    
    public function beforeFilter(Event $event)
    {
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');    // cache for 1 day
        }

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
            }
                                

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                header("Access-Control-Allow-Headers:{$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            }

            exit(0);
        }
        
        
        
        define('APIPageLimit', 3);
        $settings = TableRegistry::get('Settings');
        $query = $settings->find();
        $settings = $query->toArray();
        foreach ($settings as $setting) {
            define(strtoupper($setting->name), $setting->value);
        }
        $this->_setLanguage();
    }
    
    public function _init_language()
    {
        $language = $this->request->params['language'];
        if (empty($language)) {
            $language = 'en';
        }
        switch ($language) {
            case "de":
                I18n::locale('de_DE');
                break;
            case "fr":
                I18n::locale('fr_FR');
                break;
            case "es":
                I18n::locale('es_ES');
                break;
            case "it":
                I18n::locale('it_IT');
                break;
            case "tr":
                I18n::locale('tr_TR');
                break;
            default:
                I18n::locale('en_US');
                break;
        }
    }
    
    private function _setLanguage()
    {
        $session = $this->request->session();
        //echo $session->read('Config.language');
        //pr($this->request->params);die;
        
        if ($this->Cookie->read('language') && !$session->check('Config.language')) {
            $session->write('Config.language', $this->Cookie->read('language'));
        } elseif (isset($this->request->params['language']) && ($this->request->params['language'] !=  $session->read('Config.language'))) {
            $session->write('Config.language', $this->request->params['language']);
            $this->Cookie->write('language', $this->request->params['language'], false, '20 days');
        }
        if (!isset($this->request->params['language'])) {
            $session->write('Config.language', 'en');
            $this->Cookie->write('language', 'en', false, '20 days');
        }
    }
    
    public function sendEmail($from = null, $to = null, $subject = null, $message = null)
    {
        $email = new Email();
        try {
            $email->from($from)
                ->profile('Sendgrid')
                ->to($to)
                ->subject($subject)
                ->emailFormat("both")
                ->template('default')
                ->send($message);
            //$this->Flash->success("Message sent.");
        } catch (Exception $ex) {
            echo 'Exception : ', $ex->getMessage(), "\n";
        }
      //  return $this->redirect(['action' => 'index']);
    }
    
    public function getUserDetail($userId = null)
    {
        $users = TableRegistry::get('Users');
        $userArray = $users
                ->find()
                ->where(['id' => $userId])
                ->first()->toArray();
        return $userArray;
    }
    
    /**
     *Check the user authorization
    */
    public function check_user_authrization()
    {
        
        // echo $this->request->header('userId');die;
        
        if (!empty($this->request->header('Authorization')) && !empty($this->request->header('userId'))) {
            // echo'kamal';die;
            $token =  str_replace("Bearer ", "", $this->request->header('Authorization'));
            $userID = base64_decode($this->request->header('userId'));
            
            $articles = TableRegistry::get('Users');

            // Start a new query.
            $query = $articles->find()
            ->where(['id' => $userID, 'token'=>$token]);
            
            $row = $query->count();
            return $row;
        } else {
            return 0;
        }
    }
        
    public function validateDate($date, $format = 'Y-m-d H:i')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
		
	public function email_template()
	{
		$emailTemplate = '';
		$emailTemplate .= '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta http-equiv="X-UA-Compatible" content="IE=edge">';
		$emailTemplate .= '<meta name="viewport" content="width=device-width, initial-scale=1"><title>Bootstrap 101 Template</title></head>';
		$emailTemplate .= '<body><table width="800px" align="center" border="0" cellpadding="0" cellspacing="0"><tr>';
		$emailTemplate .= '<td align="center" style=" background:#C9ECF0; border:1px solid #ccc; padding:20px;"><img src="'.SITE_URL.'img/front/logo.png"></td></tr>';
        $emailTemplate .= '<tr><td><table width="100%"><tr>';
		$emailTemplate .= '<td style="font-size:18px; font-family:"Colaborate"; padding:20px 20px 10px;font-weight:800;">&nbsp;</td></tr></table></td></tr>';
		$emailTemplate .= '<tr><td><table width="100%"><tr><td style="font-size: 14px; padding:5px 20px 10px;line-height: 1.5;">{CONTENT}</td>';
		$emailTemplate .= '</tr></table></td></tr><tr><td>&nbsp;</td></tr><tr><td>';
		$emailTemplate .= '<table><tr><td style="font-size:18px; font-weight:800;padding: 0 20px 0;">&nbsp;</td></tr></table></td>';
        $emailTemplate .= '</tr></table></body></html>';
		return $emailTemplate;
	}
}
