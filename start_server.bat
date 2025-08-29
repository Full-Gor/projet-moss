@echo off
echo Démarrage du serveur Symfony avec Laragon PHP...
echo.

REM Définir le chemin vers PHP Laragon
set PHP_PATH=C:\laragon\bin\php\php-8.3.4-nts-Win32-vs16-x64\php.exe

REM Vérifier que PHP existe
if not exist "%PHP_PATH%" (
    echo ERREUR: PHP Laragon non trouvé à %PHP_PATH%
    pause
    exit /b 1
)

echo PHP trouvé: %PHP_PATH%
echo.

REM Démarrer le serveur
echo Démarrage du serveur sur http://localhost:8000
echo Appuyez sur Ctrl+C pour arrêter
echo.

"%PHP_PATH%" -S localhost:8000 -t public

pause 