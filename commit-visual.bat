@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

:MENU
cls
echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║                                                            ║
echo ║          🚀 SISTEMA DE COMMIT E VERSIONAMENTO 🚀          ║
echo ║                                                            ║
echo ╚════════════════════════════════════════════════════════════╝
echo.

REM Ler versão atual
if exist version.json (
    for /f "tokens=*" %%a in ('php -r "$v=json_decode(file_get_contents('version.json'),true); echo $v['version'];"') do set CURRENT_VERSION=%%a
) else (
    set CURRENT_VERSION=1.0.0
)

echo    📦 Versão Atual: %CURRENT_VERSION%
echo.
echo ════════════════════════════════════════════════════════════
echo.
echo    TIPO DE ATUALIZAÇÃO:
echo.
echo    [1] 🐛 PATCH - Correções e Ajustes
echo        └─ Para bugs, erros, melhorias pequenas
echo        └─ Exemplo: 1.1.3 → 1.1.4
echo.
echo    [2] ✨ MINOR - Novas Funcionalidades
echo        └─ Para recursos novos, páginas, módulos
echo        └─ Exemplo: 1.1.3 → 1.2.0
echo.
echo    [3] 🎉 MAJOR - Mudanças Importantes
echo        └─ Para reestruturações, versões principais
echo        └─ Exemplo: 1.1.3 → 2.0.0
echo.
echo ────────────────────────────────────────────────────────────
echo.
echo    [4] 📋 Ver Status do Git
echo    [5] 📜 Ver Últimos Commits
echo.
echo    [0] ❌ Sair
echo.
echo ════════════════════════════════════════════════════════════
echo.
set /p OPCAO="    👉 Escolha uma opção: "

if "%OPCAO%"=="1" goto COMMIT_PATCH
if "%OPCAO%"=="2" goto COMMIT_MINOR
if "%OPCAO%"=="3" goto COMMIT_MAJOR
if "%OPCAO%"=="4" goto STATUS
if "%OPCAO%"=="5" goto LOG
if "%OPCAO%"=="0" goto SAIR
goto MENU

:COMMIT_PATCH
cls
echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║           🐛 CORREÇÃO DE BUG (PATCH)                      ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
echo    Exemplos:
echo    - "Corrigido erro no login"
echo    - "Ajustado bug na busca"
echo    - "Melhorado desempenho da página"
echo.
set /p MESSAGE="    💬 Mensagem do commit: "
if "%MESSAGE%"=="" (
    echo.
    echo    ❌ Mensagem não pode estar vazia!
    timeout /t 2 >nul
    goto MENU
)
set TYPE=patch
goto EXECUTAR

:COMMIT_MINOR
cls
echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║           ✨ NOVA FUNCIONALIDADE (MINOR)                  ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
echo    Exemplos:
echo    - "Adicionado sistema de notificações"
echo    - "Implementado modo escuro"
echo    - "Nova página de relatórios"
echo.
set /p MESSAGE="    💬 Mensagem do commit: "
if "%MESSAGE%"=="" (
    echo.
    echo    ❌ Mensagem não pode estar vazia!
    timeout /t 2 >nul
    goto MENU
)
set TYPE=minor
goto EXECUTAR

:COMMIT_MAJOR
cls
echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║           🎉 VERSÃO MAJOR                                  ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
echo    ⚠️  ATENÇÃO: Use apenas para mudanças significativas!
echo.
echo    Exemplos:
echo    - "Versão 2.0 - Nova arquitetura"
echo    - "Reformulação completa da interface"
echo    - "Migração para nova tecnologia"
echo.
set /p MESSAGE="    💬 Mensagem do commit: "
if "%MESSAGE%"=="" (
    echo.
    echo    ❌ Mensagem não pode estar vazia!
    timeout /t 2 >nul
    goto MENU
)
echo.
set /p CONFIRM="    ⚠️  Tem certeza? (S/N): "
if /i not "%CONFIRM%"=="S" goto MENU
set TYPE=major
goto EXECUTAR

:EXECUTAR
cls
echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║           🚀 PROCESSANDO COMMIT...                        ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
echo    [1/4] 📋 Adicionando alterações...
git add .
echo    ✅ Arquivos adicionados ao stage
echo.

echo    [2/4] 📦 Incrementando versão (%TYPE%)...
php increment_version.php %TYPE%
if errorlevel 1 (
    echo    ❌ Erro ao incrementar versão!
    pause
    goto MENU
)
echo.

echo    [3/4] 💾 Criando commit...
git commit -m "%MESSAGE%"
if errorlevel 1 (
    echo    ❌ Erro ao criar commit!
    pause
    goto MENU
)
echo.

echo    [4/4] ☁️  Enviando para o repositório...
git push
if errorlevel 1 (
    echo    ⚠️  Aviso: Erro ao fazer push (talvez seja necessário fazer pull primeiro)
) else (
    echo    ✅ Push concluído com sucesso!
)
echo.

REM Ler nova versão
for /f "tokens=*" %%a in ('php -r "$v=json_decode(file_get_contents('version.json'),true); echo $v['version'];"') do set NEW_VERSION=%%a

echo ════════════════════════════════════════════════════════════
echo.
echo    ✅ COMMIT COMPLETO!
echo    📦 Nova Versão: %NEW_VERSION%
echo    💬 Mensagem: %MESSAGE%
echo.
echo ════════════════════════════════════════════════════════════
echo.
pause
goto MENU

:STATUS
cls
echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║           📋 STATUS DO GIT                                ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
git status
echo.
echo ════════════════════════════════════════════════════════════
pause
goto MENU

:LOG
cls
echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║           📜 ÚLTIMOS 10 COMMITS                           ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
git log --oneline --decorate --graph -10
echo.
echo ════════════════════════════════════════════════════════════
pause
goto MENU

:SAIR
cls
echo.
echo    👋 Até logo!
echo.
timeout /t 1 >nul
exit
