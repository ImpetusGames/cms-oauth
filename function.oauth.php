<?php

## Impetus Games OAuth Plugin.
## matthias@impetus-games.com
##
## Wrapper for the HybridAuth PHP library.
## See http://hybridauth.sourceforge.net/ for more info on HybridAuth.


include_once('lib/hybridauth/Hybrid/Auth.php');
include_once('lib/hybridauth/Hybrid/Logger.php'); 
include_once('lib/hybridauth/Hybrid/Endpoint.php'); 
include_once('lib/hybridauth/Hybrid/Exception.php'); 


function smarty_function_oauth($params, &$template) {
    $hybrid_auth_config = 'lib/hybridauth/config.php';

    // Clear session and return.
    if(isset($_GET['clearsession'])) {
        oauth_clear_session();
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }
    
    // Init (clear session), then do login.
    if(isset($_GET['initlogin'])) {
        oauth_clear_session();
        $_SESSION['oauth_return_to'] = $_SERVER['HTTP_REFERER'];
        header('Location: ' . oauth_get_current_uri() . '?dologin=' . $_GET['initlogin']);
        exit();
    }
    
    // Step 1: Start process, clear session, logout, check valid provider, etc.
    // Step 3: After login, request user data and save to DB.
    if(isset($_GET['dologin'])) {
        $config = include($hybrid_auth_config);
        $provider = $_GET['dologin'];
        if(array_key_exists($provider, $config['providers']) && $config['providers'][$provider]['enabled']) {    
            try {
                $_SESSION['oauth_provider'] = $provider;
    
                // Authenticate with OAuth provider
                $hybridauth = new Hybrid_Auth($hybrid_auth_config);
                $adapter = $hybridauth->authenticate($provider);

                // Get user data from OAuth provider and save it to DB
                $user_profile = $adapter->getUserProfile(); 
                $_SESSION['oauth_db_id'] = oauth_update_db($provider, $user_profile);
                
                // save user data and redirect home
                $_SESSION['oauth_user_id'] = $user_profile->identifier;
                $_SESSION['oauth_user_name'] = $user_profile->displayName;
                $_SESSION['oauth_first_name'] = $user_profile->firstName;
                $_SESSION['oauth_last_name'] = '';
                $_SESSION['oauth_display_name'] = $user_profile->firstName;
                if(isset($user_profile->lastName) && !empty($user_profile->lastName)) {
                    $_SESSION['oauth_display_name'] .= " " . $user_profile->lastName;
                    $_SESSION['oauth_last_name'] = $user_profile->lastName;
                }
                oauth_redirect_home('signed-in');
            }
            catch(Exception $ex) {
                oauth_redirect_home('auth-error', $ex->getMessage());
            }
        }
        
        else {
            $_SESSION['oauth_return_to'] = $_SERVER['HTTP_REFERER'];
            oauth_redirect_home('auth-error', 'Invalid OAuth provider: ' . $provider);
        }
    }
    
    // Step 2: Start the HybridAuth authentication "background process"
    if(isset($_REQUEST['hauth_start']) || isset($_REQUEST['hauth_done'])) {
        try {
            Hybrid_Endpoint::process();
        }
        catch(Exception $ex) {
            $_SESSION['oauth_status'] = 'auth-error';
            $_SESSION['oauth_error_msg'] = 'Authentication process failed, trying to restart';
            header('Location: ' . oauth_get_current_uri() . '?dologin=' . $_SESSION['oauth_provider']);
        }

        exit();
    }
    
    die("You must not call this script directly without parameters.");
}

/* Clear all oauth_* values in session and globally logout via HybridAuth. */
function oauth_clear_session() {
    /* clear OAuth session variables */
    foreach($_SESSION as $k => $v) {
        if(strpos($k, "oauth_") === 0) {
            unset($_SESSION[$k]);
        }
    }
    
    $_SESSION['oauth_session_cleared'] = true;

    /* Clear HybridAuth */
    $hybrid = new Hybrid_Auth('lib/hybridauth/config.php');
    $hybrid->logoutAllProviders();
}

/* Redirect to $_SESSION['return_to'], if set, or home (/) otherwise. */
function oauth_redirect_home($status, $error = null) {
    $_SESSION['oauth_status'] = $status;
    if($error)
        $_SESSION['oauth_error_msg'] = $error;
    else
        unset($_SESSION['oauth_error_msg']);
        
    header('Location: ' . (isset($_SESSION['oauth_return_to']) ? $_SESSION['oauth_return_to'] : '/'));
    exit();
}

/* Update user info in database. */
function oauth_update_db($provider, $data) {
    $config = include('lib/hybridauth/config.php');
    if(!array_key_exists('db_table', $config) || empty($config['db_table']))
        return;

    $users_tab = $config['db_table'];
    $db = cmsms()->GetDb();

    // first check whether user exists in DB already
    $sql = "SELECT * FROM " . $users_tab . " WHERE provider = '$provider' AND uid = ?";
    $result = $db->Execute($sql, array($data->identifier));
    if($result === false)
        die("Failed to select from database: " . $db->ErrorMsg());

    if($result->NumRows() == 0) {
        // insert new record into DB
        $sql = "INSERT INTO " . $users_tab . " (provider, uid, display_name, first_name, last_name, email) VALUES ('$provider', ?, ?, ?, ?, ?)";
        if(!$db->Execute($sql, array($data->identifier, $data->displayName, $data->firstName, $data->lastName, $data->email)))
            die("Failed to insert into database: " . $db->ErrorMsg());

    } else {
        // update existing record in DB
        $sql = "UPDATE " . $users_tab . " SET display_name = ?, first_name = ?, last_name = ?, email = ? WHERE provider = '$provider' AND uid = ?";
        if(!$db->Execute($sql, array($data->displayName, $data->firstName, $data->lastName, $data->email, $data->identifier)))
            die("Failed to update database: " . $db->ErrorMsg());
    }
    
    // query ID (primary key) of this DB record
    $sql = "SELECT id FROM " . $users_tab . " WHERE provider = '$provider' AND uid = ?";
    $result = $db->Execute($sql, array($data->identifier));
    if($result === false)
        die("Failed to select from database: " . $db->ErrorMsg());
    return $result->fields['id'];
}

/* Build the full URI path to the currently requested page. */
function oauth_get_current_uri() {
    return 'http' . (oauth_is_https() ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');
}

/* Determine whether client is connected to this host via HTTPS. */
function oauth_is_https() {
    return 
        (isset($_SERVER['REDIRECT_HTTPS']) && $_SERVER['REDIRECT_HTTPS'] === 'on') ||
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
        $_SERVER['SERVER_PORT'] == 443;
}

function smarty_cms_help_function_oauth() {
    echo("Wrapper for HybridAuth library.");
}

function smarty_cms_about_function_oauth() {
    ?>
    <p>Author: Matthias Brandstetter &lt;<a href="mailto:matthias@impetus-games.com">matthias AT impetus-games.com</a>&gt;</p>
    <p>See <a href="http://hybridauth.sourceforge.net/" target="_blank">http://hybridauth.sourceforge.net/</a> for more info on HybridAuth.</p>
    <br />
    <p>Version: 1.0</p>
    <p>
    Change History:
    <ul>
        <li>Initial Version.</li>
    </ul>
    </p>
    <?php
}

?>
