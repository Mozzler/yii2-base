<?php
namespace mozzler\base\yii\oauth\storage;

use OAuth2\Storage;
use OAuth2\OpenID\Storage\AuthorizationCodeInterface as OpenIDAuthorizationCodeInterface;
use yii\helpers\ArrayHelper;

class MongoDB implements Storage\AuthorizationCodeInterface,
    Storage\UserCredentialsInterface,
    Storage\AccessTokenInterface,
    Storage\ClientCredentialsInterface,
    Storage\RefreshTokenInterface,
    Storage\JwtBearerInterface,
    Storage\PublicKeyInterface,
    Storage\ScopeInterface,
    OpenIDAuthorizationCodeInterface
{
    protected $db = 'mongodb';
    protected $config;

    public function __construct($connection=null, $config = array())
    {
        if ($connection instanceof \yii\mongodb\Connection) {
            $this->db = $connection;
        } else {
	        $this->db = \Yii::$app->get($this->db);
        }
        
        $this->config = ArrayHelper::merge([
            'client_table' => 'mozzler.auth.clients',
            'access_token_table' => 'mozzler.auth.access_tokens',
            'refresh_token_table' => 'mozzler.auth.refresh_tokens',
            'code_table' => 'mozzler.auth.authorization_codes',
            'user_table' => 'mozzler.auth.users',
            'jwt_table' => 'mozzler.auth.jwt',
            'jti_table' => 'mozzler.auth.jti',
            'scope_table'  => 'mozzler.auth.scopes',
            'key_table'  => 'mozzler.auth.keys'
        ], $config);
    }

    /* ClientCredentialsInterface */
    public function checkClientCredentials($client_id, $client_secret = null)
    {
        if ($result = $this->collection('client_table')->findOne(array('client_id' => $client_id))) {
            return $result['client_secret'] == $client_secret;
        }
        return false;
    }

    public function isPublicClient($client_id)
    {
        if (!$result = $this->collection('client_table')->findOne(array('client_id' => $client_id))) {
            return false;
        }
        return empty($result['client_secret']);
    }

    /* ClientInterface */
    public function getClientDetails($client_id)
    {
        $result = $this->collection('client_table')->findOne(array('client_id' => $client_id));
        return is_null($result) ? false : $result;
    }

    public function setClientDetails($client_id, $client_secret = null, $redirect_uri = null, $grant_types = null, $scope = null, $user_id = null)
    {
        if ($this->getClientDetails($client_id)) {
            $result = $this->collection('client_table')->update(
                array('client_id' => $client_id),
                array('$set' => array(
                    'client_secret' => $client_secret,
                    'redirect_uri'  => $redirect_uri,
                    'grant_types'   => $grant_types,
                    'scope'         => $scope,
                    'user_id'       => $user_id,
                ))
            );
            return $result > 0;
        }
        $client = array(
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri'  => $redirect_uri,
            'grant_types'   => $grant_types,
            'scope'         => $scope,
            'user_id'       => $user_id,
        );
        $result = $this->collection('client_table')->insert($client);
        return !is_null($result);
    }

    public function checkRestrictedGrantType($client_id, $grant_type)
    {
        $details = $this->getClientDetails($client_id);
        if (isset($details['grant_types'])) {
            $grant_types = explode(' ', $details['grant_types']);
            return in_array($grant_type, $grant_types);
        }
        // if grant_types are not defined, then none are restricted
        return true;
    }

    /* AccessTokenInterface */
    public function getAccessToken($access_token)
    {
        $token = $this->collection('access_token_table')->findOne(array('access_token' => $access_token));
        \Yii::trace(print_r($token,true));
        return is_null($token) ? false : $token;
    }

    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null)
    {
        // if it exists, update it.
        if ($this->getAccessToken($access_token)) {
            $result = $this->collection('access_token_table')->update(
                array('access_token' => $access_token),
                array('$set' => array(
                    'client_id' => $client_id,
                    'expires' => $expires,
                    'user_id' => $user_id,
                    'scope' => $scope
                ))
            );

            return $result > 0;
        }
        $token = array(
            'access_token' => $access_token,
            'client_id' => $client_id,
            'expires' => $expires,
            'user_id' => $user_id,
            'scope' => $scope
        );
        $result = $this->collection('access_token_table')->insert($token);
        
        return !is_null($result);
    }

    public function unsetAccessToken($access_token)
    {
        $result = $this->collection('access_token_table')->remove([
            'access_token' => $access_token
        ]);
        
        return true;
    }

    /* AuthorizationCodeInterface */
    public function getAuthorizationCode($code)
    {
        $code = $this->collection('code_table')->findOne(array(
            'authorization_code' => $code
        ));
        return is_null($code) ? false : $code;
    }

    public function setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null, $id_token = null)
    {
        // if it exists, update it.
        if ($this->getAuthorizationCode($code)) {
            $result = $this->collection('code_table')->updateOne(
                array('authorization_code' => $code),
                array('$set' => array(
                    'client_id' => $client_id,
                    'user_id' => $user_id,
                    'redirect_uri' => $redirect_uri,
                    'expires' => $expires,
                    'scope' => $scope,
                    'id_token' => $id_token,
                ))
            );
            return $result->getMatchedCount() > 0;
        }
        $token = array(
            'authorization_code' => $code,
            'client_id' => $client_id,
            'user_id' => $user_id,
            'redirect_uri' => $redirect_uri,
            'expires' => $expires,
            'scope' => $scope,
            'id_token' => $id_token,
        );
        $result = $this->collection('code_table')->insert($token);

        return !is_null($result);
    }

    public function expireAuthorizationCode($code)
    {
        $result = $this->collection('code_table')->remove([
            'authorization_code' => $code
        ]);
        
        return true;
    }

    /* UserCredentialsInterface */
    public function checkUserCredentials($username, $password)
    {
        if ($user = $this->getUser($username)) {
            return $this->checkPassword($user, $password);
        }
        
        return false;
    }

    public function getUserDetails($username)
    {
	    $details = [];
        if ($user = $this->getUser($username)) {
	        $usernameField = $user::$usernameField;
            $details['user_id'] = $user->$usernameField;
        }
        return $details;
    }

    /* RefreshTokenInterface */
    public function getRefreshToken($refresh_token)
    {
        $token = $this->collection('refresh_token_table')->findOne(array(
            'refresh_token' => $refresh_token
        ));

        return is_null($token) ? false : $token;
    }

    public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null)
    {
        $token = array(
            'refresh_token' => $refresh_token,
            'client_id' => $client_id,
            'user_id' => $user_id,
            'expires' => $expires,
            'scope' => $scope
        );
        $result = $this->collection('refresh_token_table')->insert($token);

        return !is_null($result);
    }

    public function unsetRefreshToken($refresh_token)
    {
        $result = $this->collection('refresh_token_table')->remove([
            'refresh_token' => $refresh_token
        ]);
        
        return true;
    }

    // plaintext passwords are bad!  Override this for your application
    protected function checkPassword($user, $password)
    {
        return $user->validatePassword($password);
    }

    public function getUser($username)
    {
	    $identity = \Yii::createObject(\Yii::$app->user->identityClass);
	    $user = $identity::findByUsername($username);
	    
        //$result = $this->collection('user_table')->findOne(array('username' => $username));
        return is_null($user) ? false : $user;
    }

    public function setUser($username, $password, $firstName = null, $lastName = null)
    {
	    $user = $this->getUser($username);
        if ($user) {
	        $user->password = $password;
	        return $user->save();
        }
        
        $user = \Yii::createObject(\Yii::$app->user->identityClass);
        $usernameField = $user::$usernameField;

        $user->$usernameField = $username;
        $user->password = $password;
        return $user->save();
    }

    public function getClientKey($client_id, $subject)
    {
        $result = $this->collection('jwt_table')->findOne(array(
            'client_id' => $client_id,
            'subject' => $subject
        ));
        return is_null($result) ? false : $result['key'];
    }

    public function getClientScope($client_id)
    {
        if (!$clientDetails = $this->getClientDetails($client_id)) {
            return false;
        }
        if (isset($clientDetails['scope'])) {
            return $clientDetails['scope'];
        }
        return null;
    }

    public function getJti($client_id, $subject, $audience, $expires, $jti)
    {
        //TODO: Needs mongodb implementation.
        throw new \Exception('getJti() for the MongoDB driver is currently unimplemented.');
    }

    public function setJti($client_id, $subject, $audience, $expires, $jti)
    {
        //TODO: Needs mongodb implementation.
        throw new \Exception('setJti() for the MongoDB driver is currently unimplemented.');
    }

    public function getPublicKey($client_id = null)
    {
        if ($client_id) {
            $result = $this->collection('key_table')->findOne(array(
                'client_id' => $client_id
            ));
            if ($result) {
                return $result['public_key'];
            }
        }

        $result = $this->collection('key_table')->findOne(array(
            'client_id' => null
        ));
        return is_null($result) ? false : $result['public_key'];
    }

    public function getPrivateKey($client_id = null)
    {
        if ($client_id) {
            $result = $this->collection('key_table')->findOne(array(
                'client_id' => $client_id
            ));
            if ($result) {
                return $result['private_key'];
            }
        }

        $result = $this->collection('key_table')->findOne(array(
            'client_id' => null
        ));
        return is_null($result) ? false : $result['private_key'];
    }

    public function getEncryptionAlgorithm($client_id = null)
    {
        if ($client_id) {
            $result = $this->collection('key_table')->findOne(array(
                'client_id' => $client_id
            ));
            if ($result) {
                return $result['encryption_algorithm'];
            }
        }

        $result = $this->collection('key_table')->findOne(array(
            'client_id' => null
        ));
        return is_null($result) ? 'RS256' : $result['encryption_algorithm'];
    }

    // Helper function to access a MongoDB collection by `type`:
    protected function collection($name)
    {
        return $this->db->getCollection($this->config[$name]);
    }
    
    //for ScopeInterface
    public function scopeExists($scope){}
    public function getDefaultScope($client_id = null){}
}