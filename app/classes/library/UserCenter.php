<?php
/**
 * UserCenter Library Class
 *
 * @package     default
 * @author      Jokoku Xing <xuguoxing502@gmail.com>
 * @copyright   2013 PlayCrab Inc.
 * @version     Release: 0.1
 */
class UserCenter
{
    const  USERLOGINURL = 'http://usercenter.playcrab.com/www/SSO/login.php';
	const  USERINFOURL  = 'http://usercenter.playcrab.com/www/us_api.php';
    const  USERPMURL    = 'http://usercenter.playcrab.com/pm/index.php';

    // 登陆认证 生存周期
    const  LEFTTIME = 3600; 

	public $system = '';
	public $project = '';
	public $platform = '';

    private $_isProxy = false;
    private $_host = '';
    private $_userInfo = array();

    /**
     * construct to the UserCenter
     *
     * @author Jokoku Xing
     * @return null
     */
	public function __construct()
	{
        session_set_cookie_params(self::LEFTTIME); 
        session_cache_expire(self::LEFTTIME);
        if(!isset($_SESSION)) session_start();

        $this->_host = "http://" . $_SERVER['HTTP_HOST'];
	}

    /**
     * Get UserCenter Object
     *
     * @return object
     * @author Jokoku Xing
     */
    public static function getInstance()
    {
        static $uc_instance = null;
        if (is_null($uc_instance)) {
            $uc_instance = new UserCenter();
        }
        return $uc_instance;
    }

    /**
     * Init To UserCenter
     *
     * @param string $system_id 系统id
     * @param string $project   游戏项目
     * @param string $platform  游戏平台
     *
     * @author Jokoku Xing
     * @return null
     */
    public function init($system_id, 
                    $project, 
                    $platform) 
    {
		$this->system_id = $system_id;
		$this->project = $project;
		$this->platform = $platform;
    }

    /**
     * Check is Login 
     *
     * @author Jokoku Xing
     * @return bool
     */
    public function isLogined() 
    {
        $is_token = self::checkCookieToken();
        $is_user = self::checkSessionUser();
        if ($is_token['status'] && $is_user['status']) {
            return true;
        } else {
            //$this->logout();
            setcookie('uc_token', '', time() - 1);
            unset($_SESSION['uc_user']);
            //session_destroy();
            return false;
        }
    }

    /**
     * Login To UserCenter
     *
     * @param string $redirect_url 
     * 
     * @author Jokoku Xing
     * @return bool
     */
    public function login($redirect_url) 
    {
        if (!$this->isLogined()) {
            if (empty($redirect_url)) {
                throw new  UserCenterException('error,请传递redirect_url参数!');
            }

    		$url = urlencode($redirect_url); 

    		$login_url = self::USERLOGINURL
                    .'?url=' 
                    . $url . '&host=' 
                    . $this->_host;

    		header("location:$login_url");
    		exit();
        } else {
            return true;
        }
    }

    /**
     * CheckPassword To UserCenter
     *
     * @param string $username 
     * @param string $password 
     *
     * @author Jokoku Xing
     * @return array
     */
    public function checkPassword($username, $password) 
    {
        if (empty($username) || empty($password)) {
            throw new UserCenterException('error,缺少用户名或密码!');
        }
        $userinfo_url = self::USERINFOURL;
        $params = array(
            'action' => 'checkPassword',
            'username' => $username,
            'password' => $password,
        );
        $client = WebClient::call($userinfo_url, $params, $this->_isProxy);
        $response = $client->getResponse();

        $data = json_decode($response, true);

        if ('0' == $data['error']) {
            $this->_userInfo = $data['info'];
            //$_SESSION['uc_user'] = $data['info'];
        } else {
            throw new UserCenterException('error,用户名或密码错误!');
        }
        return $data['info'];
    }

    /**
     * 从新权限系统(pm)中获取用户角色及权限
     *   
     * @author Zhao Yong  
     * @author Jokoku Xing
     *
     * @return array
     */
    public function getPmRolesByUser()
    {
        if (count($this->_userInfo) > 0) {
            $username = $this->_userInfo['username'];
            $role_url = self::USERPMURL;

            $params = array(
                'r' => 'Api/GetFuncOfUser',
                'sysId' => $this->system_id,
                'account' => $username,
                'needRoleOfUser' => 1,
                'isKeyArr' => 1,
            );
            $client = WebClient::call($role_url, $params, $this->_isProxy);
            $response = $client->getResponse();
            $pmInfos = json_decode($response, true);

            return $pmInfos;
        } else {
            throw new UserCenterException('error,清先登录!');
        }
    }

