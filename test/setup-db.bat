@echo off
REM Setup do Banco de Dados de Testes
REM Execute: test\setup-db.bat

echo.
echo Configurando banco de dados de testes...
echo.

REM Verifica se o container db-test está rodando
docker compose ps db-test | findstr "Up" >nul 2>&1
if errorlevel 1 (
    echo Container db-test nao esta rodando. Iniciando...
    docker compose up -d db-test
    echo Aguardando container inicializar...
    timeout /t 5 /nobreak >nul
)

REM Recria o banco de dados
echo Recriando database hyperf_test...
docker compose exec db-test mysql -u root -proot -e "DROP DATABASE IF EXISTS hyperf_test; CREATE DATABASE hyperf_test;" 2>nul

REM Cria as tabelas
echo Criando tabelas...
type test\schema.sql | docker compose exec -T db-test mysql -u root -proot hyperf_test

REM Verifica se as tabelas foram criadas
echo.
echo Verificando tabelas criadas...
docker compose exec db-test mysql -u root -proot hyperf_test -e "SHOW TABLES;"

echo.
echo Setup completo! Agora voce pode executar os testes:
echo    docker compose exec application composer test
echo.
