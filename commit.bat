@echo off
REM Script de Commit com Versionamento Automático
REM Uso: commit.bat "mensagem do commit" [major|minor|patch]

if "%~1"=="" (
    echo Erro: Mensagem do commit necessaria!
    echo Uso: commit.bat "mensagem do commit" [major^|minor^|patch]
    exit /b 1
)

set MESSAGE=%~1
set TYPE=%2
if "%TYPE%"=="" set TYPE=patch

echo.
echo ========================================
echo  Commit com Versionamento Automatico
echo ========================================
echo.

REM Adicionar todas as alterações
echo [1/4] Adicionando alteracoes ao stage...
git add .

REM Incrementar versão
echo [2/4] Incrementando versao (%TYPE%)...
php increment_version.php %TYPE%

REM Fazer commit
echo [3/4] Criando commit...
git commit -m "%MESSAGE%"

REM Push automático (opcional - comente se não quiser)
echo [4/4] Enviando para o repositorio remoto...
git push

echo.
echo ✅ Commit completo com nova versao!
echo.
