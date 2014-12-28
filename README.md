cms-oauth
=========

With **cms-oauth** you can add OAuth authentication to your **CMS Made Simple** web site. It will wrap the **HybridAuth** PHP OAuth library into a CMS plugin. Profile data of authenticated users can optionally be saved into your CMS database for later reference.

Installation
------------

To install this plugin first download the [HybridAuth library](http://hybridauth.sourceforge.net/) and extract it to your local CMS installation under `lib/hybridauth`. In that directory then create or update `config.php` as described in the [HybridAuth documentation](http://hybridauth.sourceforge.net/userguide.html).

Afterwards download `function.oauth.php` from this repository and save it to the `plugins` folder of the CMS installation on your web server.

User Data Table
---------------

If you want to save user profile data of authenticated users into your CMS database then either download the `oauth_users.sql` file from this repository and  import it to your MySQL database (e.g. via PhpMyAdmin) or create the new table manually (as described in that SQL file). Then open the HybridAuth `config.php` file that you have created/updated before and add the following lines to the array (on the same level as `debug_mode`):

    // set this value to a non-empty value in order to save user data to DB
    "db_table" => "oauth_users",

Usage
-----

After you have saved the `function.oauth.php` file to your web server it is automatically enabled in your CMS installation. You can verify that under "Extensions" > "Tags" in the CMS admin panel, where you should find a new entry called "oauth".

If you haven't done so in the past already, create an empty template with no other content than this line:

    {content}

Then create a page that uses this template, with a page alias called "oauth", which has only one line as content:

    {oauth}

All you need to do now is to simply create a link on your web site to enable authentication with an OAuth provider that you have defined in the HybridAuth `config.php` file. For example, to enable authentication via Twitter you can add something like this:

    <a href="oauth?initlogin=Twitter" title="Login with Twitter">Login</a>

In other words: simply call the oauth page of your CMS with a query string parameter called "initlogin" with the OAuth provider of your choice as value. Of course you can create multiple such links for different providers.

After the user has authenticated he or she will be redirected to the original page (where you have added the login link). If authentication succeeded, the PHP session variable `$_SESSION['oauth_status']` will be set to "signed-in", and various profile data is available:

    $_SESSION['oauth_user_id']
    $_SESSION['oauth_user_name']
    $_SESSION['oauth_first_name']
    $_SESSION['oauth_last_name']
    $_SESSION['oauth_display_name']

If authentication failed, then `$_SESSION['oauth_status']` will be set to "auth-error", and `$_SESSION['oauth_error_msg']` set to an error message.
