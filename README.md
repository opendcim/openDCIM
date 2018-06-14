openDCIM
-----------


	An Open Source Software package for managing the infrastructure of a 
	data center, no matter how small or large.  Initially developed 
	in-house at Vanderbilt University Information Technology Services by 
	Scott Milliken.  

	After leaving Vanderbilt for Oak Ridge National Laboratory, Vanderbilt 
	granted permission for the package to be open sourced under GPLv3.  
	Scott continues as the primary contributor to the package and is 
	actively recruiting assistance from others.

        This program is free software:  you can redistribute it and/or modify
        it under the terms of the GNU General Public License as published
        by the Free Software Foundation, version 3.

        This program is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.

        For further details on the license, see http://www.gnu.org/licenses


*THE CURRENT RELEASE IN GITHUB IS FOR DEVELOPMENT ONLY*
-------------------------------------------------------

## Github issues are not for asking questions - use the mailing list for that.
[Official Website](http://www.opendcim.org/participation.html)

Installation
------------
Supposing you are using apache, php and apache-php-module firstly clone openDCIM in a directory which is accessible by apache user (e.g. /srv/http/) and then configure apache to load required modules and have access to project directory (you can define virtual host too).

If you're gonna create Dockerized development environment, you should enable apache's fast-cgi to connect to php-fpm's container

## Database (PDO, PDODRIVERS, DB.INC)

### Install and Configure Mysql and PHP-Mysql
Follow the [guide](http://php.net/manual/en/book.mysql.php)

### Create Database and Configuration File
Create the database and user
```shell
	mysql -uroot -p -e "CREATE DATABASE dcim;CREATE USER 'dcim'@'localhost' IDENTIFIED BY 'dcim';GRANT ALL ON dcim.* TO 'dcim'@'localhost';"
```

Make db.inc.php from db.inc.php-dist
```shell
	cp db.inc.php-dist db.inc.php
```

## PHP SNMP Module
Install [php-snmp](http://php.net/manual/en/book.snmp.php) and enable it in `/etc/php/php.ini` by uncomment or adding the line containing: `extension=snmp.so`

## Apache User Authentication (AUTHENTICATION, REMOTE USER)
Enbale below apache modules:
* mod_authn_file.so
* mod_authn_core.so
* mod_authz_user.so
* mod_authz_core.so
* mod_auth_basic.so

Then follow this [link](http://www.apacheweek.com/features/userauth) To create apache authentication database (such as htpasswd) and enable apache auth in openDCIM directory (e.g. using .htaccess in root). As an example you can follow this instruction (Don't forget to change paths and names to correct onse):
```shell
sudo htpasswd -c /etc/httpd/users pouyan
echo 'AuthName "restricted stuff"\nAuthType Basic\nAuthUserFile /etc/httpd/users\nrequire valid-user' > /srv/http/openDCIM
```

## Apache Rewrite (MOD_REWRITE)
Install [Apache Rewrite Module](http://httpd.apache.org/docs/current/mod/mod_rewrite.html) and enable it

## Run the application
Execute application by openning it in browser and do the installation

Contribution
---
Contributions are always welcome, please follow these steps to submit your changes:

1. Install git from http://git-scm.com/
2. Create a github account on https://github.com
3. Set up your git ssh key using these instructions http://help.github.com/set-up-git-redirect
4. Open the openDCIM project home page on github on https://github.com/samilliken/openDCIM/
5. Click the "Fork" button, this will get you to a new page: your own copy of the code.
6. Copy the SSH URL at the top of the page and clone the repository on your local machine

    ```shell
    git clone git@github.com:your-username/openDCIM.git my-opendcim-repo
    ```

7. Create a branch and switch to it

    ```shell
    cd my-opendcim-repo
    git branch mynewfeature-patch
    git checkout mynewfeature-patch
    ```

8. Apply your changes, then commit using a meaningful comment, that's the comment everybody will see!

    ```shell
    git add .
    git commit -m "Fixing issue 157, blablabla"
    ```

9. Push the changes back to github (under a different branch, here myfeature-patch)

    ```shell
    git push origin mynewfeature-patch
    ```

10. Open your forked repository on github at https://github.com/your-username/openDCIM
11. Click "Switch Branches" and select your branch (mynewfeature-patch)
12. Click "Pull Request"
13. Submit your pull request to the openDCIM Developers

Translation - i18n
---
We do not accept any po files directly.  Please use the very simple, user friendly web interface at getlocalization.

https://www.getlocalization.com/opendcim/
