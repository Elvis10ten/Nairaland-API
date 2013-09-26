Nairaland-API
=============
This is the first working Api for Nairaland. It is an Unofficial Api. It has most of the features of the main site and is written in OOP PHP.

If you use this script please include 'Elvis Chidera' in your credits, I would really appreciate it.

The unofficial Nairaland API
Nairaland Api 1.1 - Developed by Elvis Chidera
Release Date: 20th September 2013
First Release Date: 12th September 2013

CHANGELOG
=============
 - Added the Read class. frontPage, threads and readPosts methods now return an object of the Read class while viewProfile returns an array containing two objects of the Read class.
 - Optimized the code a bit
 - Added the sendEmail method to the Nairaland class
 - Fixed the bug when creating thread in demo.php
 - Fixed the bug in thread pagination on demo.php
 - Added the destruct method to delete cookies

INSTALLATION
=============
Unzip this file.
Edit the config.php file in the 'api' directory if you wish.
Create a directory on your webhost.
Upload the files in 'api' to the directory.
Include the Nairaland.php file in your script.

DEMO
=============
A demo can be found in the docs directory.

WARNING
=============
Use this script at your own risk. Be aware of ''Nairaland Rules and Regulation''.

REQUIREMENTS
=============
PHP 5+
cURL extension enabled in php.ini

