@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../heidelpay/phpdocumentor/bin/phpdoc
php "%BIN_TARGET%" %*