    /**
     * Get Userinfo need $_COOKIE['uc_token']
     *
     * @author Jokoku Xing
     * @return bool
     */
    public function getUserinfo() 
    {
        //$is_user = self::checkSessionUser();
        //if (!$is_user['status']) {
            $token = self::makeCrypt($_COOKIE['uc_token'], 'decode');
            //print_r($token); exit;
            $userinfo_url = self::USERINFOURL;
            $params = array(
                'action' => 'getUserinfo',
                'token' => self::makeCrypt(@$_COOKIE['uc_token'], 'decode'),
                'system_id' => $this->system_id,
                'project' => $this->project,
            );

            $client = WebClient::call($userinfo_url, $params, $this->_isProxy);
            $response = $client->getResponse();
            //print_r($response);exit;
            $user = json_decode($response, true);

            $_SESSION['uc_user'] = $user;  
        //} else {
            //$user = @$_SESSION['uc_user']; 
        //}
        return $user;
    }

    /**
     * Logout
     *
     * @param string $redirect_url 
     *
     * @author Jokoku Xing
     * @return null
     */
    public function logout($redirect_url='') 
    {
        setcookie('uc_token', '', time() - 1);
        unset($_SESSION['uc_user']);
        session_destroy();

        if ('' == $redirect_url) {
            $redirect_url = $this->_host;
        }
        $url = urlencode($redirect_url);

        $logout_url = self::USERLOGINURL
            .'?action=logout&url=' 
            . $url . '&host=' . $this->_host;
        header('Location:' . $logout_url);
        exit();
    }

    /**
     * Get Roles
     *
     * @author Jokoku Xing
     * @return array
     */
    public function getRoles() 
    {
    	if ($this->isLogined()) {
    		return $_SESSION['roles'];
    	} else {
    		throw new UserCenterException('error,请先登录!');
    	}
    }

    /**
     * Get All Roles
     *
     * @author Jokoku Xing
     * @return array
     */
    public function getAllRoles() 
    {
        if ($this->isLogined()) {
            //return $_SESSION['roles'];
            $userinfo_url = self::USERINFOURL;
            $params = array(
                'action' => 'getAllRoles',
                'token' => self::makeCrypt(@$_COOKIE['uc_token'], 'decode'),
                'system_id' => $this->system_id,
                'project' => $this->project,
            );
            $client = WebClient::call($userinfo_url, $params, $this->_isProxy);
            $response = $client->getResponse();

            $allroles = json_decode($response, true);
            return $allroles;
        } else {
            throw new UserCenterException('error,请先登录!');
        }
    }

    /**
     * Get User Name
     *
     * @author Jokoku Xing
     * @return string
     */
    public function getUserName() 
    {
    	if ($this->isLogined()) {
    		return $_SESSION['username'];
    	} else {
    		throw new UserCenterException('error,请先登录!');
    	}
    }

    /**
     * Get Name
     *
     * @author Jokoku Xing
     * @return string
     */
    public function getName() 
    {
    	if ($this->isLogined()) {
            return $_SESSION['name'];
    	} else {
    		throw new UserCenterException('error,请先登录!');
    	}
    }

    /**
     * Get Uid
     *
     * @author Jokoku Xing
     * @return string
     */
    public function getUid() 
    {
    	if ($this->isLogined()) {
    		return $_SESSION['id'];
    	} else {
    		throw new UserCenterException('error,请先登录!');
    	}
    }

    /**
     * Set HttpProxy
     *
     * @author Jokoku Xing
     * @return null
     */
    public function setHttpProxy() 
    {
        $this->_isProxy = true;
    }

    /**
     * Check Cookie
     *
     * @return array
     */
    public static function checkCookieToken()
    {
        if (isset($_COOKIE) && !empty($_COOKIE['uc_token'])) {
            $is_token['status'] = true;
        }  else {
            $is_token['status'] = false;
        }
        return $is_token;
    }

    /**
     * Check Session
     *
     * @return array
     */
    public static function checkSessionUser()
    {
        if (!empty($_SESSION['uc_user'])) {
            $is_token['status'] = true;
        }  else {
            $is_token['status'] = false;
        }
        return $is_token;
    }

