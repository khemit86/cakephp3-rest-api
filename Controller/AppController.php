<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\I18n\I18n;
use \Hashids\Hashids;
use Cake\Network\Email\SendgridTransport;
use Cake\Network\Email\Email;
use Cake\Routing\Router;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link http://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{

    
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('Security');`
     *
     * @return void
     */
    public $adminLanguage = array('German'=>'de','France'=>'fr','Spain'=>'es','Italy'=>'it','Turkey'=>'tr');
    public $rangeSearch = array('10'=>'10','20'=>'20','50'=>'50','100'=>'100');

    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->loadComponent('Flash');
        $this->loadComponent('Cookie');
        $this->loadComponent('Auth');
    }
    
    
    public function beforeFilter(Event $event)
    {
        if (isset($this->request->params['prefix']) && $this->request->params['prefix'] == 'admin') {
            $this->Auth->allow(['login']);
        } else {
            if (!isset($this->request->params['language'])) {
                $this->redirect(array('language' => 'en'));
            }
            $this->_setLanguage();
        }
        
        $this->set('admin_language', $this->adminLanguage);
        $this->set('rangeSearch', $this->rangeSearch);
    }
    
    public function redirect($url, $status = null, $exit = true)
    {
        if (isset($this->request->params['prefix']) && $this->request->params['prefix']=='admin') {
            parent::redirect($url, $status, $exit);
        } else {
            $session = $this->request->session();
            if ($session->check('Config.language')) {
                if (is_array($url) && !isset($url['language'])) {
                    $url['language'] = $session->read('Config.language');
                } else {
                    if ($this->request->params['action']!='googleLogin') {
                        $url['language'] = $session->read('Config.language');
                    }
                }
            }
            
            parent::redirect($url, $status, $exit);
        }
    }
    
    public function _init_language()
    {
        $language = !isset($this->request->params['language'])   ?   $this->request->session()->read('Config.language')
                                    :   $this->request->params['language'];
        
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

        if ($this->Cookie->read('language') && !$session->check('Config.language')) {
            $session->write('Config.language', $this->Cookie->read('language'));
        } elseif (isset($this->request->params['language']) && ($this->request->params['language'] !=  $session->read('Config.language'))) {
            $session->write('Config.language', $this->request->params['language']);
            $this->Cookie->write('language', $this->request->params['language'], false, '20 days');
        } elseif (!isset($this->request->params['language'])) {
            $session->write('Config.language', 'en');
            $this->Cookie->write('language', 'en', false, '20 days');
        }
    }
    
    /**
     * Before render callback.
     *
     * @param \Cake\Event\Event $event The beforeRender event.
     * @return \Cake\Network\Response|null|void
     */
    public function beforeRender(Event $event)
    {
        if (!array_key_exists('_serialize', $this->viewVars) &&
            in_array($this->response->type(), ['application/json', 'application/xml'])
        ) {
            $this->set('_serialize', true);
        }
    }
    
    
    
    public function isAuthorized($user)
    {
        // Admin can access every action
        if (isset($user['role_id']) && $user['role_id'] === '1') {
            return true;
        }

        // Default deny
        return false;
    }
    
    public $hashids = [
        'salt' => 'qwiqfix@123',
        'min_hash_length' => 32,
        'alphabet' => 'abcdefghijklmnopqrstuvwxyz0123456789'
    ];
 
    /**
     * Hashids function
     *
     * @return object
     */
    public function hashids()
    {
        return $hashids = new Hashids(
            $this->hashids['salt'],
            $this->hashids['min_hash_length'],
            $this->hashids['alphabet']
        );
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
        } catch (Exception $ex) {
            echo 'Exception : ', $ex->getMessage(), "\n";
        }
    }
}
