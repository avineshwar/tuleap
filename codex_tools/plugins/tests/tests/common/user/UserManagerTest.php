<?php

require_once('common/user/User.class.php');
Mock::generate('User');
require_once('common/dao/UserDao.class.php');
Mock::generate('UserDao');
require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');
require_once('common/include/Response.class.php');
Mock::generate('Response');
require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once('common/event/EventManager.class.php');
Mock::generate('EventManager');

require_once('common/user/UserManager.class.php');
Mock::generatePartial('UserManager', 
                      'UserManagerTestVersion', 
                      array('_getUserInstanceFromRow', 
                            '_getCookieManager', 
                            '_getServerIp', 
                            'generateSessionHash',
                            '_getPasswordLifetime',
                            '_getEventManager',
                            'getDao',
                      )
);

require_once('common/include/CookieManager.class.php');
Mock::generate('CookieManager');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 *
 * Tests the class User
 */
class UserManagerTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function UserManagerTest($name = 'User Manager test') {
        $this->UnitTestCase($name);
    }
    
    function setUp() {
        $GLOBALS['Response'] = new MockResponse($this);
        $GLOBALS['Language'] = new MockBaseLanguage($this);
    }
    function tearDown() {
        unset($GLOBALS['Response']);
        unset($GLOBALS['Language']);
    }
    
    function testCachingById() {
        $dao = new MockUserDao($this);
        $dar = new MockDataAccessResult($this);
        $dao->setReturnReference('searchByUserId', $dar);
        $dar->setReturnValueAt(0, 'getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $dar->setReturnValueAt(1, 'getRow', false);
        
        $dao->expectOnce('searchByUserId', array(123));
        
        $user123 = new MockUser($this);
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user456 = new MockUser($this);
        $user456->setReturnValue('getId', 456);
        $user456->setReturnValue('getUserName', 'user_456');
        
        $um = new UserManagerTestVersion($this);
        $um->setReturnReference('_getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        $um->setReturnReference('_getUserInstanceFromRow', $user456, array(array('user_name' => 'user_456', 'user_id' => 456)));
        
        $um->setReturnReference('getDao', $dao);
        $user_1 = $um->getUserById(123);
        $user_2 = $um->getuserById(123);
        $this->assertReference($user_1, $user_2);
        
    }
    
    function testCachingByUserName() {
        $dao = new MockUserDao($this);
        $dar = new MockDataAccessResult($this);
        $dao->setReturnReference('searchByUserName', $dar);
        $dar->setReturnValueAt(0, 'getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $dar->setReturnValueAt(1, 'getRow', false);
        
        $dao->expectOnce('searchByUserName', array('user_123'));
        
        $user123 = new MockUser($this);
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user456 = new MockUser($this);
        $user456->setReturnValue('getId', 456);
        $user456->setReturnValue('getUserName', 'user_456');
        
        $um = new UserManagerTestVersion($this);
        $um->setReturnReference('_getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        $um->setReturnReference('_getUserInstanceFromRow', $user456, array(array('user_name' => 'user_456', 'user_id' => 456)));
        
        $um->setReturnReference('getDao', $dao);
        $user_1 = $um->getUserByUserName('user_123');
        $user_2 = $um->getuserByUserName('user_123');
        $this->assertReference($user_1, $user_2);
        
    }
    
    function testDoubleCaching() {
        $dao = new MockUserDao($this);
        $dar_123 = new MockDataAccessResult($this);
        $dao->setReturnReference('searchByUserId', $dar_123, array(123));
        $dar_123->setReturnValueAt(0, 'getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $dar_123->setReturnValueAt(1, 'getRow', false);
        $dar_456 = new MockDataAccessResult($this);
        $dao->setReturnReference('searchByUserName', $dar_456, array('user_456'));
        $dar_456->setReturnValueAt(0, 'getRow', array('user_name' => 'user_456', 'user_id' => 456));
        $dar_456->setReturnValueAt(1, 'getRow', false);
        
        $dao->expectOnce('searchByUserId', array(123));
        $dao->expectOnce('searchByUserName', array('user_456'));
        
        $user123 = new MockUser($this);
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user456 = new MockUser($this);
        $user456->setReturnValue('getId', 456);
        $user456->setReturnValue('getUserName', 'user_456');
        
        $um = new UserManagerTestVersion($this);
        $um->setReturnReference('_getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        $um->setReturnReference('_getUserInstanceFromRow', $user456, array(array('user_name' => 'user_456', 'user_id' => 456)));
        
        $um->setReturnReference('getDao', $dao);
        $user_1 = $um->getUserById(123);
        $user_2 = $um->getUserByUserName('user_123');
        $this->assertReference($user_1, $user_2);
        $user_3 = $um->getUserByUserName('user_456');
        $user_4 = $um->getuserById(456);
        $this->assertReference($user_3, $user_4);
    }
    
    function testIsLoaded() {
        $dao = new MockUserDao($this);
        $dar = new MockDataAccessResult($this);
        $dao->setReturnReference('searchByUserId', $dar);
        $dar->setReturnValueAt(0, 'getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $dar->setReturnValueAt(1, 'getRow', false);
        
        $dao->expectOnce('searchByUserId', array(123));
        
        $user123 = new MockUser($this);
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        
        $um = new UserManagerTestVersion($this);
        $um->setReturnReference('_getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        
        $um->setReturnReference('getDao', $dao);
        $this->assertFalse($um->isUserLoadedById(123));
        $this->assertFalse($um->isUserLoadedByUserName('user_123'));
        $um->getUserById(123);
        $this->assertTrue($um->isUserLoadedById(123));
        $this->assertTrue($um->isUserLoadedByUserName('user_123'));
    }
    
    function testEmptySessionHash() {
        $cm               = new MockCookieManager($this);
        $userAnonymous    = new MockUser($this);
        $dao              = new MockUserDao($this);
        $um               = new UserManagerTestVersion($this);
        
        $userAnonymous->setReturnValue('getId', 0);
        $userAnonymous->setReturnValue('isAnonymous', true);
        
        $cm->setReturnValue('getCookie', '');
        
        $um->setReturnReference('getDao', $dao);
        $um->setReturnReference('_getCookieManager', $cm);
        $um->setReturnReference('_getUserInstanceFromRow', $userAnonymous, array(array('user_id' => 0)));
        
        //expect that the user is cached
        $um->expectOnce('_getUserInstanceFromRow');
        
        $user = $um->getCurrentUser();
        $this->assertTrue($user->isAnonymous(), 'An empty session hash gives an anonymous user');
        
        $this->assertReference($user, $um->getUserById($user->getId()));
    }
    
    function testValidSessionHash() {
        $cm               = new MockCookieManager($this);
        $dar_valid_hash   = new MockDataAccessResult($this);
        $user123          = new MockUser($this);
        $dao              = new MockUserDao($this);
        $um               = new UserManagerTestVersion($this);
        
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user123->setReturnValue('isAnonymous', false);

        $cm->setReturnValue('getCookie', 'valid_hash');
        $um->setReturnValue('_getServerIp', '212.212.123.12');
        $dar_valid_hash->setReturnValue('getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $dao->setReturnReference('searchBySessionHashAndIp', $dar_valid_hash, array('valid_hash', '212.212.123.12'));
        $um->setReturnReference('_getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        
        $dao->expectOnce('storeLastAccessDate', array(123, '*'));
        
        $um->setReturnReference('getDao', $dao);
        $um->setReturnReference('_getCookieManager', $cm);
        
        $user = $um->getCurrentUser();
        $this->assertFalse($user->isAnonymous(), 'An valid session hash gives a registered user');
    }
    
    function testInvalidSessionHash() {
        $cm               = new MockCookieManager($this);
        $dar_invalid_hash = new MockDataAccessResult($this);
        $userAnonymous    = new MockUser($this);
        $dao              = new MockUserDao($this);
        $um               = new UserManagerTestVersion($this);
        
        $userAnonymous->setReturnValue('getId', 0);
        $userAnonymous->setReturnValue('isAnonymous', true);

        $cm->setReturnValue('getCookie', 'invalid_hash');
        $um->setReturnValue('_getServerIp', '212.212.123.12');
        $dar_invalid_hash->setReturnValue('getRow', false);
        $dao->setReturnReference('searchBySessionHashAndIp', $dar_invalid_hash, array('invalid_hash', '212.212.123.12'));
        $um->setReturnReference('_getUserInstanceFromRow', $userAnonymous, array(array('user_id' => 0)));
        
        $um->setReturnReference('getDao', $dao);
        $um->setReturnReference('_getCookieManager', $cm);
        
        $user = $um->getCurrentUser();
        $this->assertTrue($user->isAnonymous(), 'An invalid session hash gives an anonymous user');
    }
    
    function testInvalidIp() {
        $cm               = new MockCookieManager($this);
        $dar_invalid      = new MockDataAccessResult($this);
        $userAnonymous    = new MockUser($this);
        $dao              = new MockUserDao($this);
        $um               = new UserManagerTestVersion($this);
        
        $userAnonymous->setReturnValue('getId', 0);
        $userAnonymous->setReturnValue('isAnonymous', true);
        
        $cm->setReturnValue('getCookie', 'valid_hash');
        $um->setReturnValue('_getServerIp', 'in.val.id.ip');
        $dar_invalid->setReturnValue('getRow', false);
        $dao->setReturnReference('searchBySessionHashAndIp', $dar_invalid, array('valid_hash', 'in.val.id.ip'));
        $um->setReturnReference('_getUserInstanceFromRow', $userAnonymous, array(array('user_id' => 0)));
        
        $um->setReturnReference('getDao', $dao);
        $um->setReturnReference('_getCookieManager', $cm);
        
        $user = $um->getCurrentUser();
        $this->assertTrue($user->isAnonymous(), 'An invalid ip gives an anonymous user');
    }
    
    function testSessionContinue() {
        $cm               = new MockCookieManager($this);
        $dar_invalid_hash = new MockDataAccessResult($this);
        $dar_valid_hash   = new MockDataAccessResult($this);
        $user123          = new MockUser($this);
        $userAnonymous    = new MockUser($this);
        $dao              = new MockUserDao($this);
        $um               = new UserManagerTestVersion($this);
        
        $userAnonymous->setReturnValue('getId', 0);
        $userAnonymous->setReturnValue('isAnonymous', true);
        
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user123->setReturnValue('isAnonymous', false);
        $user123->expectOnce('setSessionHash', array('valid_hash'));

        $cm->setReturnValue('getCookie', 'empty_hash');
        $um->setReturnValue('_getServerIp', '212.212.35.25');
        $dao->setReturnReference('searchBySessionHashAndIp', $dar_invalid_hash, array('empty_hash', ''));
        $dar_invalid_hash->setReturnValue('getRow', false);
        $um->setReturnReference('_getUserInstanceFromRow', $userAnonymous, array(array('user_id' => 0)));
        
        $dao->setReturnReference('searchBySessionHashAndIp', $dar_valid_hash, array('valid_hash', '212.212.35.25'));
        $dar_valid_hash->setReturnValue('getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $um->setReturnReference('_getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        
        $um->setReturnReference('getDao', $dao);
        $um->setReturnReference('_getCookieManager', $cm);
        
        $user1 = $um->getCurrentUser();
        $this->assertTrue($user1->isAnonymous(), 'An invalid ip gives an anonymous user');
        
        //The user is cached
        $user2 = $um->getCurrentUser();
        $this->assertTrue($user2->isAnonymous(), 'An invalid ip gives an anonymous user');
        
        //Force refresh by providing a session_hash.
        //This will continue the session for the protocols 
        //which don't handle cookies
        $user3 = $um->getCurrentUser('valid_hash');
        $this->assertFalse($user3->isAnonymous(), 'The session can be continued');
    }
    
    function testLogout() {
        $cm               = new MockCookieManager($this);
        $dar_valid_hash   = new MockDataAccessResult($this);
        $user123          = new MockUser($this);
        $dao              = new MockUserDao($this);
        $um               = new UserManagerTestVersion($this);
        
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user123->setReturnValue('isAnonymous', false);
        $user123->setReturnValue('getSessionHash', 'valid_hash');
        $user123->expectAt(0, 'setSessionHash', array('valid_hash'));
        $user123->expectAt(1, 'setSessionHash', array(false));

        $cm->setReturnValue('getCookie', 'valid_hash');
        $cm->expectOnce('removeCookie', array('session_hash'));
        $um->setReturnValue('_getServerIp', '212.212.123.12');
        $dao->expectOnce('deleteSession', array('valid_hash'));
        $dao->setReturnReference('searchBySessionHashAndIp', $dar_valid_hash, array('valid_hash', '212.212.123.12'));
        $dar_valid_hash->setReturnValue('getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $um->setReturnReference('_getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        
        $um->setReturnReference('getDao', $dao);
        $um->setReturnReference('_getCookieManager', $cm);
        
        $user = $um->getCurrentUser();
        $um->logout();
    }
    
    function testGoodLogin() {
        $cm               = new MockCookieManager($this);
        $dao              = new MockUserDao($this);
        $dar              = new MockDataAccessResult($this);
        $user123          = new MockUser($this);
        $um               = new UserManagerTestVersion($this);
        $em               = new MockEventManager($this);
        
        $um->setReturnReference('_getEventManager', $em);
        
        $hash = 'valid_hash';
        $dao->setReturnValue('createSession', $hash);
        
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user123->setReturnValue('getUserPw', md5('pwd'));
        $user123->setReturnValue('getStatus', 'A');
        $user123->setReturnValue('isAnonymous', false);
        $user123->expectOnce('setSessionHash', array($hash));
        
        $cm->expectOnce('setCookie', array('session_hash', $hash, 0));
        $um->setReturnReference('_getCookieManager', $cm);
        
        $dao->setReturnReference('searchByUserName', $dar, array('user_123'));
        $dar->setReturnValue('getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $um->setReturnReference('_getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        
        $dao->expectNever('storeLoginFailure');
        
        $um->setReturnReference('getDao', $dao);
        $this->assertReference($user123, $um->login('user_123', 'pwd', 0));
    }
    
    function testBadLogin() {
        $cm               = new MockCookieManager($this);
        $dao              = new MockUserDao($this);
        $dar              = new MockDataAccessResult($this);
        $user123          = new MockUser($this);
        $userAnonymous    = new MockUser($this);
        $um               = new UserManagerTestVersion($this);
        $em               = new MockEventManager($this);
        
        $um->setReturnReference('_getEventManager', $em);
        
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user123->setReturnValue('getUserPw', md5('pwd'));
        $user123->setReturnValue('isAnonymous', false);
        $user123->expectNever('setSessionHash');
        
        $userAnonymous->setReturnValue('getId', 0);
        $userAnonymous->setReturnValue('isAnonymous', true);
        
        $cm->expectNever('setCookie');
        $um->setReturnReference('_getCookieManager', $cm);
        
        $dao->setReturnReference('searchByUserName', $dar, array('user_123'));
        $dar->setReturnValue('getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $um->setReturnReference('_getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        $um->setReturnReference('_getUserInstanceFromRow', $userAnonymous, array(array('user_id' => 0)));
        
        $dao->expectNever('createSession');
        $dao->expectOnce('storeLoginFailure');
        
        $um->setReturnReference('getDao', $dao);
        $this->assertReference($userAnonymous, $um->login('user_123', 'bad_pwd', 0));
    }
}
?>
