# Wordpress Integration

* **IMPORTANT: Please read this [THIS WARNING](info.html#mue) first!**

lang-learn-guy:
> I CANNOT give any support for this feature, NOR can I help you with any WordPress problems!
**USE AT YOUR OWN RISK!**

Hugo:
> The WordPress feature is a legacy function no longer maintained. It should be considered experimental as it may break with a new release of LWT.

The following instructions are for users who have installed WordPress, and want to install LWT for multiple WordPress users in conjunction with WordPress authentication. Every WordPress user will have his/her own LWT table set.  

1. [Download](https://wordpress.org/) and install WordPress.
2. [Download](https://github.com/hugofara/lwt/) and install LWT into a new subdirectory "lwt", located in the main directory of your WordPress installation.
3. In subdirectory "lwt", rename the file _connect\_wordpress.inc.php_ into _connect.inc.php_, and enter the database parameters $server (database server), $userid (database user id), $passwd (database password), and $dbname (database name, can be the same like your wordpress database, or a different one) by editing the file with a text editor.
4. In the WordPress General Settings, decide whether anyone can register and use LWT (Membership = "Anyone can register"), or not (an administrator must create new users). The "New User Default Role" should be "Subscriber".
5. The link to start LWT with **complete** WordPress authentication is:  
    _http&#58;&#47;&#47;...path-to-wp-blog.../lwt/wp\_lwt\_start.php_
6. The link to start LWT (without WordPress authentication, only by checking the session cookie that is valid until the browser is closed) is:  
    _http&#58;&#47;&#47;...path-to-wp-blog.../lwt/_  
    If the session cookie does not exist, both above start methods are the same.
7. To properly log out from both WordPress and LWT, use the link:  
    _http&#58;&#47;&#47;...path-to-wp-blog.../lwt/wp\_lwt\_stop.php_  
    The LWT home page has such a link. If you only log out via the links on the WordPress pages, you will still be able to use LWT until the browser is closed. If you want to log out from both WordPress and LWT, use the above link, or click on the link on the LWT home page!
8. If you delete a user, you must find out its user number (table "wp\_users"). After deleting the user in WordPress, you can delete all LWT tables with table names beginning with the user number plus an underscore "\_". You can do this in phpMyAdmin.