    /**
     * Encryption And Decryption
     *     
     * @param string $date
     * @param string $mode 
     *
     * @return array
     */
	public static function makeCrypt($date, $mode='encode') {
        
        // 用MD5哈希生成一个密钥，注意加密和解密的密钥必须统一
	    $key = md5('PlayCrab123');
	    if ($mode == 'decode') {
	        $date = base64_decode($date);
	    }
	    if (function_exists('mcrypt_create_iv')) {
	        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
	        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	    }
	    if (isset($iv) && $mode == 'encode') {
	        $passcrypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $date, MCRYPT_MODE_ECB, $iv);
	    } elseif (isset($iv) && $mode == 'decode') {
	        $passcrypt = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $date, MCRYPT_MODE_ECB, $iv);
	        $passcrypt = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $date, MCRYPT_MODE_ECB, $iv);
	    }
	    if ($mode == 'encode') {
	        $passcrypt = base64_encode($passcrypt);
	    }
	    return trim($passcrypt);
	}

}




/**
 * WebClient
 *
 * @package     default
 * @author      Jokoku Xing <xuguoxing502@gmail.com>
 * @copyright   2013 PlayCrab Inc.
 * @version     Release: 0.1
 */
class WebClient 
{
    private $_curl;
    private $_url;
    private $_response = "";
    private $_params = null;

    public function __construct() 
    {
        $this->_curl = curl_init();
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);

        // This verbose option for extracting the headers
        curl_setopt($this->_curl, CURLOPT_HEADER, false); 
    }

    /**
     * Execute the call to the webservice
     *
     * @author Jokoku Xing
     * @return WebClient
     */
    public function execute()
    {
        $this->_treatURL();
        curl_setopt($this->_curl, CURLOPT_URL, $this->_url);
        $r = curl_exec($this->_curl);

        // Extract the response
        $this->_treatResponse($r); 
        return $this;
    }

    /**
     * Treats Response
     *
     * @author Jokoku Xing
     * @return void
     */
    private function _treatResponse($r)
    {
         $this->_response = $r;
    }

    /**
     * Treats URL
     *
     * @author Jokoku Xing
     * @return void
     */
    private function _treatURL()
    {
        // Transform parameters in key/value pars in URL
        if (is_array($this->_params) && count($this->_params) >= 1) { 
            if (!strpos($this->_url, '?')) {
                $this->_url .= '?' ;
            }
            foreach ($this->_params as $k=>$v) {
                $this->_url .= "&" . urlencode($k) . "=" . urlencode($v);
            }
        }
        return $this->_url;
    }

    /**
     * This closes the connection and release resources
     *
     * @author Jokoku Xing
     * @return WebClient
     */
    public function close()
    {
        curl_close($this->_curl);
        $this->_curl = null ;
        return $this;
    }

    /**
     * Sets the URL to be Called
     *
     * @param string $url The web service address
     *
     * @author Jokoku Xing
     * @return WebClient
     */
    public function setUrl($url)
    {
        $this->_url = $url;
        return $this;
    }

    /**
     * Sets the Params to be Called
     *
     * @param array $params The web service params
     *
     * @author Jokoku Xing
     * @return WebClient
     */
    public function setParameters($params)
    {
        $this->_params = $params;
        return $this;
    }

    /**
     * Sets the HttpProxy to be Called (for QQ)
     *
     * @param string $is_proxy   The HTTP Proxy Switch
     *
     * @author Jokoku Xing
     * @return WebClient
     */
    public function setHttpProxy($is_proxy) 
    {
        if ($is_proxy) {
            curl_setopt($this->_curl, CURLOPT_PROXY, '10.172.48.92:3300');
        }
        return $this;
    }

    /**
     * Creates the WebClient
     *
     * @param string $url [optional] web service adress
     *
     * @author Jokoku Xing
     * @return WebClient
     */
    public static function createClient($url=null)
    {
        $client = new WebClient ;
        if ($url != null) {
            $client->setUrl($url);
        }
        return $client;
    }

    /**
     * get response content
     *
     * @return string
     */
    public function getResponse()
    {
        return $this->_response ;
    }

    /**
     * Convenience method wrapping a commom custom call
     *
     * @param string $url    Web service address
     * @param string $params   The request params
     * @param string $is_proxy   The HTTP Proxy Switch
     *
     * @author Jokoku Xing
     * @return WebClient
     */
    public static function call($url, $params, $is_proxy=false)
    {
        return self::createClient($url)
            ->setParameters($params)
            ->setHttpProxy($is_proxy)
            ->execute()
            ->close();
    }

}

/**
 * UC异常类
 *
 * PHP version 5
 *
 * @package    default
 * @author     Jokoku Xing <xuguoxing502@gmail.com>
 * @copyright  2013 PlayCrab Inc.
 * @version    Release: 0.1
 */

class UserCenterException extends \Exception
{
    //todo
} // END class UserCenterException

?>