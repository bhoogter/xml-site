@ECHO OFF

CD /D "%~dp0"

PUSHD ..\php-dependency-manager
php ./buildPhar.php
POPD

COPY ..\php-dependency-manager\php-dependency-manager.phar src\phars
