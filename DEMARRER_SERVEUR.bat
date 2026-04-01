@echo off
color 0A
title IDENE PARFUM - Serveur Local

echo.
echo ========================================
echo    IDENE PARFUM - Serveur Local
echo ========================================
echo.
echo Demarrage du serveur...
echo.
echo IMPORTANT: Ne fermez pas cette fenetre!
echo.
echo Le serveur sera accessible sur:
echo   http://localhost:8000/accueil
echo   http://localhost:8000/auth
echo.
echo Appuyez sur Ctrl+C pour arreter le serveur
echo.
echo ========================================
echo.

cd /d "%~dp0"
php -S localhost:8000 -t public

pause
