@echo off
echo Execution des tests unitaires uniquement...

REM Exécuter seulement les tests unitaires
php bin/phpunit --filter=Unit --coverage-clover=coverage.xml --testdox

echo Tests unitaires termines! 